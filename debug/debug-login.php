<?php
session_start();
require_once 'config.php';

echo "<h2>🔍 Login Debug</h2>";
echo "<hr>";

// Test credentials
$test_username = 'princea40_priya';
$test_password = 'agent123'; // Replace with actual password

echo "<h3>Step 1: Check Database Connection</h3>";
if($conn) {
    echo "✅ Database connected!<br>";
} else {
    echo "❌ Database connection failed!<br>";
    die();
}

echo "<h3>Step 2: Check User Exists</h3>";
$query = "SELECT * FROM users WHERE username = '$test_username'";
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) > 0) {
    echo "✅ User found!<br>";
    $user = mysqli_fetch_assoc($result);
    
    echo "<h3>User Data:</h3>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Username: " . $user['username'] . "<br>";
    echo "Role: " . ($user['role'] ?: '<strong style="color:red;">BLANK/NULL</strong>') . "<br>";
    echo "Full Name: " . $user['full_name'] . "<br>";
    echo "Password Hash: " . substr($user['password'], 0, 20) . "...<br>";
    echo "Password Length: " . strlen($user['password']) . " chars<br>";
    
    echo "<h3>Step 3: Test Password</h3>";
    if(password_verify($test_password, $user['password'])) {
        echo "✅ Password MATCHES!<br>";
    } else {
        echo "❌ Password DOES NOT MATCH!<br>";
        echo "<p style='color:red;'>Try these passwords: 12345, 123456, agent123, password</p>";
    }
    
    echo "<h3>Step 4: Check Role</h3>";
    if(empty($user['role']) || $user['role'] == '') {
        echo "❌ Role is BLANK! Fix this:<br>";
        echo "<code>UPDATE users SET role = 'user' WHERE username = '$test_username';</code><br>";
    } else {
        echo "✅ Role is set: " . $user['role'] . "<br>";
    }
    
    echo "<h3>Step 5: Check Login Query</h3>";
    $role = 'user';
    $login_query = "SELECT * FROM users WHERE username = '$test_username' AND role = '$role'";
    $login_result = mysqli_query($conn, $login_query);
    
    if(mysqli_num_rows($login_result) > 0) {
        echo "✅ Login query works!<br>";
    } else {
        echo "❌ Login query FAILS! (role mismatch)<br>";
    }
    
} else {
    echo "❌ User NOT found!<br>";
    echo "Username tried: $test_username<br>";
    echo "<p>Check if username is correct in database.</p>";
}

echo "<hr>";
echo "<h3>All Users with Role 'user':</h3>";
$all_users = mysqli_query($conn, "SELECT username, role FROM users WHERE role = 'user' LIMIT 5");
while($u = mysqli_fetch_assoc($all_users)) {
    echo "- " . $u['username'] . " (role: " . $u['role'] . ")<br>";
}

echo "<hr>";
echo "<a href='index.php'>← Back to Login</a>";
?>
