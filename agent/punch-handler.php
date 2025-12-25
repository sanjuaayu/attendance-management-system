<?php
// punch-handler.php

require __DIR__ . '/config.php'; // ✅ Safe include

// Get POST data
$action = $_POST['action']; // 'punchin' or 'punchout'
$userId = $_POST['user_id'];
$location = $_POST['location'];
$timestamp = date('Y-m-d H:i:s');

// Save to DB
$stmt = $conn->prepare("INSERT INTO attendance (user_id, action, location, timestamp) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $userId, $action, $location, $timestamp);
$stmt->execute();

// ✅ Redirect to success page (correct folder name)
header("Location: /attendence_project/success.php?action=$action");
exit;
?>
