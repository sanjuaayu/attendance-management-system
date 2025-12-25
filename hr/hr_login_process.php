<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Please enter both username and password.";
        header("Location: hrsection-login.php");
        exit;
    }

    // Check if user is HR
    $stmt = $conn->prepare("SELECT id, username, password, role, full_name, branch FROM users WHERE username = ? AND role = 'hr'");
    
    if(!$stmt) {
        $_SESSION['error'] = "Database error. Please try again.";
        header("Location: hrsection-login.php");
        exit;
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Login successful - set session variables
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['hr_id']     = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = 'hr';
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['hr_name']   = $user['full_name'];
            $_SESSION['branch']    = $user['branch'];
            $_SESSION['hr_branch'] = $user['branch'];
            $_SESSION['logged_in'] = true;

            // Redirect to HR Dashboard
            header("Location: hr-dashboard.php");
            exit;
        } else {
            $_SESSION['error'] = "Invalid username or password.";
            header("Location: hrsection-login.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "Invalid HR credentials. Please check your username.";
        header("Location: hrsection-login.php");
        exit;
    }

    $stmt->close();
} else {
    header("Location: hrsection-login.php");
    exit;
}

$conn->close();
?>
