<?php
// Response ko JSON format mein set karein
header('Content-Type: application/json');
require_once 'config.php';

// Admin panel se bheji gayi branch ID prapt karein
$branchId = $_POST['branch_id'] ?? 0;

if (!$branchId) {
    echo json_encode([]); // Agar branch ID nahi hai to khali response bhejein
    exit;
}

// NOTE: Hum maan rahe hain ki aapke 'users' table mein branch ko link karne wala column 'branch_id' hai.
// Agar iska naam kuch aur hai, to neeche query mein use badal dein.
$sql = "SELECT id, username FROM users WHERE branch_id = ? ORDER BY username ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("SQL Prepare Error: " . $conn->error);
    echo json_encode(['error' => 'Database query mein samasya hai.']);
    exit;
}

$stmt->bind_param("i", $branchId);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$stmt->close();
$conn->close();

// Users ki list ko JSON format mein return karein
echo json_encode($users);
?>