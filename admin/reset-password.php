<?php
require_once 'config.php';

$username = 'princea40_priya';
$new_password = 'agent123';
$hashed = password_hash($new_password, PASSWORD_DEFAULT);

$query = "UPDATE users SET password = '$hashed', role = 'user' WHERE username = '$username'";
mysqli_query($conn, $query);

echo "✅ Password reset successful!<br>";
echo "Username: $username<br>";
echo "New Password: $new_password<br>";
echo "<br><a href='index.php'>Go to Login</a>";
?>
