<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$employee_name = $_SESSION['full_name'];
$employee_branch = $_SESSION['branch'];
$employee_code = $_SESSION['employee_code'] ?? 'N/A';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $from_date = mysqli_real_escape_string($conn, $_POST['from_date']);
    $to_date = mysqli_real_escape_string($conn, $_POST['to_date']);
    $leave_type = mysqli_real_escape_string($conn, $_POST['leave_type']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    
    // Validate dates
    if(strtotime($from_date) > strtotime($to_date)) {
        echo json_encode(['success' => false, 'message' => 'From date cannot be greater than to date']);
        exit();
    }
    
    // Calculate days
    $days = (strtotime($to_date) - strtotime($from_date)) / (60 * 60 * 24) + 1;
    
    // Insert leave request
    $query = "INSERT INTO leave_requests (employee_id, employee_name, employee_code, branch, from_date, to_date, leave_type, reason, days_count, status, submitted_date) 
              VALUES ('$user_id', '$employee_name', '$employee_code', '$employee_branch', '$from_date', '$to_date', '$leave_type', '$reason', '$days', 'Pending', NOW())";
    
    if(mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Leave request submitted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
    }
    exit();
}

// Fetch only 3 leave types
$leave_types_query = "SELECT * FROM leave_types WHERE is_active = 1 ORDER BY leave_type ASC";
$leave_types_result = mysqli_query($conn, $leave_types_query);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, minimum-scale=0.5, maximum-scale=2.0, user-scalable=yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Apply for Leave</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-user-select: text;
            user-select: text;
        }

        html, body {
            height: 100%;
            width: 100%;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            padding-bottom: max(10px, env(safe-area-inset-bottom));
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            animation: slideUp 0.4s ease;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        .form-header {
            background: linear-gradient(135deg, #34d399 0%, #3b82f6 100%);
            padding: 20px 16px;
            color: white;
            text-align: center;
            flex-shrink: 0;
        }

        .form-header h1 {
            font-size: 24px;
            margin-bottom: 4px;
            font-weight: 700;
            -webkit-text-size-adjust: 100%;
        }

        .form-header p {
            font-size: 13px;
            opacity: 0.9;
        }

        /* Header Buttons */
        .header-buttons {
            display: flex;
            gap: 10px;
            padding: 12px 16px;
            background: #f3f4f6;
            border-bottom: 1px solid #e5e7eb;
        }

        .btn-header {
            flex: 1;
            padding: 10px 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
            -webkit-user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .btn-back {
            background: #e0e7ff;
            color: #3b82f6;
        }

        .btn-back:active {
            background: #c7d2fe;
            transform: scale(0.98);
        }

        .btn-logout {
            background: #fee2e2;
            color: #ef4444;
        }

        .btn-logout:active {
            background: #fecaca;
            transform: scale(0.98);
        }

        .form-body {
            padding: 20px 16px;
            overflow-y: auto;
            flex: 1;
            -webkit-overflow-scrolling: touch;
        }

        .employee-info {
            background: #f3f4f6;
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #3b82f6;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 13px;
            line-height: 1.4;
            -webkit-text-size-adjust: 100%;
        }

        .info-label {
            color: #6b7280;
            font-weight: 600;
            flex: 1;
        }

        .info-value {
            color: #1f2937;
            font-weight: 500;
            text-align: right;
            flex: 1;
            word-break: break-word;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            color: #374151;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
            -webkit-text-size-adjust: 100%;
        }

        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            font-family: inherit;
            transition: all 0.2s ease;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-clip: padding-box;
            -webkit-text-size-adjust: 100%;
        }

        .form-group select {
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 20px;
            padding-right: 40px;
            background-color: white;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .date-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .date-group .form-group {
            margin-bottom: 0;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
            font-size: 15px;
        }

        .days-display {
            background: linear-gradient(135deg, #dbeafe 0%, #cffafe 100%);
            border: 2px solid #3b82f6;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            text-align: center;
            font-weight: bold;
            color: #1e40af;
            display: none;
            font-size: 15px;
        }

        .days-display.show {
            display: block;
            animation: slideDown 0.3s ease;
        }

        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-shrink: 0;
        }

        .btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 15px;
            min-height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
            -webkit-user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn-submit {
            background: linear-gradient(135deg, #34d399 0%, #3b82f6 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-submit:active {
            box-shadow: 0 2px 6px rgba(59, 130, 246, 0.2);
        }

        .btn-cancel {
            background: #e5e7eb;
            color: #1f2937;
        }

        .btn-cancel:active {
            background: #d1d5db;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .success-message {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 2px solid #6ee7b7;
            color: #065f46;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 16px;
            display: none;
            animation: slideDown 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }

        .success-message.show {
            display: block;
        }

        .error-message {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 2px solid #f87171;
            color: #991b1b;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 16px;
            display: none;
            animation: slideDown 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }

        .error-message.show {
            display: block;
        }

        .required {
            color: #ef4444;
        }

        .form-footer {
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
            margin-top: 10px;
            padding-bottom: 10px;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Tablet & Desktop */
        @media (min-width: 768px) {
            .container {
                max-width: 600px;
            }

            .form-header {
                padding: 30px 24px;
            }

            .form-header h1 {
                font-size: 28px;
                margin-bottom: 8px;
            }

            .form-header p {
                font-size: 15px;
            }

            .form-body {
                padding: 30px 24px;
            }

            .header-buttons {
                padding: 16px 24px;
                gap: 12px;
            }

            .btn-header {
                font-size: 15px;
                min-height: 44px;
            }

            .employee-info {
                padding: 16px;
                margin-bottom: 24px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-group label {
                font-size: 15px;
            }

            .form-group select,
            .form-group input,
            .form-group textarea {
                padding: 12px 14px;
                font-size: 15px;
            }

            .btn {
                min-height: 44px;
                font-size: 16px;
                padding: 12px;
            }
        }

        @media (min-width: 1024px) {
            body {
                padding: 20px;
            }

            .container {
                max-width: 700px;
            }
        }

        @supports (padding: max(0px)) {
            body {
                padding-left: max(10px, env(safe-area-inset-left));
                padding-right: max(10px, env(safe-area-inset-right));
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h1>📋 Apply for Leave</h1>
            <p>Submit your leave request</p>
        </div>

        <!-- HEADER BUTTONS -->
        <div class="header-buttons">
            <button type="button" class="btn-header btn-back" onclick="goBackToAttendance()" title="Back to Punch Attendance">
                ← Back
            </button>
            <button type="button" class="btn-header btn-logout" onclick="logoutUser()" title="Logout">
                🚪 Logout
            </button>
        </div>

        <div class="form-body">
            <!-- Success Message -->
            <div class="success-message" id="successMessage">
                ✅ Leave request submitted successfully! Redirecting...
            </div>

            <!-- Error Message -->
            <div class="error-message" id="errorMessage"></div>

            <!-- Employee Info -->
            <div class="employee-info">
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($employee_name); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Code:</span>
                    <span class="info-value"><?php echo htmlspecialchars($employee_code); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Branch:</span>
                    <span class="info-value"><?php echo htmlspecialchars($employee_branch); ?></span>
                </div>
            </div>

            <!-- Leave Application Form -->
            <form id="leaveForm">
                <div class="form-group">
                    <label for="leave_type">Leave Type <span class="required">*</span></label>
                    <select id="leave_type" name="leave_type" required>
                        <option value="">-- Select Leave Type --</option>
                        <?php 
                        // Show only 3 leave types
                        while($leave = mysqli_fetch_assoc($leave_types_result)) {
                            echo '<option value="' . htmlspecialchars($leave['leave_type']) . '">' . htmlspecialchars($leave['leave_type']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="date-group">
                    <div class="form-group">
                        <label for="from_date">From Date <span class="required">*</span></label>
                        <input type="date" id="from_date" name="from_date" required>
                    </div>

                    <div class="form-group">
                        <label for="to_date">To Date <span class="required">*</span></label>
                        <input type="date" id="to_date" name="to_date" required>
                    </div>
                </div>

                <div class="days-display" id="daysDisplay">
                    📅 Total Days: <span id="daysCount">0</span>
                </div>

                <div class="form-group">
                    <label for="reason">Reason <span class="required">*</span></label>
                    <textarea id="reason" name="reason" placeholder="Enter reason for your leave..." required></textarea>
                </div>

                <div class="form-buttons">
                    <button type="button" class="btn btn-cancel" onclick="goBack()">Cancel</button>
                    <button type="submit" class="btn btn-submit" id="submitBtn">Submit Request</button>
                </div>

                <div class="form-footer">
                    All fields are required
                </div>
            </form>
        </div>
    </div>

    <script>
        // ============ Calculate Days ============
        document.getElementById('from_date').addEventListener('change', calculateDays);
        document.getElementById('to_date').addEventListener('change', calculateDays);

        function calculateDays() {
            const fromDate = document.getElementById('from_date').value;
            const toDate = document.getElementById('to_date').value;

            if(fromDate && toDate) {
                const from = new Date(fromDate);
                const to = new Date(toDate);
                const days = Math.floor((to - from) / (1000 * 60 * 60 * 24)) + 1;

                if(days > 0) {
                    document.getElementById('daysCount').textContent = days;
                    document.getElementById('daysDisplay').classList.add('show');
                } else {
                    document.getElementById('daysDisplay').classList.remove('show');
                }
            }
        }

        // ============ Form Submission ============
        document.getElementById('leaveForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            const formData = new FormData(this);

            fetch('employee-leave-application.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('successMessage').classList.add('show');
                    document.getElementById('errorMessage').classList.remove('show');
                    document.getElementById('leaveForm').reset();
                    document.getElementById('daysDisplay').classList.remove('show');
                    
                    // Redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'punch-attendance.php';
                    }, 2000);
                } else {
                    document.getElementById('errorMessage').textContent = '❌ ' + data.message;
                    document.getElementById('errorMessage').classList.add('show');
                    document.getElementById('successMessage').classList.remove('show');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Request';
                }
            })
            .catch(error => {
                document.getElementById('errorMessage').textContent = '❌ An error occurred. Please try again.';
                document.getElementById('errorMessage').classList.add('show');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Request';
                console.error('Error:', error);
            });
        });

        // ============ Navigation Functions ============
        function goBackToAttendance() {
            window.location.href = 'punch-attendance.php';
        }

        function logoutUser() {
            if(confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }

        function goBack() {
            window.history.back();
        }

        // ============ Initialize ============
        document.addEventListener('DOMContentLoaded', function() {
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('from_date').setAttribute('min', today);
            document.getElementById('to_date').setAttribute('min', today);

            // Prevent pull-to-refresh on mobile
            document.body.addEventListener('touchmove', function(e) {
                if(e.touches.length > 1) {
                    e.preventDefault();
                }
            }, { passive: false });
        });
    </script>
</body>
</html>
