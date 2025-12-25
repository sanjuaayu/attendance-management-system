<?php
// Test database connection and tables
require_once 'config.php';

echo "<h2>Database Connection Test</h2>";

// Test connection
if ($conn) {
    echo "<p>✓ Successfully connected to database: " . DB_NAME . "</p>";
} else {
    echo "<p>✗ Failed to connect to database</p>";
    exit;
}

// Test users table
echo "<h3>Testing 'users' table:</h3>";
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result && $result->num_rows > 0) {
    echo "<p>✓ 'users' table exists</p>";
    
    // Count users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>✓ Found " . $row['count'] . " user(s) in the table</p>";
    }
} else {
    echo "<p>✗ 'users' table does not exist</p>";
}

// Test attendance table
echo "<h3>Testing 'attendance' table:</h3>";
$result = $conn->query("SHOW TABLES LIKE 'attendance'");
if ($result && $result->num_rows > 0) {
    echo "<p>✓ 'attendance' table exists</p>";
    
    // Count attendance records
    $result = $conn->query("SELECT COUNT(*) as count FROM attendance");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>✓ Found " . $row['count'] . " attendance record(s) in the table</p>";
    }
} else {
    echo "<p>✗ 'attendance' table does not exist</p>";
}

// Test branches table
echo "<h3>Testing 'branches' table:</h3>";
$result = $conn->query("SHOW TABLES LIKE 'branches'");
if ($result && $result->num_rows > 0) {
    echo "<p>✓ 'branches' table exists</p>";
    
    // List branches
    $result = $conn->query("SELECT name FROM branches ORDER BY name");
    if ($result && $result->num_rows > 0) {
        echo "<p>✓ Branches in the table:</p><ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li>" . $row['name'] . "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p>✗ 'branches' table does not exist</p>";
}

$conn->close();

echo "<h3>Database test completed!</h3>";
echo "<p>You can now proceed with integrating the database with your application.</p>";
?>