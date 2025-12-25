<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Employee Attendance System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <img src="rupeeq.png" alt="Company Logo" class="company-logo">
            <h2>Admin Login</h2>
            <?php
            session_start();
            if (isset($_SESSION['error'])) {
                echo '<div class="error-message" style="color: red; margin-bottom: 15px; text-align: center;">' . $_SESSION['error'] . '</div>';
                unset($_SESSION['error']);
            }
            ?>
            <form action="login_process.php" method="POST">
                <input type="hidden" name="role" value="admin">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="login-btn">Login</button>
            </form>
            <div class="links">
<p>Are you Parent Admin? <a href="parent-dashboard.php">Login here</a></p>
            </div>
        </div>
    </div>
</body>
</html>