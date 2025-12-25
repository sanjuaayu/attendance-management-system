<?php
require_once 'config.php'; // DB connection

function createUser($username, $plainPassword, $role, $fullName, $branch) {
    global $conn;

    // 🔒 Sanitize inputs
    $username = trim($username);
    $fullName = trim($fullName);
    $branch = strtoupper(trim($branch));

    // ✅ Validate role
    $validRoles = ['admin', 'agent', 'parent_admin'];
    if (!in_array($role, $validRoles)) {
        echo "❌ Invalid role '$role'. Allowed roles: " . implode(', ', $validRoles) . "<br>";
        return;
    }

    // 🔍 Check if username already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        echo "⚠️ Username '$username' already exists.<br>";
        $checkStmt->close();
        return;
    }
    $checkStmt->close();

    // 🔐 Hash the password securely
    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

    // 🚀 Insert new user
    $stmt = $conn->prepare("
        INSERT INTO users (username, password, role, full_name, branch)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssss", $username, $hashedPassword, $role, $fullName, $branch);

    if ($stmt->execute()) {
        echo "✅ User '$username' created successfully.<br>";
    } else {
        echo "❌ Error creating user '$username': " . $stmt->error . "<br>";
    }

    $stmt->close();
}

// 🔧 Example usage
createUser('agent1', 'agent123', 'agent', 'Agent One', 'A40');
createUser('admin1', 'Admin@2025!', 'admin', 'Admin One', 'HQ');
createUser('parent1', 'Parent@2025!', 'parent_admin', 'Parent Admin', 'B78');

$conn->close();
?>
