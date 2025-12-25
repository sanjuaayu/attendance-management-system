<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$employee_name = $_SESSION['full_name'];
$employee_code = $_SESSION['employee_code'] ?? 'N/A';

// Get current month or selected month
$selected_month = $_GET['month'] ?? date('Y-m');
$month_display = date('F Y', strtotime($selected_month . '-01'));

// Calculate month start and end dates
$month_start = $selected_month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));

// ============ FETCH MONTHLY STATISTICS ============

// Total days in month
$total_days_in_month = date('t', strtotime($month_start));

// Count punch records
$punch_stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_punches,
        SUM(CASE WHEN punch_out_datetime IS NOT NULL THEN 1 ELSE 0 END) as completed_punches,
        SUM(CASE WHEN punch_out_datetime IS NULL THEN 1 ELSE 0 END) as incomplete_punches
    FROM attendance 
    WHERE user_id = $user_id 
    AND DATE(punch_in_datetime) >= '$month_start' 
    AND DATE(punch_in_datetime) <= '$month_end'
"));

// Count attendance status
$status_stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        SUM(CASE 
            WHEN punch_out_datetime IS NULL THEN 0
            WHEN TIMESTAMPDIFF(MINUTE, punch_in_datetime, punch_out_datetime) >= 480 THEN 1
            ELSE 0
        END) as present_days,
        
        SUM(CASE 
            WHEN punch_out_datetime IS NULL THEN 0
            WHEN TIMESTAMPDIFF(MINUTE, punch_in_datetime, punch_out_datetime) >= 270 
                AND TIMESTAMPDIFF(MINUTE, punch_in_datetime, punch_out_datetime) < 480 THEN 1
            ELSE 0
        END) as half_days,
        
        SUM(CASE 
            WHEN punch_out_datetime IS NULL THEN 1
            WHEN TIMESTAMPDIFF(MINUTE, punch_in_datetime, punch_out_datetime) < 270 THEN 1
            ELSE 0
        END) as absent_days
        
    FROM attendance 
    WHERE user_id = $user_id 
    AND DATE(punch_in_datetime) >= '$month_start' 
    AND DATE(punch_in_datetime) <= '$month_end'
"));

// Fetch detailed records
$attendance_query = "
    SELECT 
        DATE(punch_in_datetime) as attendance_date,
        DATE_FORMAT(punch_in_datetime, '%h:%i %p') as punch_in_time,
        DATE_FORMAT(punch_out_datetime, '%h:%i %p') as punch_out_time,
        punch_in_datetime,
        punch_out_datetime,
        
        CASE 
            WHEN punch_out_datetime IS NOT NULL THEN
                CONCAT(
                    FLOOR(TIMESTAMPDIFF(MINUTE, punch_in_datetime, punch_out_datetime) / 60), 'h ',
                    MOD(TIMESTAMPDIFF(MINUTE, punch_in_datetime, punch_out_datetime), 60), 'm'
                )
            ELSE '--'
        END as working_hours,
        
        CASE 
            WHEN punch_out_datetime IS NULL THEN 'Absent'
            WHEN TIMESTAMPDIFF(MINUTE, punch_in_datetime, punch_out_datetime) < 270 THEN 'Absent'
            WHEN TIMESTAMPDIFF(MINUTE, punch_in_datetime, punch_out_datetime) >= 270 
                AND TIMESTAMPDIFF(MINUTE, punch_in_datetime, punch_out_datetime) < 480 THEN 'Half Day'
            WHEN TIMESTAMPDIFF(MINUTE, punch_in_datetime, punch_out_datetime) >= 480 THEN 'Present'
        END as status,
        
        CASE 
            WHEN punch_out_datetime IS NULL THEN 'No Punch Out'
            WHEN TIMESTAMPDIFF(MINUTE, punch_in_datetime, punch_out_datetime) < 270 THEN '< 4.5 hours'
            ELSE 'OK'
        END as remarks
        
    FROM attendance 
    WHERE user_id = $user_id 
    AND DATE(punch_in_datetime) >= '$month_start' 
    AND DATE(punch_in_datetime) <= '$month_end'
    ORDER BY attendance_date DESC
";

$attendance_result = mysqli_query($conn, $attendance_query);

$present = $status_stats['present_days'] ?? 0;
$half_day = $status_stats['half_days'] ?? 0;
$absent = $status_stats['absent_days'] ?? 0;
$total_punches = $punch_stats['total_punches'] ?? 0;
$completed = $punch_stats['completed_punches'] ?? 0;
$incomplete = $punch_stats['incomplete_punches'] ?? 0;

// Calculate working days (excluding absent without punch)
$working_days = $present + $half_day + $absent;
$days_without_punch = $total_days_in_month - $working_days;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance Record</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-back {
            background: #e0e7ff;
            color: #667eea;
        }
        
        .btn-back:hover {
            background: #c7d2fe;
        }
        
        /* Month Selector */
        .month-selector {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .month-selector label {
            font-weight: 600;
            color: #333;
        }
        
        .month-selector input[type="month"] {
            padding: 8px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .month-selector button {
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border-left: 4px solid;
        }
        
        .stat-card.total { border-left-color: #3b82f6; }
        .stat-card.present { border-left-color: #10b981; }
        .stat-card.half { border-left-color: #f59e0b; }
        .stat-card.absent { border-left-color: #ef4444; }
        .stat-card.incomplete { border-left-color: #8b5cf6; }
        
        .stat-card h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .stat-card.total h3 { color: #3b82f6; }
        .stat-card.present h3 { color: #10b981; }
        .stat-card.half h3 { color: #f59e0b; }
        .stat-card.absent h3 { color: #ef4444; }
        .stat-card.incomplete h3 { color: #8b5cf6; }
        
        .stat-card p {
            color: #6b7280;
            font-size: 13px;
            font-weight: 600;
        }
        
        /* Table */
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        .table-container h2 {
            margin-bottom: 15px;
            color: #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f3f4f6;
        }
        
        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #6b7280;
        }
        
        tbody tr:hover {
            background: #f9fafb;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-badge.present {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.half {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.absent {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .remarks {
            font-size: 12px;
            color: #9ca3af;
            font-style: italic;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .header h1 {
                font-size: 20px;
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 8px 6px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📊 My Attendance Record</h1>
            <p><strong><?php echo htmlspecialchars($employee_name); ?></strong> (<?php echo htmlspecialchars($employee_code); ?>)</p>
            <div class="header-actions">
                <button class="btn btn-back" onclick="location.href='punch-attendance.php'">← Back to Dashboard</button>
            </div>
        </div>
        
        <!-- Month Selector -->
        <div class="month-selector">
            <label for="month">Select Month:</label>
            <input type="month" id="month" value="<?php echo $selected_month; ?>">
            <button onclick="changeMonth()">View</button>
        </div>
        
        <h3 style="color: white; margin-bottom: 15px; text-align: center; font-size: 20px;">
            📅 <?php echo $month_display; ?>
        </h3>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <h3><?php echo $total_days_in_month; ?></h3>
                <p>Total Days in Month</p>
            </div>
            <div class="stat-card present">
                <h3><?php echo $present; ?></h3>
                <p>Present Days (8h+)</p>
            </div>
            <div class="stat-card half">
                <h3><?php echo $half_day; ?></h3>
                <p>Half Days (4.5-8h)</p>
            </div>
            <div class="stat-card absent">
                <h3><?php echo $absent; ?></h3>
                <p>Absent Days</p>
            </div>
            <div class="stat-card total">
                <h3><?php echo $total_punches; ?></h3>
                <p>Total Punch In Records</p>
            </div>
            <div class="stat-card present">
                <h3><?php echo $completed; ?></h3>
                <p>Completed (Punch Out)</p>
            </div>
            <div class="stat-card incomplete">
                <h3><?php echo $incomplete; ?></h3>
                <p>Incomplete (No Punch Out)</p>
            </div>
            <div class="stat-card absent">
                <h3><?php echo $days_without_punch; ?></h3>
                <p>Days Without Any Punch</p>
            </div>
        </div>
        
        <!-- Detailed Table -->
        <div class="table-container">
            <h2>📅 Day-wise Attendance Details</h2>
            
            <?php if(mysqli_num_rows($attendance_result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Punch In</th>
                            <th>Punch Out</th>
                            <th>Working Hours</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($attendance_result)): ?>
                            <tr>
                                <td><?php echo date('d-M-Y', strtotime($row['attendance_date'])); ?></td>
                                <td><?php echo $row['punch_in_time']; ?></td>
                                <td><?php echo $row['punch_out_time'] ?? '--'; ?></td>
                                <td><?php echo $row['working_hours']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower(str_replace(' ', '', $row['status'])); ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td class="remarks"><?php echo $row['remarks']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>📭 No Attendance Records Found</h3>
                    <p>No attendance records for <?php echo $month_display; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function changeMonth() {
            const month = document.getElementById('month').value;
            window.location.href = 'employee-attendance-record.php?month=' + month;
        }
    </script>
</body>
</html>
