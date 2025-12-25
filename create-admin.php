<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php'; // DB connection

$users = [
    [
        'username'   => 'admin1',
        'password'   => 'Admin@2025!',
        'role'       => 'admin',
        'full_name'  => 'Admin One',
        'branch'     => 'HQ'
    ],
    [
        'username'   => 'parentA40',
        'password'   => 'Parent@a40',
        'role'       => 'parent_admin',
        'full_name'  => 'Parent Admin One',
        'branch'     => ''
    ],
    [
        'username'   => 'parentB78',
        'password'   => 'Parent@b78',
        'role'       => 'parent_admin',
        'full_name'  => 'Parent Admin Two',
        'branch'     => ''
    ],
      [
        'username'   => 'downloadreport',
        'password'   => 'report',
        'role'       => 'parent_admin',
        'full_name'  => 'Parent Admin Three',
        'branch'     => ''
    ],

   
    
];

foreach ($users as $user) {
    // Check if username already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->bind_param("s", $user['username']);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows === 0) {
        // Safe to insert
        $hashedPassword = password_hash($user['password'], PASSWORD_BCRYPT);

        $stmt = $conn->prepare("
            INSERT INTO users (username, password, role, full_name, branch)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssss",
            $user['username'],
            $hashedPassword,
            $user['role'],
            $user['full_name'],
            $user['branch']
        );

        if ($stmt->execute()) {
            echo "✅ User '{$user['username']}' ({$user['role']}) created successfully.<br>";
        } else {
            echo "❌ Error creating '{$user['username']}': " . $stmt->error . "<br>";
        }

        $stmt->close();
    } else {
        echo "⚠️ User '{$user['username']}' already exists. Skipping insert.<br>";
    }

    $checkStmt->close();
}

$conn->close();
?>