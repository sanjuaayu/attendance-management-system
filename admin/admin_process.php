<?php
// Process admin actions
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: admin-login.php");
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_user') {
        // Handle add user
        $username = $_POST['username'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'agent';
        
        // Validate input
        if (empty($username) || empty($full_name) || empty($password)) {
            $_SESSION['error'] = "All fields are required.";
            header("Location: admin-dashboard.html");
            exit;
        }
        
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['error'] = "Username already exists.";
            header("Location: admin-dashboard.html");
            exit;
        }
        
        $stmt->close();
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $hashed_password, $role, $full_name);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "User added successfully.";
        } else {
            $_SESSION['error'] = "Failed to add user.";
        }
        
        $stmt->close();
        header("Location: admin-dashboard.html");
        exit;
    } else if ($action == 'delete_user') {
        // Handle delete user
        $user_id = $_POST['user_id'] ?? 0;
        
        // Validate input
        if (empty($user_id) || $user_id == $_SESSION['user_id']) {
            $_SESSION['error'] = "Invalid user ID.";
            header("Location: admin-dashboard.html");
            exit;
        }
        
        // Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            // Also delete user's attendance records
            $stmt2 = $conn->prepare("DELETE FROM attendance WHERE user_id = ?");
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();
            $stmt2->close();
            
            $_SESSION['success'] = "User deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete user.";
        }
        
        $stmt->close();
        header("Location: admin-dashboard.php");
        exit;
    } else {
        $_SESSION['error'] = "Invalid action.";
        header("Location: admin-dashboard.php");
        exit;
    }
} else {
    // If not a POST request, redirect to admin dashboard
    header("Location: admin-dashboard.php");
    exit;
}

$conn->close();
?>
