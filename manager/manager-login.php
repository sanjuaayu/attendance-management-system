<?php
session_start();
if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'manager') {
    header('Location: manager-dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Login</title>
    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { 
        font-family: Arial; 
        /* Changed: Orange → Gradient Blue/Green like HR */
        background: linear-gradient(to right, #22c1c3, #4facfe);
        min-height: 100vh; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        padding: 20px; 
    }
    .container { 
        background: white; 
        border-radius: 15px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.2); 
        width: 100%; 
        max-width: 400px; 
        overflow: hidden; 
    }
    .header { 
        /* Changed: Orange → Blue gradient */
        background: linear-gradient(to right, #22c1c3, #4facfe);
        padding: 30px; 
        text-align: center; 
        color: white; 
    }
    .header h1 { font-size: 24px; margin-bottom: 5px; }
    .body { padding: 30px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { 
        display: block; 
        color: #374151; 
        font-weight: 600; 
        margin-bottom: 8px; 
        font-size: 14px; 
    }
    .form-group input { 
        width: 100%; 
        padding: 12px; 
        border: 2px solid #e5e7eb; 
        border-radius: 8px; 
        font-size: 15px; 
    }
    .form-group input:focus { 
        outline: none; 
        /* Changed: Orange → Blue */
        border-color: #4facfe; 
    }
    .btn { 
        width: 100%; 
        padding: 14px; 
        /* Changed: Orange → Blue gradient */
        background: linear-gradient(to right, #22c1c3, #4facfe);
        color: white; 
        border: none; 
        border-radius: 8px; 
        font-size: 16px; 
        font-weight: 600; 
        cursor: pointer; 
    }
    .btn:hover { transform: translateY(-2px); }
    .error { 
        background: #fee2e2; 
        border: 1px solid #ef4444; 
        color: #991b1b; 
        padding: 12px; 
        border-radius: 8px; 
        margin-bottom: 20px; 
        font-size: 14px; 
    }
</style>

</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👨‍💼 Manager Login</h1>
            <p>Leave Management System</p>
        </div>
        <div class="body">
            <?php if(isset($_GET['error'])): ?>
                <div class="error">❌ Invalid username or password!</div>
            <?php endif; ?>
            
            <form action="manager_login_process.php" method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
