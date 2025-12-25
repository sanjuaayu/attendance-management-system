<?php
// Database creation script for Employee Attendance System

// Database configuration
$host = 'localhost';
$username = 'root';
$password = ''; // Default password for Laragon is empty
$database = 'rupeeqat_employee_attendance';

// Create connection
echo "Connecting to MySQL server...\n";
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully to MySQL server.\n";

// Create database
echo "Creating database '$database'...\n";
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === TRUE) {
    echo "Database '$database' created successfully or already exists.\n";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($database);

// Create users table
echo "Creating 'users' table...\n";
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('agent', 'admin') NOT NULL,
    full_name VARCHAR(100),
    branch VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'users' created successfully.\n";
} else {
    echo "Error creating table 'users': " . $conn->error . "\n";
}

// Create attendance table
echo "Creating 'attendance' table...\n";
$sql = "CREATE TABLE IF NOT EXISTS attendance (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    punch_in_datetime DATETIME,
    punch_out_datetime DATETIME NULL,
    punch_in_location_lat DECIMAL(10, 8) NULL,
    punch_in_location_lng DECIMAL(11, 8) NULL,
    punch_out_location_lat DECIMAL(10, 8) NULL,
    punch_out_location_lng DECIMAL(11, 8) NULL,
    punch_in_photo VARCHAR(255) NULL,
    punch_out_photo VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'attendance' created successfully.\n";
} else {
    echo "Error creating table 'attendance': " . $conn->error . "\n";
}

// Create branches table
echo "Creating 'branches' table...\n";
$sql = "CREATE TABLE IF NOT EXISTS branches (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'branches' created successfully.\n";
} else {
    echo "Error creating table 'branches': " . $conn->error . "\n";
}

// Insert default branches
echo "Inserting default branches...\n";
$branches = [
    'Prince Gupta',
    'Sonali mam',
    'Mukesh',
    'Rohit',
    'Abhishek',
    'Rohit Tandand',
    'Asfaq',
    'Backend Team',
    'Codexa Team'
];

foreach ($branches as $branch) {
    $stmt = $conn->prepare("INSERT IGNORE INTO branches (name) VALUES (?)");
    $stmt->bind_param("s", $branch);
    $stmt->execute();
    $stmt->close();
}

echo "Default branches inserted.\n";

// Insert default admin user
echo "Inserting default admin user...\n";
$admin_password = password_hash('admin123', PASSWORD_DEFAULT); // Default password
$stmt = $conn->prepare("INSERT IGNORE INTO users (username, password, role, full_name) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", 'admin', $admin_password, 'admin', 'System Administrator');
$stmt->execute();
$stmt->close();

echo "Default admin user created (username: admin, password: admin123).\n";

echo "Database setup completed successfully!\n";
echo "You can now access your database through phpMyAdmin at: http://localhost/phpmyadmin\n";

$conn->close();
?>