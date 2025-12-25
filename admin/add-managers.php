<?php
require_once 'config.php';

// Array of Manager users - ONE MANAGER PER BRANCH
$manager_users = [
    [
        'username' => 'princeguptab78',
        'password' => 'admin@123',
        'full_name' => 'Prince Gupta B78',
        'branch' => 'Prince Gupta B78',
        'branch_id' => 1,
        'employee_code' => 'MGRB78'
    ],
    [
        'username' => 'sonaligupta',
        'password' => 'admin@123',
        'full_name' => 'Sonali Gupta',
        'branch' => 'Sonali Gupta',
        'branch_id' => 2,
        'employee_code' => 'MGRSG'
    ],
    [
        'username' => 'mukesh',
        'password' => 'admin@123',
        'full_name' => 'Mukesh',
        'branch' => 'Mukesh',
        'branch_id' => 3,
        'employee_code' => 'MGRM'
    ],
    [
        'username' => 'rohit',
        'password' => 'admin@123',
        'full_name' => 'Rohit',
        'branch' => 'Rohit',
        'branch_id' => 4,
        'employee_code' => 'MGRR'
    ],
    [
        'username' => 'abhishek',
        'password' => 'admin@123',
        'full_name' => 'Abhishek',
        'branch' => 'Abhishek',
        'branch_id' => 5,
        'employee_code' => 'MGRA'
    ],
    [
        'username' => 'rohittandand',
        'password' => 'admin@123',
        'full_name' => 'Rohit Tandand',
        'branch' => 'Rohit Tandand',
        'branch_id' => 6,
        'employee_code' => 'MGRRT'
    ],
    [
        'username' => 'asfaq',
        'password' => 'admin@123',
        'full_name' => 'Asfaq',
        'branch' => 'Asfaq',
        'branch_id' => 7,
        'employee_code' => 'MGRASF'
    ],
    [
        'username' => 'backendteam',
        'password' => 'admin@123',
        'full_name' => 'Backend Team',
        'branch' => 'Backend Team',
        'branch_id' => 8,
        'employee_code' => 'MGRBT'
    ],
    [
        'username' => 'codexateam',
        'password' => 'admin@123',
        'full_name' => 'Codexa Team',
        'branch' => 'Codexa Team',
        'branch_id' => 9,
        'employee_code' => 'MGRCT'
    ],
    [
        'username' => 'princeguptaa40',
        'password' => 'admin@123',
        'full_name' => 'Prince Gupta A40',
        'branch' => 'Prince Gupta A40',
        'branch_id' => 10,
        'employee_code' => 'MGRA40'
    ],
];

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 20px; margin: 0; }";
echo ".container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }";
echo "h1 { color: #f59e0b; text-align: center; margin-bottom: 10px; font-size: 32px; }";
echo ".subtitle { text-align: center; color: #6b7280; margin-bottom: 30px; }";
echo ".success { background: #d1fae5; border-left: 5px solid #10b981; color: #065f46; padding: 15px; margin: 10px 0; border-radius: 8px; }";
echo ".error { background: #fee2e2; border-left: 5px solid #ef4444; color: #991b1b; padding: 15px; margin: 10px 0; border-radius: 8px; }";
echo ".info-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 10px; margin-top: 10px; }";
echo ".info-label { color: #6b7280; font-weight: bold; }";
echo ".info-value { color: #1f2937; font-family: 'Courier New', monospace; background: #f3f4f6; padding: 4px 8px; border-radius: 4px; }";
echo "table { width: 100%; border-collapse: collapse; margin-top: 30px; background: white; }";
echo "table th { background: #f59e0b; color: white; padding: 15px; text-align: left; font-weight: bold; }";
echo "table td { padding: 15px; border-bottom: 1px solid #e5e7eb; }";
echo "table tr:hover { background: #fffbeb; }";
echo ".btn { display: inline-block; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 10px; margin-top: 20px; font-weight: bold; font-size: 16px; }";
echo ".btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3); }";
echo ".count-badge { background: white; color: #f59e0b; padding: 8px 16px; border-radius: 20px; font-weight: bold; font-size: 18px; }";
echo "</style></head><body>";
echo "<div class='container'>";
echo "<h1>👨‍💼 Create Manager Accounts</h1>";
echo "<p class='subtitle'>One manager per branch - Simple & Easy</p>";

$created = 0;
$failed = 0;

foreach($manager_users as $manager) {
    // Hash password
    $hashed_password = password_hash($manager['password'], PASSWORD_DEFAULT);
    
    // Prepare statement
    $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, branch, branch_id, employee_code, created_at) VALUES (?, ?, 'manager', ?, ?, ?, ?, NOW())");
    
    if(!$stmt) {
        echo "<div class='error'>";
        echo "<strong>❌ Database Error:</strong> " . $conn->error;
        echo "</div>";
        $failed++;
        continue;
    }
    
    $stmt->bind_param("ssssis", 
        $manager['username'],
        $hashed_password,
        $manager['full_name'],
        $manager['branch'],
        $manager['branch_id'],
        $manager['employee_code']
    );
    
    if($stmt->execute()) {
        echo "<div class='success'>";
        echo "<strong>✅ Manager Created Successfully!</strong>";
        echo "<div class='info-grid'>";
        echo "<div class='info-label'>👤 Username:</div><div class='info-value'>" . $manager['username'] . "</div>";
        echo "<div class='info-label'>🔑 Password:</div><div class='info-value'>" . $manager['password'] . "</div>";
        echo "<div class='info-label'>📛 Full Name:</div><div class='info-value'>" . $manager['full_name'] . "</div>";
        echo "<div class='info-label'>🏢 Branch:</div><div class='info-value'>" . $manager['branch'] . "</div>";
        echo "<div class='info-label'>🆔 Code:</div><div class='info-value'>" . $manager['employee_code'] . "</div>";
        echo "</div></div>";
        $created++;
    } else {
        echo "<div class='error'>";
        echo "<strong>❌ Failed to create: " . $manager['username'] . "</strong><br>";
        echo "Error: " . $stmt->error;
        echo "</div>";
        $failed++;
    }
    
    $stmt->close();
}

echo "<hr style='margin: 30px 0; border: none; border-top: 2px solid #e5e7eb;'>";
echo "<h2 style='color: #1f2937; text-align: center;'>📊 Summary Report</h2>";
echo "<table>";
echo "<tr><th>Status</th><th>Count</th></tr>";
echo "<tr><td>✅ Successfully Created</td><td><span class='count-badge' style='background: #d1fae5; color: #10b981;'>" . $created . "</span></td></tr>";
echo "<tr><td>❌ Failed</td><td><span class='count-badge' style='background: #fee2e2; color: #ef4444;'>" . $failed . "</span></td></tr>";
echo "<tr style='background: #f3f4f6;'><td><strong>📌 Total Processed</strong></td><td><strong>" . ($created + $failed) . "</strong></td></tr>";
echo "</table>";

if($created > 0) {
    echo "<div style='text-align: center; margin-top: 30px;'>";
    echo "<a href='manager-login.php' class='btn'>🚀 Go to Manager Login</a>";
    echo "<br><br>";
    echo "<p style='color: #6b7280; font-size: 14px;'>⚠️ Remember to delete this file after use for security!</p>";
    echo "</div>";
}

echo "</div>";
echo "</body></html>";

$conn->close();
?>
