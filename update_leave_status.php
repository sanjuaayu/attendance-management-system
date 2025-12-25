<?php
session_start();
include('db_connection.php');

header('Content-Type: application/json');

if(!isset($_SESSION['hr_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_id = mysqli_real_escape_string($conn, $_POST['leave_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $hr_id = $_SESSION['hr_id'];
    $approved_date = date('Y-m-d H:i:s');
    
    // Update leave request status
    $query = "UPDATE leave_requests 
              SET status = '$status', 
                  approved_by = '$hr_id', 
                  approved_date = '$approved_date' 
              WHERE id = '$leave_id'";
    
    if(mysqli_query($conn, $query)) {
        // If approved, update employee leave balance
        if($status == 'APPROVED') {
            $leave_query = "SELECT employee_id, from_date, to_date FROM leave_requests WHERE id = '$leave_id'";
            $leave_result = mysqli_query($conn, $leave_query);
            $leave_data = mysqli_fetch_assoc($leave_result);
            
            $days_used = (strtotime($leave_data['to_date']) - strtotime($leave_data['from_date'])) / (60 * 60 * 24) + 1;
            
            $balance_query = "UPDATE employees 
                            SET leave_used = leave_used + $days_used,
                                leave_remaining = leave_remaining - $days_used 
                            WHERE id = '{$leave_data['employee_id']}'";
            mysqli_query($conn, $balance_query);
        }
        
        echo json_encode(['success' => true, 'message' => 'Leave request updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
