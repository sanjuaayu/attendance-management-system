<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Login - Employee Attendance System</title>
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #007bff 0%, #28a745 50%, #17a2b8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .container {
            width: 90%;
            max-width: 400px;
            padding: 20px;
        }
        .login-box {
            background: #ffffff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            text-align: center;
            animation: fadeIn 0.6s ease-in-out;
        }
        h2 {
            margin-bottom: 24px;
            color: #334e68;
            font-weight: 700;
        }
        .input-group {
            position: relative;
            text-align: left;
            margin-bottom: 20px;
        }
        .input-group label {
            display: block;
            font-size: 14px;
            color: #586e8b;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .input-group input {
            width: 100%;
            padding: 14px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .input-group input:focus {
            border-color: #55c1b3;
            box-shadow: 0 0 0 3px rgba(85, 193, 179, 0.2);
            outline: none;
        }
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(20%);
            cursor: pointer;
            color: #999;
            font-size: 1.2em;
        }
        .login-btn {
            width: 100%;
            padding: 14px;
            margin-top: 10px;
            background: #55c1b3;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            letter-spacing: 0.5px;
        }
        .login-btn:hover {
            background-color: #49a597;
            transform: translateY(-2px);
        }
        .links {
            margin-top: 25px;
            font-size: 14px;
            color: #7f8c8d;
        }
        .links a {
            color: #55c1b3;
            text-decoration: none;
            font-weight: 600;
        }
        .links a:hover {
            text-decoration: underline;
        }
        .logo-box {
            margin-top: 30px;
        }
        .logo-box img {
            max-width: 160px;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        .error-message {
            background: #ffe3e3;
            color: #c0392b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.98) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h2>Agent Login <img src="hd.png" height="60" width="50"/></h2>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="error-message">❌ Invalid username or password!</div>
            <?php endif; ?>
            
            <form id="loginForm" action="login_process.php" method="POST">
                <!-- ============ FIX: Changed 'agent' to 'user' ============ -->
                <input type="hidden" name="role" value="user">
                
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <span id="togglePassword" class="password-toggle">👁️</span>
                </div>
                <button type="submit" class="login-btn">Login</button>
            </form>
            <div class="links">
                <p>Hr Login <a href="hrsection-login.php">Login here</a></p>
                <p>Manager <a href="manager-login.php">Login here</a></p>
                <p>Download Attendance <a href="parent-login.php">Login here</a></p>
            </div>
            <div class="logo-box">
                <img src="rupeeq.png" alt="Company Logo">
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.textContent = (type === 'password') ? '👁️' : '🔒';
        });
    </script>
</body>
</html>
