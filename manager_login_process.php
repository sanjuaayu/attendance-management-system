<?php
session_start();
require_once 'config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE username = '$username' AND role = 'manager' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        if(password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['branch'] = $user['branch'];
            $_SESSION['branch_id'] = $user['branch_id'];
            
            header('Location: manager-dashboard.php');
            exit();
        }
    }
    
    header('Location: manager-login.php?error=invalid');
    exit();
}

header('Location: manager-login.php');
exit();
?>
