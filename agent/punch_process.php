<?php
// Process punch in/out requests
session_start();
require_once 'config.php';

// Check if user is logged in as agent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'agent') {
    header("Location: index.php");
    exit;
}

// Check if branch is selected
if (!isset($_SESSION['branch']) || empty($_SESSION['branch'])) {
    header("Location: branch-selection.php");
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $branch_name = $_SESSION['branch']; // Use branch name instead of branch_id
    $action = $_POST['action'] ?? ''; // 'punch_in' or 'punch_out'
    $location_lat = $_POST['location_lat'] ?? null;
    $location_lng = $_POST['location_lng'] ?? null;
    $photo_data = $_POST['photo_data'] ?? null;
    
    // Validate input
    if (empty($action)) {
        $_SESSION['error'] = "Invalid action.";
        header("Location: punch-attendance.php");
        exit;
    }
    
    // Function: save photo if provided
    function savePhoto($photo_data, $prefix, $user_id) {
        if (!$photo_data) return null;

        $photo_data = str_replace('data:image/png;base64,', '', $photo_data);
        $photo_data = str_replace(' ', '+', $photo_data);
        $photo_binary = base64_decode($photo_data);

        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        $photo_filename = 'uploads/' . $prefix . '_' . $user_id . '_' . time() . '.png';
        if (file_put_contents($photo_filename, $photo_binary)) {
            return $photo_filename;
        }
        return null;
    }
    
    // Punch In
    if ($action == 'punch_in') {
        // Check if user already punched in today
        $stmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND punch_out_datetime IS NULL ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['error'] = "You are already punched in. Please punch out first.";
            $stmt->close();
            header("Location: punch-attendance.php");
            exit;
        }
        $stmt->close();
        
        $photo_filename = savePhoto($photo_data, "punch_in", $user_id);
        
        // Insert punch in record with branch name
       // Corrected Code without branch_name
       $stmt = $conn->prepare("INSERT INTO attendance 
       (user_id, punch_in_datetime, punch_in_location_lat, punch_in_location_lng, punch_in_photo) 
       VALUES (?, NOW(), ?, ?, ?)");
$stmt->bind_param("idss", $user_id, $location_lat, $location_lng, $photo_filename);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Punch in successful at " . date('h:i A') . "!";
        } else {
            $_SESSION['error'] = "Failed to record punch in. Error: " . $conn->error;
        }
        $stmt->close();
    }
    // Punch Out
    else if ($action == 'punch_out') {
        // Check if user has punched in but not out
        $stmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND punch_out_datetime IS NULL ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $_SESSION['error'] = "No punch in record found. Please punch in first.";
            $stmt->close();
            header("Location: punch-attendance.php");
            exit;
        }
        $stmt->close();
        
        $photo_filename = savePhoto($photo_data, "punch_out", $user_id);
        
        // Update punch out for the latest record where punch_out is NULL
        $stmt = $conn->prepare("UPDATE attendance 
            SET punch_out_datetime = NOW(), 
                punch_out_location_lat = ?, 
                punch_out_location_lng = ?, 
                punch_out_photo = ? 
            WHERE user_id = ? AND punch_out_datetime IS NULL 
            ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("ddsi", $location_lat, $location_lng, $photo_filename, $user_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['success'] = "Punch out successful at " . date('h:i A') . "!";
            } else {
                $_SESSION['error'] = "No punch in record found to punch out from.";
            }
        } else {
            $_SESSION['error'] = "Failed to record punch out. Error: " . $conn->error;
        }
        $stmt->close();
    }
    
    header("Location: punch-attendance.php");
    exit;
} else {
    // If not a POST request, redirect to punch attendance page
    header("Location: punch-attendance.php");
    exit;
}

$conn->close();
?>
