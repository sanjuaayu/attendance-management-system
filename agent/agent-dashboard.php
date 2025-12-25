<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard - Employee Attendance System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <img src="rupeeq.png" alt="Company Logo" class="company-logo">
            <h1>Agent Dashboard</h1>
            <button class="logout-btn" id="logoutBtn">Logout</button>
        </div>
        
        <div class="welcome-message">
            <h2>Welcome, <span id="agentName">Agent</span>!</h2>
            <p>Today is <span id="currentDate"></span></p>
        </div>
        
        <div class="status-box">
            <h2>Attendance Status</h2>
            <p id="statusMessage">You are currently logged out</p>
            <button class="punch-btn punch-in-btn" id="punchInBtn">Punch In</button>
            <button class="punch-btn punch-out-btn" id="punchOutBtn" disabled>Punch Out</button>
        </div>
        
        <div class="attendance-history">
            <h2>Attendance History</h2>
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Punch In</th>
                        <th>Punch Out</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody id="attendanceTableBody">
                    <!-- Attendance records will be populated here by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
