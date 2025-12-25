<?php
session_start();
require_once 'config.php';

// ============ AUTHENTICATION CHECK ============
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent_admin') {
    die("❌ Access Denied - Parent Admin Login Required");
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$csvData = [];

// Form data
$branchId = $_POST['branch'] ?? 'all';
$userId = $_POST['user'] ?? 'all';

// Date handling
$startDate = '';
$endDate = '';

if (!empty($_POST['start_date'])) {
    $startDate = $_POST['start_date'];
}

if (!empty($_POST['end_date'])) {
    $endDate = $_POST['end_date'];
}

if (!empty($startDate) && empty($endDate)) {
    $endDate = $startDate;
}

$isAllUsersSelected = ($userId === 'all');

// ============ UPDATED ATTENDANCE LOGIC ============
// Office Hours: 10:00 AM - 7:00 PM (9 hours)
// Full Day: 8+ hours
// Half Day: 4.5-8 hours
// Absent (Low Hours): < 4.5 hours
// Absent (No Punch Out): Punch In only, No Punch Out

$sql = "
    SELECT
        u.employee_code,
        u.full_name,
        b.name AS branch_name,
        DATE(a.punch_in_datetime) as date,
        DATE_FORMAT(a.punch_in_datetime, '%h:%i:%s %p') as punch_in_time,
        DATE_FORMAT(a.punch_out_datetime, '%h:%i:%s %p') as punch_out_time,
        a.punch_in_datetime,
        a.punch_out_datetime,
        
        -- Working Hours Display
        CASE 
            WHEN a.punch_out_datetime IS NOT NULL THEN
                CONCAT(
                    FLOOR(TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) / 60), 'h ',
                    MOD(TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime), 60), 'm'
                )
            ELSE '--'
        END as working_hours_display,
        
        -- Working Hours Decimal
        CASE 
            WHEN a.punch_out_datetime IS NOT NULL THEN
                ROUND(TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) / 60, 2)
            ELSE 0
        END as working_hours_decimal,
        
        -- Total Minutes
        TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) as total_minutes,
        
        -- Day Type (UPDATED LOGIC)
        CASE 
            -- No Punch Out = Absent
            WHEN a.punch_out_datetime IS NULL THEN 'Absent'
            
            -- Less than 4.5 hours = Absent
            WHEN TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) < 270 THEN 'Absent'
            
            -- 4.5 to 8 hours = Half Day
            WHEN TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) >= 270 
                AND TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) < 480 THEN 'Half Day'
            
            -- 8+ hours = Full Day
            WHEN TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) >= 480 THEN 'Full Day'
            
            ELSE 'Absent'
        END as day_type,
        
        -- Status (DETAILED EXPLANATION)
        CASE 
            -- No Punch Out = Absent
            WHEN a.punch_out_datetime IS NULL THEN 'Absent - No Punch Out'
            
            -- Less than 4.5 hours = Absent
            WHEN TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) < 270 THEN 
                CONCAT('Absent (', 
                    FLOOR(TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) / 60), 'h ',
                    MOD(TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime), 60), 'm - Less than 4.5h)')
            
            -- Half Day
            WHEN TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) >= 270 
                AND TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) < 480 THEN 
                CONCAT('Half Day (', 
                    FLOOR(TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) / 60), 'h ',
                    MOD(TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime), 60), 'm)')
            
            -- Full Day
            WHEN TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) >= 480 THEN 
                CONCAT('Full Day (', 
                    FLOOR(TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) / 60), 'h ',
                    MOD(TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime), 60), 'm)')
            
            ELSE 'Absent'
        END as status,
        
        -- Day Count (FOR SALARY CALCULATION)
        CASE 
            -- No Punch Out = 0 days (Absent)
            WHEN a.punch_out_datetime IS NULL THEN 0
            
            -- Less than 4.5 hours = 0 days (Absent)
            WHEN TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) < 270 THEN 0
            
            -- Half Day = 0.5 days
            WHEN TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) >= 270 
                AND TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) < 480 THEN 0.5
            
            -- Full Day = 1.0 days
            WHEN TIMESTAMPDIFF(MINUTE, a.punch_in_datetime, a.punch_out_datetime) >= 480 THEN 1.0
            
            ELSE 0
        END as day_count
        
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    JOIN branches b ON a.branch_id = b.id
";

$whereClauses = [];
$params = [];
$types = "";

// Branch filter
if ($branchId !== 'all') {
    $whereClauses[] = "b.id = ?";
    $params[] = intval($branchId);
    $types .= "i";
}

// User filter
if (!$isAllUsersSelected) {
    $whereClauses[] = "u.id = ?";
    $params[] = intval($userId);
    $types .= "i";
}

// Date filter
if (!empty($startDate) && !empty($endDate)) {
    $whereClauses[] = "DATE(a.punch_in_datetime) >= ?";
    $params[] = $startDate;
    $types .= "s";
    
    $whereClauses[] = "DATE(a.punch_in_datetime) <= ?";
    $params[] = $endDate;
    $types .= "s";
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

$sql .= " ORDER BY DATE(a.punch_in_datetime) DESC, u.full_name ASC";

// ============ EXECUTE QUERY ============
$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    
    if ($stmt->error) {
        error_log("SQL Error: " . $stmt->error);
        die("Report generate karne mein error: " . $stmt->error);
    }

    $result = $stmt->get_result();
    
    // CSV HEADER
    $csvData[] = ['Employee Code', 'Employee Name', 'Branch', 'Date', 'Punch In', 'Punch Out', 'Working Hours', 'Day Type', 'Status'];
    
    $totalRecords = 0;
    $totalWorkingHours = 0;
    $totalFullDays = 0;
    $totalHalfDays = 0;
    $totalAbsent = 0;
    $totalAbsentNoPunchOut = 0;
    $totalAbsentLowHours = 0;
    $totalDaysCount = 0;
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $csvData[] = [
                $row['employee_code'] ?? '',
                $row['full_name'] ?? '',
                $row['branch_name'] ?? '',
                $row['date'] ?? '',
                $row['punch_in_time'] ?? '',
                $row['punch_out_time'] ?? 'Not Punched',
                $row['working_hours_display'] ?? '--',
                $row['day_type'] ?? 'Absent',
                $row['status'] ?? 'Unknown'
            ];
            
            $totalRecords++;
            $totalWorkingHours += floatval($row['working_hours_decimal'] ?? 0);
            $totalDaysCount += floatval($row['day_count'] ?? 0);
            
            // Count types
            if ($row['day_type'] == 'Full Day') {
                $totalFullDays++;
            } elseif ($row['day_type'] == 'Half Day') {
                $totalHalfDays++;
            } elseif ($row['day_type'] == 'Absent') {
                $totalAbsent++;
                
                // Track reason for absent
                if ($row['punch_out_datetime'] === NULL) {
                    $totalAbsentNoPunchOut++;
                } else {
                    $totalAbsentLowHours++;
                }
            }
        }
        
        // Calculate total hours and minutes
        $totalHours = floor($totalWorkingHours);
        $totalMinutes = round(($totalWorkingHours - $totalHours) * 60);
        
    } else {
        $csvData[] = ['No data found for selected criteria.', '', '', '', '', '', '', '', ''];
    }
    
    // ============ FINAL SUMMARY ============
    $csvData[] = ['', '', '', '', '', '', '', '', ''];
    $csvData[] = ['=' . str_repeat('=', 80), '', '', '', '', '', '', '', ''];
    $csvData[] = ['ATTENDANCE REPORT SUMMARY', '', '', '', '', '', '', '', ''];
    $csvData[] = ['=' . str_repeat('=', 80), '', '', '', '', '', '', '', ''];
    $csvData[] = ['', '', '', '', '', '', '', '', ''];
    
    // Office Rules
    $csvData[] = ['OFFICE POLICY', '', '', '', '', '', '', '', ''];
    $csvData[] = ['Office Timing', '10:00 AM - 7:00 PM', '(9 hours)', '', '', '', '', '', ''];
    $csvData[] = ['Full Day Required', '8+ hours', '', '', '', '', '', '', ''];
    $csvData[] = ['Half Day', '4.5 to 8 hours', '', '', '', '', '', '', ''];
    $csvData[] = ['Absent (Low Hours)', 'Less than 4.5 hours', '', '', '', '', '', '', ''];
    $csvData[] = ['Absent (No Punch Out)', 'Punch In only, forgot Punch Out', '', '', '', '', '', '', ''];
    $csvData[] = ['', '', '', '', '', '', '', '', ''];
    
    // Attendance Breakdown
    $csvData[] = ['ATTENDANCE BREAKDOWN', '', '', '', '', '', '', '', ''];
    $csvData[] = ['-' . str_repeat('-', 40), '', '', '', '', '', '', '', ''];
    $csvData[] = ['Total Records', $totalRecords, '', '', '', '', '', '', ''];
    $csvData[] = ['', '', '', '', '', '', '', '', ''];
    $csvData[] = ['Full Days (8+ hours)', $totalFullDays, 'Count = ' . $totalFullDays . ' days', '', '', '', '', '', ''];
    $csvData[] = ['Half Days (4.5-8 hours)', $totalHalfDays, 'Count = ' . ($totalHalfDays * 0.5) . ' days', '', '', '', '', '', ''];
    $csvData[] = ['', '', '', '', '', '', '', '', ''];
    $csvData[] = ['ABSENT BREAKDOWN:', '', '', '', '', '', '', '', ''];
    $csvData[] = ['  - No Punch Out', $totalAbsentNoPunchOut, 'Forgot to punch out', '', '', '', '', '', ''];
    $csvData[] = ['  - Low Hours (< 4.5h)', $totalAbsentLowHours, 'Worked less than required', '', '', '', '', '', ''];
    $csvData[] = ['  - Total Absent', $totalAbsent, '', '', '', '', '', '', ''];
    $csvData[] = ['', '', '', '', '', '', '', '', ''];
    
    // Totals
    $csvData[] = ['CALCULATION SUMMARY', '', '', '', '', '', '', '', ''];
    $csvData[] = ['-' . str_repeat('-', 40), '', '', '', '', '', '', '', ''];
    $csvData[] = ['Total Days Count', round($totalDaysCount, 2) . ' days', '(Full Days + Half Days only)', '', '', '', '', '', ''];
    $csvData[] = ['  = Full Days', $totalFullDays . ' × 1.0', '= ' . $totalFullDays . ' days', '', '', '', '', '', ''];
    $csvData[] = ['  + Half Days', $totalHalfDays . ' × 0.5', '= ' . ($totalHalfDays * 0.5) . ' days', '', '', '', '', '', ''];
    $csvData[] = ['  + Absent Days', $totalAbsent . ' × 0.0', '= 0 days', '', '', '', '', '', ''];
    $csvData[] = ['', '', '', '', '', '', '', '', ''];
    $csvData[] = ['Total Working Hours', $totalHours . 'h ' . $totalMinutes . 'm', 'Decimal: ' . round($totalWorkingHours, 2) . ' hours', '', '', '', '', '', ''];
    $csvData[] = ['Average Hours/Day', round($totalWorkingHours / max($totalRecords, 1), 2) . ' hours', '(Total hrs / Total records)', '', '', '', '', '', ''];
    $csvData[] = ['', '', '', '', '', '', '', '', ''];
    
    // Report Info
    $csvData[] = ['REPORT INFORMATION', '', '', '', '', '', '', '', ''];
    $csvData[] = ['-' . str_repeat('-', 40), '', '', '', '', '', '', '', ''];
    $csvData[] = ['Generated Date', date('d-M-Y h:i A'), '', '', '', '', '', '', ''];
    $csvData[] = ['Report Period', $startDate . ' to ' . $endDate, '', '', '', '', '', '', ''];
    $csvData[] = ['Generated By', 'Parent Admin', '', '', '', '', '', '', ''];
    
    $stmt->close();
} else {
    error_log("SQL Prepare Error: " . $conn->error);
    die("Report generate karne mein error.");
}

// ============ CSV OUTPUT ============
$filename = "attendance_report_" . date('Y-m-d_H-i-s') . ".csv";
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen("php://output", "w");

// UTF-8 BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

foreach ($csvData as $row) {
    fputcsv($output, $row);
}

fclose($output);
$conn->close();
exit;
?>
