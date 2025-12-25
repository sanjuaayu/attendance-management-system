<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$error = "";

// Agar form submit hua
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // DB connect
include 'config.php';
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Parent Admin record nikaalo
    $sql = "SELECT * FROM users WHERE username = ? AND role = 'parent_admin' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Password verify karo
        if (password_verify($password, $user['password'])) {
            // Session set karo
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // ✅ Redirect to parent_admin_panel.php
            header("Location: parent_admin_panel.php");
            exit;
        } else {
            $error = "❌ Invalid password!";
        }
    } else {
        $error = "❌ User not found!";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Parent Admin Login</title>
    <style>
        body { background: #f4f6f9; font-family: Arial; }
        .login-box { width: 350px; margin: 80px auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0px 0px 10px #ccc; }
        h2 { text-align: center; margin-bottom: 20px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border-radius: 6px; border: 1px solid #ccc; }
        button { width: 100%; padding: 10px; background: linear-gradient(to right, #6c63ff, #1e3c72); color: white; border: none; border-radius: 6px; font-weight: bold; }
        button:hover { background: linear-gradient(to right, #5a52e0, #162c5a); }
        .error { color: red; text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    <div class="login-box">
        <img src="rupeeq.png" alt="Company Logo" class="company-logo">
        <h2>Parent Admin Login</h2>
        <?php if (!empty($error)) { echo "<p class='error'>$error</p>"; } ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Enter Username" required>
            <input type="password" name="password" placeholder="Enter Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
