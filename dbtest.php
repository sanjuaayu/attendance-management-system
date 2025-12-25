<?php
$servername = "localhost";
$username   = "rupeeqat_employee_attendance"; 
$password   = "4yknR4hLtwCKRjSHbdM5"; 
$dbname     = "rupeeqat_employee_attendance"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "✅ Database Connected Successfully!";
?>
