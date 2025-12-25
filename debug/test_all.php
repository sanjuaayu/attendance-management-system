<?php
// Comprehensive test script for the Employee Attendance System
require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Employee Attendance System - Test Results</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        h2 { color: #333; }
        ul { list-style-type: none; padding: 0; }
        li { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .success-bg { background-color: #d4edda; }
        .error-bg { background-color: #f8d7da; }
        .info-bg { background-color: #d1ecf1; }
    </style>
</head>
<body>
    <h1>Employee Attendance System - Test Results</h1>";

// Test 1: Database Connection
echo "<h2>Test 1: Database Connection</h2>";
if ($conn) {
    echo "<li class='success success-bg'>✓ Successfully connected to database: " . DB_NAME . "</li>";
} else {
    echo "<li class='error error-bg'>✗ Failed to connect to database: " . $conn->connect_error . "</li>";
    exit;
}

// Test 2: Database Tables
echo "<h2>Test 2: Database Tables</h2>";

$tables = ['users', 'attendance', 'branches'];
$all_tables_exist = true;

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "<li class='success success-bg'>✓ '$table' table exists</li>";
    } else {
        echo "<li class='error error-bg'>✗ '$table' table does not exist</li>";
        $all_tables_exist = false;
    }
}

if (!$all_tables_exist) {
    echo "<li class='error error-bg'>Some tables are missing. Please run create_database.php to create all tables.</li>";
}

// Test 3: Sample Data
echo "<h2>Test 3: Sample Data</h2>";

// Check if admin user exists
$stmt = $conn->prepare("SELECT id, username, full_name FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "<li class='success success-bg'>✓ Admin user found: " . $admin['full_name'] . " (" . $admin['username'] . ")</li>";
} else {
    echo "<li class='info info-bg'>ℹ No admin user found. Default admin user will be created when you run create_database.php</li>";
}

$stmt->close();

// Check if branches exist
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM branches");
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['count'];
$stmt->close();

if ($count > 0) {
    echo "<li class='success success-bg'>✓ Found $count branch(es) in the database</li>";
} else {
    echo "<li class='info info-bg'>ℹ No branches found. Default branches will be created when you run create_database.php</li>";
}

// Test 4: File Structure
echo "<h2>Test 4: File Structure</h2>";

$required_files = [
    'config.php',
    'create_database.php',
    'login_process.php',
    'select_branch.php',
    'punch_process.php',
    'logout.php',
    'admin_process.php',
    'agent-login.html',
    'admin-login.html',
    'branch-selection.html',
    'punch-attendance.html',
    'admin-dashboard.html',
    'css/style.css'
];

$all_files_exist = true;

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<li class='success success-bg'>✓ $file exists</li>";
    } else {
        echo "<li class='error error-bg'>✗ $file is missing</li>";
        $all_files_exist = false;
    }
}

if (!$all_files_exist) {
    echo "<li class='error error-bg'>Some required files are missing. Please check your file structure.</li>";
}

// Test 5: Uploads Directory
echo "<h2>Test 5: Uploads Directory</h2>";

if (is_dir('uploads')) {
    echo "<li class='success success-bg'>✓ Uploads directory exists</li>";
} else {
    echo "<li class='info info-bg'>ℹ Uploads directory does not exist. It will be created automatically when users upload photos.</li>";
}

$conn->close();

echo "<h2>Test Summary</h2>";

if ($conn && $all_tables_exist && $all_files_exist) {
    echo "<li class='success success-bg'>✓ All tests passed! Your Employee Attendance System is ready to use.</li>";
    echo "<li class='info info-bg'>ℹ To get started:
        <ul>
            <li>Run create_database.php to create the database tables and default admin user</li>
            <li>Access the application through your web browser</li>
            <li>Login with username: admin, password: admin123</li>
        </ul>
    </li>";
} else {
    echo "<li class='error error-bg'>✗ Some tests failed. Please check the errors above and fix them before using the application.</li>";
}

echo "</body>
</html>";
?>
