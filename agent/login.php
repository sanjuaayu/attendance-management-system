<?php
session_start();
require_once 'config.php';

// ✅ Input validation
if (!isset($_POST['username'], $_POST['password'])) {
    die("Missing credentials.");
}

$username = trim($_POST['username']);
$password = trim($_POST['password']);

// ✅ Prepare and execute query
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // ✅ Verify password
    if (password_verify($password, $user['password'])) {
        session_regenerate_id(true); // 🔒 Prevent session fixation
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // ✅ Role-based redirect
        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
            exit;
        } elseif ($user['role'] === 'parent_admin') {
            header("Location: parent_admin_panel.php");
            exit;
        } else {
            echo "❌ Unauthorized role.";
        }
    } else {
        echo "❌ Incorrect password.";
    }
} else {
    echo "❌ Username not found.";
}

// ✅ Clean up
$stmt->close();
$conn->close();
?>
