<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header('Location: manager-login.php');
    exit();
}

$manager_name = $_SESSION['full_name'];
$manager_branch = $_SESSION['branch'];

// Handle approve/reject actions
if(isset($_POST['action']) && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'];
    
    if($action == 'approve') {
        mysqli_query($conn, "UPDATE leave_requests SET status = 'APPROVED', approved_by_name = '$manager_name', approved_date = NOW() WHERE id = $request_id");
        $success = "✅ Leave approved!";
    } elseif($action == 'reject') {
        $reason = mysqli_real_escape_string($conn, $_POST['rejection_reason']);
        mysqli_query($conn, "UPDATE leave_requests SET status = 'REJECTED', approved_by_name = '$manager_name', approved_date = NOW(), rejection_reason = '$reason' WHERE id = $request_id");
        $success = "❌ Leave rejected!";
    }
}

// ============ FIX: Fetch ALL leave requests (not just Pending) ============
$query = "SELECT lr.*, u.employee_code 
          FROM leave_requests lr 
          JOIN users u ON lr.employee_id = u.id 
          WHERE lr.branch = '$manager_branch' 
          ORDER BY 
            CASE 
                WHEN lr.status = 'Pending' THEN 1
                WHEN lr.status = 'APPROVED' THEN 2
                WHEN lr.status = 'REJECTED' THEN 3
            END,
            lr.submitted_date DESC";

$result = mysqli_query($conn, $query);

// Get statistics
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

$stats_query = "SELECT 
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'APPROVED' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'REJECTED' THEN 1 ELSE 0 END) as rejected
                FROM leave_requests 
                WHERE branch = '$manager_branch'";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

$pending_count = $stats['pending'] ?? 0;
$approved_count = $stats['approved'] ?? 0;
$rejected_count = $stats['rejected'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { 
        font-family: Arial; 
        background: linear-gradient(to right, #22c1c3, #4facfe);
        min-height: 100vh; 
        padding: 20px; 
    }
    .container { max-width: 1200px; margin: 0 auto; }
    
    .header { 
        background: white; 
        border-radius: 15px; 
        padding: 30px; 
        margin-bottom: 20px; 
        box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
    }
    .header h1 { color: #4facfe; font-size: 28px; }
    .header p { color: #6b7280; margin-top: 5px; }
    
    .btn-logout { 
        padding: 10px 20px; 
        background: #ef4444; 
        color: white; 
        border: none; 
        border-radius: 8px; 
        cursor: pointer; 
        font-weight: 600; 
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-left: 5px solid;
    }
    
    .stat-card.pending { border-left-color: #f59e0b; }
    .stat-card.approved { border-left-color: #10b981; }
    .stat-card.rejected { border-left-color: #ef4444; }
    
    .stat-card h3 { font-size: 36px; margin-bottom: 10px; }
    .stat-card.pending h3 { color: #f59e0b; }
    .stat-card.approved h3 { color: #10b981; }
    .stat-card.rejected h3 { color: #ef4444; }
    
    .stat-card p { color: #6b7280; font-size: 14px; }
    
    .requests { 
        background: white; 
        border-radius: 15px; 
        padding: 30px; 
        box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
    }
    
    .section-title {
        font-size: 24px;
        color: #1f2937;
        margin-bottom: 20px;
    }
    
    .request-card { 
        background: #f9fafb; 
        border: 2px solid #e5e7eb; 
        border-radius: 10px; 
        padding: 20px; 
        margin-bottom: 15px; 
        border-left: 5px solid;
    }
    
    .request-card.pending { border-left-color: #f59e0b; }
    .request-card.approved { border-left-color: #10b981; background: #f0fdf4; }
    .request-card.rejected { border-left-color: #ef4444; background: #fef2f2; }
    
    .request-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 15px;
    }
    
    .employee-info h4 { color: #1f2937; margin-bottom: 5px; }
    .employee-info p { color: #6b7280; font-size: 14px; }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-badge.pending { background: #fef3c7; color: #f59e0b; }
    .status-badge.approved { background: #d1fae5; color: #10b981; }
    .status-badge.rejected { background: #fee2e2; color: #ef4444; }
    
    .request-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .detail-item { display: flex; flex-direction: column; }
    .detail-label { color: #6b7280; font-size: 12px; margin-bottom: 5px; }
    .detail-value { color: #1f2937; font-weight: 600; font-size: 14px; }
    
    .request-reason { 
        background: white; 
        padding: 10px; 
        border-radius: 5px; 
        margin: 10px 0; 
    }
    
    .actions { display: flex; gap: 10px; margin-top: 15px; }
    
    .btn { 
        padding: 10px 20px; 
        border: none; 
        border-radius: 8px; 
        cursor: pointer; 
        font-weight: 600; 
    }
    
    .btn-approve { background: #10b981; color: white; }
    .btn-reject { background: #ef4444; color: white; }
    
    .approved-info {
        background: #d1fae5;
        padding: 10px;
        border-radius: 5px;
        margin-top: 10px;
        font-size: 13px;
        color: #065f46;
    }
    
    .rejected-info {
        background: #fee2e2;
        padding: 10px;
        border-radius: 5px;
        margin-top: 10px;
        font-size: 13px;
        color: #991b1b;
    }
    
    .modal { 
        display: none; 
        position: fixed; 
        top: 0; 
        left: 0; 
        width: 100%; 
        height: 100%; 
        background: rgba(0,0,0,0.5); 
        justify-content: center; 
        align-items: center; 
    }
    
    .modal.show { display: flex; }
    
    .modal-content { 
        background: white; 
        border-radius: 15px; 
        padding: 30px; 
        max-width: 500px; 
        width: 90%; 
    }
    
    .modal-content textarea { 
        width: 100%; 
        padding: 10px; 
        border: 2px solid #e5e7eb; 
        border-radius: 8px; 
        margin: 15px 0; 
        min-height: 100px; 
    }
    
    .success { 
        background: #d1fae5; 
        border: 2px solid #10b981; 
        color: #065f46; 
        padding: 15px; 
        border-radius: 10px; 
        margin-bottom: 20px; 
    }
</style>

</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>👨‍💼 Manager Dashboard</h1>
                <p><?php echo $manager_name; ?> - <?php echo $manager_branch; ?></p>
            </div>
            <button class="btn-logout" onclick="if(confirm('Logout?')) location.href='logout.php'">Logout</button>
        </div>

        <?php if(isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
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

        <!-- All Leave Requests -->
        <div class="requests">
            <h2 class="section-title">📋 Leave Requests</h2>
            
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <div class="request-card <?php echo strtolower($row['status']); ?>">
                        <div class="request-header">
                            <div class="employee-info">
                                <h4><?php echo $row['employee_name']; ?></h4>
                                <p>Code: <?php echo $row['employee_code']; ?></p>
                            </div>
                            <span class="status-badge <?php echo strtolower($row['status']); ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </div>

                        <div class="request-details">
                            <div class="detail-item">
                                <span class="detail-label">Leave Type</span>
                                <span class="detail-value"><?php echo $row['leave_type']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">From</span>
                                <span class="detail-value"><?php echo date('d M Y', strtotime($row['from_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">To</span>
                                <span class="detail-value"><?php echo date('d M Y', strtotime($row['to_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Days</span>
                                <span class="detail-value"><?php echo $row['days_count']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Submitted</span>
                                <span class="detail-value"><?php echo date('d M Y', strtotime($row['submitted_date'])); ?></span>
                            </div>
                        </div>

                        <?php if($row['reason']): ?>
                        <div class="request-reason">
                            <strong>Reason:</strong> <?php echo $row['reason']; ?>
                        </div>
                        <?php endif; ?>

                        <?php if($row['status'] == 'Pending'): ?>
                            <div class="actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-approve" onclick="return confirm('Approve?')">✅ Approve</button>
                                </form>
                                <button class="btn btn-reject" onclick="openReject(<?php echo $row['id']; ?>)">❌ Reject</button>
                            </div>
                        <?php elseif($row['status'] == 'APPROVED'): ?>
                            <div class="approved-info">
                                ✅ Approved by: <?php echo $row['approved_by_name'] ?? 'HR'; ?> 
                                on <?php echo date('d M Y, h:i A', strtotime($row['approved_date'])); ?>
                            </div>
                        <?php elseif($row['status'] == 'REJECTED'): ?>
                            <div class="rejected-info">
                                ❌ Rejected by: <?php echo $row['approved_by_name'] ?? 'HR'; ?> 
                                on <?php echo date('d M Y, h:i A', strtotime($row['approved_date'])); ?>
                                <?php if($row['rejection_reason']): ?>
                                    <br>Reason: <?php echo $row['rejection_reason']; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #6b7280; padding: 40px;">No leave requests</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <h3>Reject Leave Request</h3>
            <form method="POST">
                <input type="hidden" id="reject_id" name="request_id">
                <input type="hidden" name="action" value="reject">
                <textarea name="rejection_reason" placeholder="Rejection reason..." required></textarea>
                <div class="actions">
                    <button type="button" class="btn" style="background: #9ca3af;" onclick="closeReject()">Cancel</button>
                    <button type="submit" class="btn btn-reject">Reject</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openReject(id) {
            document.getElementById('reject_id').value = id;
            document.getElementById('rejectModal').classList.add('show');
        }
        function closeReject() {
            document.getElementById('rejectModal').classList.remove('show');
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
