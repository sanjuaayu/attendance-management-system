<?php
/**
 * Unified Login Process Handler
 * Handles: user, hr, manager, parent_admin, admin
 */

session_start();
require_once 'config.php';

// Only process POST requests
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Get and sanitize input
$username = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
$password = $_POST['password'] ?? '';
$role = strtolower(trim($_POST['role'] ?? 'user'));

// Validate input
if (empty($username) || empty($password)) {
    $_SESSION['error'] = "Please enter both username and password.";
    header("Location: " . getLoginPage($role) . "?error=empty");
    exit;
}

// Prepare and execute query
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = ? LIMIT 1");
$stmt->bind_param("ss", $username, $role);
$stmt->execute();
$result = $stmt->get_result();

// Check if user exists
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verify password
    if (password_verify($password, $user['password'])) {
        // ✅ Set all session variables
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['full_name']     = $user['full_name'] ?? '';
        $_SESSION['branch']        = $user['branch'] ?? '';
        $_SESSION['branch_id']     = $user['branch_id'] ?? 0;
        $_SESSION['employee_code'] = $user['employee_code'] ?? '';

        // Clear any previous error messages
        unset($_SESSION['error']);

        // Redirect to appropriate dashboard
        $stmt->close();
        $conn->close();
        
        header("Location: " . getDashboard($user['role']));
        exit;
    }
}

// Login failed - redirect back with error
$stmt->close();
$conn->close();

$_SESSION['error'] = "Invalid username or password.";
header("Location: " . getLoginPage($role) . "?error=invalid");
exit;

/**
 * Get login page based on role
 */
function getLoginPage($role) {
    switch($role) {
        case 'parent_admin':
            return 'parent-login.php';
        case 'hr':
            return 'hrsection-login.php';
        case 'manager':
            return 'manager-login.php';
        case 'admin':
            return 'admin-login.php';
        case 'user':
        default:
            return 'index.php';
    }
}

/**
 * Get dashboard based on role
 */
function getDashboard($role) {
    switch($role) {
        case 'parent_admin':
            return 'parent_admin_panel.php';
        case 'hr':
            return 'hr-dashboard.php';
        case 'manager':
            return 'manager-dashboard.php';
        case 'admin':
            return 'admin-dashboard.php';
        case 'user':
            return 'branch-selection.php';
        default:
            return 'index.php';
    }
}
?>
