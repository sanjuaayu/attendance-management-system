<?php
require_once 'config.php';

// Missing branches ko add karenge
$missing_branches = [
    'Prince Gupta B78',
    'Sonali Gupta', 
    'Mukesh'
];

foreach($missing_branches as $branch) {
    // Check if already exists
    $stmt = $conn->prepare("SELECT id FROM branches WHERE full_name = ?");
    $stmt->bind_param("s", $branch);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Insert new branch
        $insert_stmt = $conn->prepare("INSERT INTO branches (full_name, created_at) VALUES (?, NOW())");
        $insert_stmt->bind_param("s", $branch);
        
        if ($insert_stmt->execute()) {
            echo "Added branch: $branch<br>";
        } else {
            echo "Error adding branch $branch: " . $conn->error . "<br>";
        }
        $insert_stmt->close();
    } else {
        echo "Branch already exists: $branch<br>";
    }
    $stmt->close();
}

echo "<br><h3>Updated Branches List:</h3>";
$result = $conn->query("SELECT id, full_name FROM branches ORDER BY full_name");
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']} - Name: {$row['full_name']}<br>";
}

$conn->close();
echo "<br><strong>Done! Now test your branch selection page.</strong>";
?>