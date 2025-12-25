<?php
// Handle branch selection for agents
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'agent') {
    header("Location: agent-login.php");
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['branch'])) {
    $branch = $_POST['branch'];
    $user_id = $_SESSION['user_id'];
    
    // Update user's branch in database
    $stmt = $conn->prepare("UPDATE users SET branch = ? WHERE id = ?");
    $stmt->bind_param("si", $branch, $user_id);
    
    if ($stmt->execute()) {
        // Update session with branch
        $_SESSION['branch'] = $branch;
        
        // Redirect to punch attendance page
        header("Location: punch-attendance.php");
        exit;
    } else {
        $_SESSION['error'] = "Failed to update branch selection.";
        header("Location: branch-selection.php");
        exit;
    }
    
    $stmt->close();
} else {
    // If not a POST request or no branch selected, redirect to branch selection
    header("Location: branch-selection.php");
    exit;
}

$conn->close();
?>