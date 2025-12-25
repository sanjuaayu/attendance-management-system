<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'rupeeqat_employee_attendance');
define('DB_PASSWORD', '4yknR4hLtwCKRjSHbdM5');
define('DB_NAME', 'rupeeqat_employee_attendance');

// Create connection using defined constants
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>