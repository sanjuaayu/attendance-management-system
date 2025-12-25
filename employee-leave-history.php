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

// Fetch all leave requests for this employee
$query = "SELECT * FROM leave_requests WHERE employee_id = '$user_id' ORDER BY submitted_date DESC";
$result = mysqli_query($conn, $query);

// Count statistics
$pending_query = "SELECT COUNT(*) as count FROM leave_requests WHERE employee_id = '$user_id' AND status = 'Pending'";
$pending_result = mysqli_query($conn, $pending_query);
$pending_count = mysqli_fetch_assoc($pending_result)['count'];

$approved_query = "SELECT COUNT(*) as count FROM leave_requests WHERE employee_id = '$user_id' AND status = 'APPROVED'";
$approved_result = mysqli_query($conn, $approved_query);
$approved_count = mysqli_fetch_assoc($approved_result)['count'];

$rejected_query = "SELECT COUNT(*) as count FROM leave_requests WHERE employee_id = '$user_id' AND status = 'REJECTED'";
$rejected_result = mysqli_query($conn, $rejected_query);
$rejected_count = mysqli_fetch_assoc($rejected_result)['count'];

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Leave History - Employee</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 10px;
            padding-bottom: max(10px, env(safe-area-inset-bottom));
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.4s ease;
        }

        .header {
            background: linear-gradient(135deg, #34d399 0%, #3b82f6 100%);
            color: white;
            padding: 20px 16px;
            text-align: center;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 4px;
            font-weight: 700;
        }

        .header p {
            font-size: 13px;
            opacity: 0.9;
        }

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
            font-size: 14px;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
            transition: all 0.3s ease;
        }

        .btn-back {
            background: #e0e7ff;
            color: #3b82f6;
        }

        .btn-back:active {
            background: #c7d2fe;
            transform: scale(0.98);
        }

        .btn-apply {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-apply:active {
            background: #bfdbfe;
            transform: scale(0.98);
        }

        .content {
            padding: 20px 16px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #f3f4f6;
            padding: 14px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #3b82f6;
        }

        .stat-card.pending {
            border-left-color: #f59e0b;
        }

        .stat-card.approved {
            border-left-color: #10b981;
        }

        .stat-card.rejected {
            border-left-color: #ef4444;
        }

        .stat-card h3 {
            font-size: 28px;
            margin-bottom: 4px;
        }

        .stat-card.pending h3 {
            color: #f59e0b;
        }

        .stat-card.approved h3 {
            color: #10b981;
        }

        .stat-card.rejected h3 {
            color: #ef4444;
        }

        .stat-card p {
            color: #6b7280;
            font-size: 12px;
        }

        .section-title {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .leave-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }

        .leave-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .leave-card.pending {
            border-left: 4px solid #f59e0b;
            background: #fffbeb;
        }

        .leave-card.approved {
            border-left: 4px solid #10b981;
            background: #f0fdf4;
        }

        .leave-card.rejected {
            border-left: 4px solid #ef4444;
            background: #fef2f2;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .card-header h3 {
            font-size: 16px;
            color: #1f2937;
            font-weight: 600;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #f59e0b;
        }

        .status-badge.approved {
            background: #d1fae5;
            color: #10b981;
        }

        .status-badge.rejected {
            background: #fee2e2;
            color: #ef4444;
        }

        .card-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            color: #6b7280;
            font-weight: 600;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .detail-value {
            color: #1f2937;
            font-weight: 500;
        }

        .card-reason {
            background: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 3px solid #3b82f6;
        }

        .card-reason p {
            color: #1f2937;
            font-size: 14px;
            line-height: 1.5;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }

        .empty-state h3 {
            color: #9ca3af;
            margin-bottom: 10px;
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

        @media (min-width: 768px) {
            .container {
                margin: 20px auto;
            }

            .header {
                padding: 30px 24px;
            }

            .header h1 {
                font-size: 28px;
            }

            .content {
                padding: 30px 24px;
            }

            .card-details {
                grid-template-columns: 1fr 1fr 1fr 1fr;
            }

            .stats {
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Leave History</h1>
            <p><?php echo htmlspecialchars($employee_name); ?> - <?php echo htmlspecialchars($employee_branch); ?></p>
        </div>

        <!-- Header Buttons -->
        <div class="header-buttons">
            <button type="button" class="btn-header btn-back" onclick="goBack()" title="Back">
                ← Back
            </button>
            <button type="button" class="btn-header btn-apply" onclick="applyLeave()" title="Apply New Leave">
                ➕ Apply Leave
            </button>
        </div>

        <div class="content">
            <!-- Statistics -->
            <div class="stats">
                <div class="stat-card pending">
                    <h3><?php echo $pending_count; ?></h3>
                    <p>Pending</p>
                </div>
                <div class="stat-card approved">
                    <h3><?php echo $approved_count; ?></h3>
                    <p>Approved</p>
                </div>
                <div class="stat-card rejected">
                    <h3><?php echo $rejected_count; ?></h3>
                    <p>Rejected</p>
                </div>
            </div>

            <!-- Leave Requests -->
            <div>
                <div class="section-title">📝 Your Leave Requests</div>

                <?php
                if(mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) {
                        $days = (strtotime($row['to_date']) - strtotime($row['from_date'])) / (60 * 60 * 24) + 1;
                        $status_class = strtolower($row['status']);
                ?>

                <div class="leave-card <?php echo $status_class; ?>">
                    <div class="card-header">
                        <h3><?php echo htmlspecialchars($row['leave_type']); ?></h3>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </div>

                    <div class="card-details">
                        <div class="detail-item">
                            <span class="detail-label">From:</span>
                            <span class="detail-value"><?php echo date('d M Y', strtotime($row['from_date'])); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">To:</span>
                            <span class="detail-value"><?php echo date('d M Y', strtotime($row['to_date'])); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Days:</span>
                            <span class="detail-value"><?php echo $days; ?> day(s)</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Submitted:</span>
                            <span class="detail-value"><?php echo date('d M Y', strtotime($row['submitted_date'])); ?></span>
                        </div>
                    </div>

                    <?php if($row['reason']) { ?>
                    <div class="card-reason">
                        <p><strong>Reason:</strong> <?php echo htmlspecialchars($row['reason']); ?></p>
                    </div>
                    <?php } ?>

                    <?php if($row['status'] != 'Pending') { ?>
                    <div class="card-reason" style="background: #f0fdf4; border-left-color: #10b981;">
                        <p>
                            <strong>Approved by:</strong> <?php echo htmlspecialchars($row['approved_by_name'] ?? 'HR'); ?><br>
                            <strong>Date:</strong> <?php echo date('d M Y h:i A', strtotime($row['approved_date'] ?? $row['submitted_date'])); ?>
                        </p>
                    </div>
                    <?php } ?>

                    <?php if($row['status'] == 'REJECTED' && $row['rejection_reason']) { ?>
                    <div class="card-reason" style="background: #fef2f2; border-left-color: #ef4444;">
                        <p>
                            <strong>Rejection Reason:</strong> <?php echo htmlspecialchars($row['rejection_reason']); ?>
                        </p>
                    </div>
                    <?php } ?>
                </div>

                <?php
                    }
                } else {
                    echo '<div class="empty-state">';
                    echo '<h3>📭 No Leave Requests</h3>';
                    echo '<p>You haven\'t submitted any leave requests yet.</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        function goBack() {
            window.history.back();
        }

        function applyLeave() {
            window.location.href = 'employee-leave-application.php';
        }
    </script>
</body>
</html>
