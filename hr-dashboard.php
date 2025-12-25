<?php
session_start();
require_once 'config.php';

// Check if HR is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'hr') {
    $_SESSION['error'] = "Please login as HR to access this section.";
    header('Location: hrsection-login.php');
    exit();
}

$hr_name = $_SESSION['full_name'];
$hr_branch = $_SESSION['branch'];
$hr_id = $_SESSION['user_id'];

// Fetch statistics
$pending_query = "SELECT COUNT(*) as count FROM leave_requests WHERE branch = '$hr_branch' AND status = 'Pending'";
$pending_result = mysqli_query($conn, $pending_query);
$pending_count = mysqli_fetch_assoc($pending_result)['count'];

$approved_query = "SELECT COUNT(*) as count FROM leave_requests WHERE branch = '$hr_branch' AND status = 'APPROVED'";
$approved_result = mysqli_query($conn, $approved_query);
$approved_count = mysqli_fetch_assoc($approved_result)['count'];

$rejected_query = "SELECT COUNT(*) as count FROM leave_requests WHERE branch = '$hr_branch' AND status = 'REJECTED'";
$rejected_result = mysqli_query($conn, $rejected_query);
$rejected_count = mysqli_fetch_assoc($rejected_result)['count'];

// Fetch all leave requests for this branch
$query = "SELECT * FROM leave_requests WHERE branch = '$hr_branch' ORDER BY submitted_date DESC";
$result = mysqli_query($conn, $query);

// Handle approve/reject action
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_id = mysqli_real_escape_string($conn, $_POST['leave_id']);
    $action = mysqli_real_escape_string($conn, $_POST['action']);
    $rejection_reason = mysqli_real_escape_string($conn, $_POST['rejection_reason'] ?? '');
    
    if($action == 'approve') {
        $update_query = "UPDATE leave_requests SET status = 'APPROVED', approved_by = '$hr_id', approved_by_name = '$hr_name', approved_date = NOW() WHERE id = '$leave_id'";
    } else {
        $update_query = "UPDATE leave_requests SET status = 'REJECTED', approved_by = '$hr_id', approved_by_name = '$hr_name', approved_date = NOW(), rejection_reason = '$rejection_reason' WHERE id = '$leave_id'";
    }
    
    if(mysqli_query($conn, $update_query)) {
        $_SESSION['success'] = "Leave request updated successfully!";
        header('Location: hr-dashboard.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Dashboard - Leave Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f3f4f6;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #34d399 0%, #3b82f6 100%);
            border-radius: 15px;
            padding: 30px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .header-left h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header-left p {
            font-size: 14px;
            opacity: 0.9;
        }

        .header-right {
            text-align: right;
        }

        .branch-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.3);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Statistics */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
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
            font-size: 36px;
            margin-bottom: 8px;
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
            font-size: 14px;
        }

        /* Leave Requests Section */
        .requests-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .requests-header h2 {
            color: #1f2937;
            font-size: 24px;
        }

        .filter-btn {
            background: white;
            border: 2px solid #e5e7eb;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            border-color: #3b82f6;
            color: #3b82f6;
        }

        .filter-btn.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        /* Leave Request Card */
        .request-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #3b82f6;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .request-card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .request-card.pending {
            border-left-color: #f59e0b;
        }

        .request-card.approved {
            border-left-color: #10b981;
        }

        .request-card.rejected {
            border-left-color: #ef4444;
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .request-header h3 {
            color: #1f2937;
            font-size: 18px;
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

        .request-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
        }

        .detail-label {
            color: #6b7280;
            font-weight: 500;
        }

        .detail-value {
            color: #1f2937;
            font-weight: 600;
        }

        .request-reason {
            background: #f9fafb;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 3px solid #3b82f6;
        }

        .request-reason h4 {
            color: #6b7280;
            font-size: 12px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .request-reason p {
            color: #1f2937;
            font-size: 14px;
        }

        /* Action Buttons */
        .request-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-approve {
            background: #10b981;
            color: white;
        }

        .btn-approve:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .btn-reject {
            background: #ef4444;
            color: white;
        }

        .btn-reject:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
        }

        .close-modal {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #6b7280;
        }

        .close-modal:hover {
            color: #1f2937;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: #374151;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit {
            background: #3b82f6;
            color: white;
        }

        .btn-submit:hover {
            background: #2563eb;
        }

        .btn-cancel {
            background: #e5e7eb;
            color: #1f2937;
        }

        .btn-cancel:hover {
            background: #d1d5db;
        }

        /* Success Message */
        .success-message {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideDown 0.3s ease;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }

        .empty-state h3 {
            color: #9ca3af;
            margin-bottom: 10px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes slideDown {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }

            .request-details {
                grid-template-columns: 1fr;
            }

            .request-actions {
                flex-direction: column;
            }

            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1>🏢 HR Dashboard</h1>
                <p>Welcome, <strong><?php echo $hr_name; ?></strong></p>
            </div>
            <div class="header-right">
                <div class="branch-badge">📍 <?php echo $hr_branch; ?></div>
                <form method="POST" action="logout.php" style="display: inline;">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>
        </div>

        <!-- Success Message -->
        <?php
        if(isset($_SESSION['success'])) {
            echo '<div class="success-message">✅ ' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        ?>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card pending">
                <h3><?php echo $pending_count; ?></h3>
                <p>Pending Requests</p>
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
        <div class="requests-header">
            <h2>📋 Leave Requests</h2>
        </div>

        <?php
        if(mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                $days = (strtotime($row['to_date']) - strtotime($row['from_date'])) / (60 * 60 * 24) + 1;
                $status_class = strtolower($row['status']);
                $is_pending = $row['status'] == 'Pending' ? true : false;
        ?>

            <div class="request-card <?php echo $status_class; ?>">
                <div class="request-header">
                    <div>
                        <h3><?php echo $row['employee_name']; ?></h3>
                        <p style="color: #6b7280; font-size: 13px;">Employee Code: <strong><?php echo $row['employee_code']; ?></strong></p>
                    </div>
                    <span class="status-badge <?php echo $status_class; ?>">
                        <?php echo $row['status']; ?>
                    </span>
                </div>

                <div class="request-details">
                    <div class="detail-item">
                        <span class="detail-label">Leave Type:</span>
                        <span class="detail-value"><?php echo $row['leave_type']; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Duration:</span>
                        <span class="detail-value"><?php echo $days; ?> day(s)</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">From:</span>
                        <span class="detail-value"><?php echo date('d/m/Y', strtotime($row['from_date'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">To:</span>
                        <span class="detail-value"><?php echo date('d/m/Y', strtotime($row['to_date'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Submitted:</span>
                        <span class="detail-value"><?php echo date('d/m/Y h:i A', strtotime($row['submitted_date'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Branch:</span>
                        <span class="detail-value"><?php echo $row['branch']; ?></span>
                    </div>
                </div>

                <?php if($row['reason']) { ?>
                <div class="request-reason">
                    <h4>Reason:</h4>
                    <p><?php echo $row['reason']; ?></p>
                </div>
                <?php } ?>

                <?php if($is_pending) { ?>
                <div class="request-actions">
                    <button class="btn btn-approve" onclick="openApproveModal(<?php echo $row['id']; ?>)">✓ Approve</button>
                    <button class="btn btn-reject" onclick="openRejectModal(<?php echo $row['id']; ?>)">✗ Reject</button>
                </div>
                <?php } ?>
            </div>

        <?php
            }
        } else {
            echo '<div class="empty-state">';
            echo '<h3>📭 No leave requests found</h3>';
            echo '<p>All pending requests have been processed</p>';
            echo '</div>';
        }
        ?>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('approveModal')">&times;</span>
            <div class="modal-header">✓ Approve Leave Request</div>
            <form method="POST">
                <input type="hidden" id="leaveIdApprove" name="leave_id">
                <input type="hidden" name="action" value="approve">
                <div class="form-group">
                    <label>Are you sure you want to approve this leave request?</label>
                    <p style="color: #6b7280; font-size: 14px; margin-top: 10px;">This action cannot be undone.</p>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal('approveModal')">Cancel</button>
                    <button type="submit" class="btn-submit">Yes, Approve</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('rejectModal')">&times;</span>
            <div class="modal-header">✗ Reject Leave Request</div>
            <form method="POST">
                <input type="hidden" id="leaveIdReject" name="leave_id">
                <input type="hidden" name="action" value="reject">
                <div class="form-group">
                    <label>Rejection Reason (Optional):</label>
                    <textarea name="rejection_reason" placeholder="Enter reason for rejection..." rows="4"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal('rejectModal')">Cancel</button>
                    <button type="submit" class="btn-submit">Yes, Reject</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openApproveModal(leaveId) {
            document.getElementById('leaveIdApprove').value = leaveId;
            document.getElementById('approveModal').style.display = 'block';
        }

        function openRejectModal(leaveId) {
            document.getElementById('leaveIdReject').value = leaveId;
            document.getElementById('rejectModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        window.onclick = function(event) {
            let approveModal = document.getElementById('approveModal');
            let rejectModal = document.getElementById('rejectModal');
            
            if (event.target == approveModal) {
                approveModal.style.display = 'none';
            }
            if (event.target == rejectModal) {
                rejectModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>
