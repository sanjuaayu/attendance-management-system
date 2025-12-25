<?php
require_once 'config.php';

// Array of HR users to create
$hr_users = [
   // [
        //'username' => 'Anuradha_hrA40',
         //'password' => 'hr123',
        // 'full_name' => 'Anuradha - HR Manager',
        // 'branch' => 'Prince Gupta A40',
        // 'branch_id' => 2,
       //  'employee_code' => 'HR001'
   // ],
    // [
         //'username' => 'Ragini_hrB78',
        // 'password' => 'hr123',
       //  'full_name' => 'Ragini - HR Manager',
      //   'branch' => 'Prince Gupta B78',
      //   'branch_id' => 1,
       //  'employee_code' => 'HR002'
    // ]
     //  [
       //  'username' => 'Codexateam_hr',
      //   'password' => 'hr123',
      //   'full_name' => 'codexa - HR Manager',
      //   'branch' => 'Codexa Team',
      //   'branch_id' => 9,
      //   'employee_code' => 'HR003'
   //  ] 
   [
    'username' => 'neha_hr',
    'password' => 'hr123',
    'full_name' => 'Rohit= Hr Manager',
    'branch' => 'Rohit',
    'branch_id' => 4,
    'employee_code' => 'HR004'
   ]
];

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'>";
echo "<style>";
echo "body { font-family: Arial; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; }";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }";
echo ".success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }";
echo ".error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }";
echo "h1 { color: #667eea; }";
echo "table { width: 100%; border-collapse: collapse; margin-top: 20px; }";
echo "table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }";
echo "table th { background: #f8f9fa; font-weight: bold; }";
echo "</style></head><body>";
echo "<div class='container'>";
echo "<h1>🏢 Add HR Users</h1>";

$created = 0;
$failed = 0;

foreach($hr_users as $hr) {
    // Hash password using bcrypt
    $hashed_password = password_hash($hr['password'], PASSWORD_DEFAULT);
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, branch, branch_id, employee_code, created_at) VALUES (?, ?, 'hr', ?, ?, ?, ?, NOW())");
    
    if(!$stmt) {
        echo "<div class='error'>";
        echo "<strong>❌ Error:</strong> " . $conn->error;
        echo "</div>";
        $failed++;
        continue;
    }
    
    $stmt->bind_param("ssssii", 
        $hr['username'],
        $hashed_password,
        $hr['full_name'],
        $hr['branch'],
        $hr['branch_id'],
        $hr['employee_code']
    );
    
    if($stmt->execute()) {
        echo "<div class='success'>";
        echo "<strong>✅ User Created Successfully!</strong><br>";
        echo "Username: <strong>" . $hr['username'] . "</strong><br>";
        echo "Password: <strong>" . $hr['password'] . "</strong><br>";
        echo "Branch: <strong>" . $hr['branch'] . "</strong><br>";
        echo "Full Name: <strong>" . $hr['full_name'] . "</strong>";
        echo "</div>";
        $created++;
    } else {
        echo "<div class='error'>";
        echo "<strong>❌ Error Creating User: " . $hr['username'] . "</strong><br>";
        echo $stmt->error;
        echo "</div>";
        $failed++;
    }
    
    $stmt->close();
}

echo "<hr style='margin: 20px 0;'>";
echo "<h3>📊 Summary</h3>";
echo "<table>";
echo "<tr><th>Status</th><th>Count</th></tr>";
echo "<tr><td>✅ Created</td><td><strong>" . $created . "</strong></td></tr>";
echo "<tr><td>❌ Failed</td><td><strong>" . $failed . "</strong></td></tr>";
echo "</table>";

echo "<br><br>";
echo "<a href='hrsection-login.php' style='display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to HR Login</a>";
echo "</div>";
echo "</body></html>";

$conn->close();
?>
