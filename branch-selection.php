<?php
// Start the session at the very top of the script
session_start();

// Check if the user is authenticated. 
// If not, redirect them back to the login page and stop execution.
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// User is authenticated, so proceed to fetch their details.
$userId = $_SESSION['user_id'];

// Database connection details
$host = 'localhost';
$db   = 'rupeeqat_employee_attendance';
$user = 'rupeeqat_employee_attendance';
$pass = '4yknR4hLtwCKRjSHbdM5';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$errorMessage = '';
$successMessage = '';
$assignedBranch = null;
$fullName = "Guest";

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Fetch user details and their assigned branch
    $stmt = $pdo->prepare("SELECT full_name, assigned_branch FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch();

    if ($userData) {
        $fullName = htmlspecialchars($userData['full_name']);
        $assignedBranch = $userData['assigned_branch'];
    }
    
    // If no assigned branch found, show error
    if (!$assignedBranch) {
        $errorMessage = "No branch assigned to your account. Please contact admin.";
    }
    
    // Handle branch selection form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['branch'])) {
        $selectedBranch = $_POST['branch'];
        
        // Check if selected branch matches assigned branch
        if ($selectedBranch !== $assignedBranch) {
            $errorMessage = "Please choose your correct assigned branch: " . htmlspecialchars($assignedBranch);
        } else {
            // Get branch_id from branches table based on selected branch name
            $branchStmt = $pdo->prepare("SELECT id FROM branches WHERE name = ?");
            $branchStmt->execute([$selectedBranch]);
            $branch = $branchStmt->fetch();
            
            if ($branch) {
                $branchId = $branch['id'];
                
                // Update user's current branch in the users table
                $updateStmt = $pdo->prepare("UPDATE users SET branch_id = ?, branch = ? WHERE id = ?");
                $updateResult = $updateStmt->execute([$branchId, $selectedBranch, $userId]);
                
                if ($updateResult) {
                    // Store branch info in session
                    $_SESSION['branch'] = $selectedBranch;
                    $_SESSION['branch_id'] = $branchId;
                    
                    // Redirect to dashboard or next page
                    header("Location: punch-attendance.php");
                    exit();
                } else {
                    $errorMessage = "Failed to update branch selection. Please try again.";
                }
            } else {
                $errorMessage = "Invalid branch selected. Please choose a valid branch.";
            }
        }
    }
    
} catch (\PDOException $e) {
    $fullName = "Error"; 
    $errorMessage = "Database connection error. Please try again later.";
    error_log($e->getMessage()); 
}

// Available branches (all branches for display)
$availableBranches = [
    'Prince Gupta B78', 'Sonali Gupta', 'Mukesh', 'Rohit', 'Abhishek', 
    'Rohit Tandand', 'Asfaq', 'Backend Team', 'Codexa Team', 'Prince Gupta A40'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Select Branch - Employee Attendance System</title>
   <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            background: linear-gradient(135deg, #3f4fd8ff 0%, #32670cff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dashboard-container {
            max-width: 900px;
            width: 90%;
            margin: 30px auto;
            background: rgba(255,255,255,0.13);
            border-radius: 18px;
            box-shadow: 0 8px 40px rgba(80,80,99,0.10);
            padding: 35px 40px 30px 40px;
        }
        .header {
            display: flex;
            align-items: center;
            gap: 30px;
            padding-bottom: 12px;
            flex-wrap: wrap;
        }
        .company-logo {
            height: 72px;
            width: auto;
            background: none;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(66, 133, 244, 0.10);
            object-fit: contain;
        }
        .header h1 {
            color: #222e50;
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: 1px;
        }
        .welcome-message {
            background: rgba(255,255,255,0.80);
            padding: 24px 28px 18px 28px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(60,60,100,0.08);
            margin-bottom: 32px;
        }
        .welcome-message h2 {
            color: #19336a;
            font-size: 1.35rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .welcome-message p {
            color: #3d6cb9;
            font-size: 1rem;
            margin-bottom: 0;
        }
        .assigned-branch-info {
            background: rgba(40, 167, 69, 0.1);
            border: 2px solid #28a745;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .assigned-branch-info h3 {
            color: #155724;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        .assigned-branch-info p {
            color: #155724;
            font-weight: bold;
            font-size: 1.1rem;
            margin: 0;
        }
        .error-message {
            background: #e04949;
            color: #fff !important;
            border-radius: 7px;
            padding: 12px;
            margin-bottom: 22px;
            font-size: 1rem;
            text-align: center;
            box-shadow: 0 1px 5px rgba(90,0,0,0.15);
            display: none;
        }
        .error-message.show {
            display: block;
        }
        .success-message {
            background: #28a745;
            color: #fff !important;
            border-radius: 7px;
            padding: 12px;
            margin-bottom: 22px;
            font-size: 1rem;
            text-align: center;
            box-shadow: 0 1px 5px rgba(0,90,0,0.15);
        }
        .branch-selection {
            width: 100%;
        }
        .branch-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: center;
            margin-top: 12px;
        }
        .branch-btn {
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, #f3f3f6ff 68%, #f427dfff 100%);
            border-radius: 50%;
            border: none;
            box-shadow: 0 6px 32px rgba(81,81,120,0.15);
            font-weight: 500;
            color: #2542ad;
            font-size: 1.09rem;
            transition: all 0.15s;
            cursor: pointer;
            outline: none;
            display: flex;
            align-items: center;
            justify-content: center;  
            text-align: center;
            padding: 10px;
            position: relative;
        }
        .branch-btn:hover,
        .branch-btn:focus {
            background: linear-gradient(135deg, #b8e994 50%, #fff 100%);
            color: #1b2a54;
            transform: translateY(-5px) scale(1.04);
            box-shadow: 0 10px 36px rgba(51,51,110,0.21);
            font-weight: bold;
        }
        .branch-btn.assigned {
            background: linear-gradient(135deg, #28a745 50%, #20c997 100%);
            color: white;
            font-weight: bold;
            border: 3px solid #fff;
        }
        .branch-btn.assigned:hover {
            background: linear-gradient(135deg, #20c997 50%, #28a745 100%);
            color: white;
        }
        .branch-btn.disabled {
            background: linear-gradient(135deg, #6c757d 50%, #adb5bd 100%);
            color: #fff;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .branch-btn.disabled:hover {
            transform: none;
            box-shadow: 0 6px 32px rgba(81,81,120,0.15);
        }
        .assigned-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        @media (max-width:900px){
            .branch-btn { width:110px; height:110px; font-size: 0.97rem;}
            .dashboard-container { padding: 6vw 2vw;}
            .header h1 { font-size: 1.25rem;}
        }
        @media (max-width:600px){
            .header { gap:12px;}
            .company-logo { height: 44px;}
            .branch-buttons { gap:16px; }
            .branch-btn { width: 80px; height: 80px; font-size:0.82rem;}
            .welcome-message { padding: 10px;}
            .assigned-badge { width: 16px; height: 16px; font-size: 10px; }
        }
   
    </style>
</head>

<body>
    <div class="dashboard-container">
        <div class="header">
            <img src="rupeeq.png" alt="Company Logo" class="company-logo">
            <h1>Select Your Branch/Cluster Manager</h1>
        </div>
         <div style="text-align: right; margin-bottom: 20px;">
        <a href="logout.php" 
           style="
             display: inline-block; 
             padding: 10px 20px; 
             background-color: #e04949; 
             color: white; 
             font-weight: bold; 
             border-radius: 6px; 
             text-decoration: none;
             transition: background-color 0.3s ease;
           "
           onmouseover="this.style.backgroundColor='#b03a3a';"
           onmouseout="this.style.backgroundColor='#e04949';"
        >
            Logout
        </a>
    </div>

        <div class="welcome-message">
            <h2>Welcome, <?php echo $fullName; ?>!</h2> 
            <p>Please select your assigned branch from the options below:</p>
        </div>

        <?php if ($assignedBranch): ?>
        <div class="assigned-branch-info">
            <h3>Your Assigned Branch</h3>
            <p><?php echo htmlspecialchars($assignedBranch); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="error-message show">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="branchForm">
            <input type="hidden" id="selected_branch" name="branch" value="">

            <div class="branch-selection">
                <div class="branch-buttons">
                    <?php foreach ($availableBranches as $branch): ?>
                        <?php 
                        $isAssigned = ($branch === $assignedBranch);
                        $isDisabled = !$isAssigned && $assignedBranch;
                        ?>
                        <button type="button" 
                                class="branch-btn<?php echo $isAssigned ? ' assigned' : ''; ?><?php echo $isDisabled ? ' disabled' : ''; ?>" 
                                onclick="<?php echo $isDisabled ? 'showError()' : "selectBranch('" . htmlspecialchars($branch, ENT_QUOTES) . "')"; ?>"
                                <?php echo $isDisabled ? 'disabled' : ''; ?>>
                            <?php echo htmlspecialchars($branch); ?>
                            <?php if ($isAssigned): ?>
                                <span class="assigned-badge">✓</span>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </form>

        <script>
            function selectBranch(branchName) {
                // Set the branch value
                document.getElementById('selected_branch').value = branchName;
                
                // Hide error message if shown
                const errorMsg = document.querySelector('.error-message');
                if (errorMsg) {
                    errorMsg.classList.remove('show');
                }
                
                // Submit the form
                document.getElementById('branchForm').submit();
            }
            
            function showError() {
                const errorMsg = document.querySelector('.error-message');
                if (errorMsg) {
                    errorMsg.innerHTML = 'Please choose your correct assigned branch: <?php echo htmlspecialchars($assignedBranch ?? ''); ?>';
                    errorMsg.classList.add('show');
                }
                
                // Shake animation for visual feedback
                const assignedInfo = document.querySelector('.assigned-branch-info');
                if (assignedInfo) {
                    assignedInfo.style.animation = 'shake 0.5s';
                    setTimeout(() => {
                        assignedInfo.style.animation = '';
                    }, 500);
                }
            }
            
            // Add shake animation CSS
            const style = document.createElement('style');
            style.textContent = `
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                    20%, 40%, 60%, 80% { transform: translateX(5px); }
                }
            `;
            document.head.appendChild(style);
        </script>
    </div>
</body>
</html>