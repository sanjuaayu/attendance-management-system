<?php
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['branch_id'])) {
    header("Location: index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$branchId = $_SESSION['branch_id'];
$branch = $_SESSION['branch'];

// Database connection
$host = 'localhost';
$db   = 'rupeeqat_employee_attendance';
$user = 'rupeeqat_employee_attendance';
$pass = '4yknR4hLtwCKRjSHbdM5';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$message = '';
$messageType = '';
$branchLocation = null;
$todayAttendance = null;
$fullName = 'User';

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Get user details
    $userStmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $userData = $userStmt->fetch();
    if ($userData) {
        $fullName = htmlspecialchars($userData['full_name']);
    }
    
    // Get branch location details
    $branchStmt = $pdo->prepare("SELECT latitude, longitude, radius, name FROM branches WHERE id = ?");
    $branchStmt->execute([$branchId]);
    $branchLocation = $branchStmt->fetch();
    
    // Check if branch location is configured
    if (!$branchLocation) {
        $message = "Branch not found in database.";
        $messageType = 'error';
    } elseif (!$branchLocation['latitude'] || !$branchLocation['longitude']) {
        $message = "Branch location coordinates not configured. Please contact admin.";
        $messageType = 'error';
    }
    
    // Check today's attendance
    $today = date('Y-m-d');
    $attendanceStmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND DATE(punch_in_datetime) = ?");
    $attendanceStmt->execute([$userId, $today]);
    $todayAttendance = $attendanceStmt->fetch();
    
    // Handle punch in/out
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $branchLocation && $branchLocation['latitude']) {
        $action = $_POST['action'] ?? '';
        $userLat = floatval($_POST['latitude'] ?? 0);
        $userLng = floatval($_POST['longitude'] ?? 0);
        $photoData = $_POST['photo'] ?? '';
        
        if (!$userLat || !$userLng) {
            $message = "Location data is required for attendance.";
            $messageType = 'error';
        } elseif (empty($photoData)) {
            $message = "Photo is required for attendance.";
            $messageType = 'error';
        } else {
            // Calculate distance using Haversine formula
            $distance = calculateDistance(
                $branchLocation['latitude'], 
                $branchLocation['longitude'], 
                $userLat, 
                $userLng
            );
            
            $allowedRadius = $branchLocation['radius'];
            
            if ($distance <= $allowedRadius) {
                if ($action === 'punch_in' && !$todayAttendance) {
                    // Save photo
                    $photoName = savePhoto($photoData, $userId, 'in');
                    
                    // Insert punch in record
                    try {
                        $punchInStmt = $pdo->prepare("INSERT INTO attendance (user_id, branch_id, punch_in_datetime, punch_in_location_lat, punch_in_location_lng, punch_in_photo, location_verified) VALUES (?, ?, NOW(), ?, ?, ?, 1)");
                        $result = $punchInStmt->execute([$userId, $branchId, $userLat, $userLng, $photoName]);
                    } catch (PDOException $e) {
                        // Fallback
                        try {
                            $punchInStmt = $pdo->prepare("INSERT INTO attendance (user_id, branch_id, punch_in_datetime, punch_in_photo) VALUES (?, ?, NOW(), ?)");
                            $result = $punchInStmt->execute([$userId, $branchId, $photoName]);
                        } catch (PDOException $e2) {
                            $punchInStmt = $pdo->prepare("INSERT INTO attendance (user_id, branch_id, punch_in_datetime) VALUES (?, ?, NOW())");
                            $result = $punchInStmt->execute([$userId, $branchId]);
                        }
                    }
                    
                    if ($result) {
                        $message = "Punch In successful! Welcome to work.";
                        $messageType = 'success';
                        // Refresh attendance data
                        $attendanceStmt->execute([$userId, $today]);
                        $todayAttendance = $attendanceStmt->fetch();
                    } else {
                        $message = "Failed to record punch in. Please try again.";
                        $messageType = 'error';
                    }
                    
                } elseif ($action === 'punch_out' && $todayAttendance && !$todayAttendance['punch_out_datetime']) {
                    // Save photo
                    $photoName = savePhoto($photoData, $userId, 'out');
                    
                    // Update punch out record
                    try {
                        $punchOutStmt = $pdo->prepare("UPDATE attendance SET punch_out_datetime = NOW(), punch_out_location_lat = ?, punch_out_location_lng = ?, punch_out_photo = ? WHERE id = ?");
                        $result = $punchOutStmt->execute([$userLat, $userLng, $photoName, $todayAttendance['id']]);
                    } catch (PDOException $e) {
                        // Fallback
                        $punchOutStmt = $pdo->prepare("UPDATE attendance SET punch_out_datetime = NOW(), punch_out_photo = ? WHERE id = ?");
                        $result = $punchOutStmt->execute([$photoName, $todayAttendance['id']]);
                    }
                    
                    if ($result) {
                        $message = "Punch Out successful! Have a great day.";
                        $messageType = 'success';
                        // Refresh attendance data
                        $attendanceStmt->execute([$userId, $today]);
                        $todayAttendance = $attendanceStmt->fetch();
                    } else {
                        $message = "Failed to record punch out. Please try again.";
                        $messageType = 'error';
                    }
                } else {
                    $message = "Invalid action or attendance already recorded.";
                    $messageType = 'error';
                }
            } else {
                $message = "You are outside the allowed area. Distance: " . round($distance, 2) . "m (Max: {$allowedRadius}m)";
                $messageType = 'error';
            }
        }
    }
    
} catch (\PDOException $e) {
    $message = "Database connection error. Error: " . $e->getMessage();
    $messageType = 'error';
    error_log("Database Error: " . $e->getMessage());
}

// Function to save photo
function savePhoto($photoData, $userId, $type) {
    if (empty($photoData)) return null;
    
    // Remove data:image/jpeg;base64, part
    $photoData = preg_replace('#^data:image/[^;]*;base64,#', '', $photoData);
    $photoData = base64_decode($photoData);
    
    // Create uploads directory if it doesn't exist
    $uploadDir = 'uploads/attendance_photos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $filename = $userId . '_' . date('Y-m-d') . '_' . $type . '_' . time() . '.jpg';
    $filepath = $uploadDir . $filename;
    
    // Save photo
    if (file_put_contents($filepath, $photoData)) {
        return $filename;
    }
    
    return null;
}

// Function to calculate distance between two coordinates
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // Earth radius in meters
    
    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);
    $deltaLatRad = deg2rad($lat2 - $lat1);
    $deltaLonRad = deg2rad($lon2 - $lon1);
    
    $a = sin($deltaLatRad / 2) * sin($deltaLatRad / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLonRad / 2) * sin($deltaLonRad / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punch Attendance - Employee Attendance System</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            background: linear-gradient(135deg, #3f4fd8ff 0%, #32670cff 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background: rgba(255,255,255,0.13);
            border-radius: 18px;
            box-shadow: 0 8px 40px rgba(80,80,99,0.10);
            margin-top: 30px;
        }
        .header {
            text-align: center;
            color: #222e50;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }
        .user-info {
            background: rgba(255,255,255,0.80);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
        }
        .user-info h2 {
            color: #19336a;
            margin-bottom: 10px;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .attendance-status {
            background: rgba(255,255,255,0.90);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .status-item {
            text-align: center;
            padding: 15px;
            background: rgba(63, 79, 216, 0.1);
            border-radius: 8px;
        }
        .status-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }
        .status-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: #19336a;
        }
        
        /* Camera Section */
        .camera-section {
            background: rgba(255,255,255,0.90);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
        }
        #video {
            width: 100%;
            max-width: 300px;
            height: 250px;
            border-radius: 8px;
            background: #f0f0f0;
            object-fit: cover;
        }
        #canvas {
            display: none;
        }
        .camera-controls {
            margin: 15px 0;
        }
        .camera-btn {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        .camera-btn.capture {
            background: #007bff;
            color: white;
        }
        .camera-btn.retake {
            background: #6c757d;
            color: white;
        }
        .captured-photo {
            max-width: 300px;
            border-radius: 8px;
            margin: 10px 0;
        }

        #map {
            height: 400px;
            width: 100%;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .location-info {
            background: rgba(255,255,255,0.90);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .punch-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        .punch-btn {
            padding: 15px 30px;
            border: none;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 150px;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }
        .punch-btn.punch-in {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .punch-btn.punch-out {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: white;
        }
        .punch-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .punch-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Attendance History */
        .attendance-history {
            background: rgba(255,255,255,0.90);
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }
        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            background: rgba(63, 79, 216, 0.05);
            border-radius: 8px;
            border-left: 4px solid #3f4fd8;
        }
        .history-time {
            font-weight: bold;
            color: #19336a;
        }
        .history-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
        }
        .history-details {
            flex: 1;
            margin: 0 15px;
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
            background-color: rgba(0,0,0,0.9);
        }
        .modal-content {
            display: block;
            margin: auto;
            max-width: 80%;
            max-height: 80%;
            margin-top: 5%;
        }
        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .loading {
            text-align: center;
            color: #19336a;
            font-style: italic;
        }
        
        /* Location Permission Popup */
        .permission-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .permission-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            max-width: 400px;
            margin: 20px;
        }
        .permission-icon {
            font-size: 3rem;
            color: #3f4fd8;
            margin-bottom: 20px;
        }
        
        @media (max-width: 600px) {
            .container {
                margin: 10px;
                padding: 15px;
            }
            .punch-buttons {
                flex-direction: column;
                align-items: center;
            }
            .history-item {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
        }
        @media (max-width:600px){
            .header { gap:12px;}
            .company-logo { height: 40px;}
            .branch-buttons { gap:16px; }
            .branch-btn { width: 80px; height: 80px; font-size:0.82rem;}
            .welcome-message { padding: 10px;}
            .assigned-badge { width: 16px; height: 16px; font-size: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Punch Attendance</h1>
                        <img src="aayu.png" alt="Company Logo" class="company-logo">

        </div>
<div style="text-align: center; margin-bottom: 20px;">
  <a href="logout.php" 
     style="
       display: inline-block; 
       padding: 10px 20px; 
       background-color: #e04949; 
       color: white; 
       font-weight: bold; 
       border-radius: 6px; 
       text-decoration: none;
       transition: background-color 0.3s ease;
       margin-right: 10px;
     "
     onmouseover="this.style.backgroundColor='#b03a3a';"
     onmouseout="this.style.backgroundColor='#e04949';"
  >
      Logout 
  </a>

  <a href="employee-leave-application.php" 
     style="
       display: inline-block; 
       padding: 10px 20px; 
       background-color: #e04949; 
       color: white; 
       font-weight: bold; 
       border-radius: 6px; 
       text-decoration: none;
       transition: background-color 0.3s ease;
     "
     onmouseover="this.style.backgroundColor='#b03a3a';"
     onmouseout="this.style.backgroundColor='#e04949';"
  >
      Apply leave
  </a>


  <a href="employee-leave-history.php" 
     style="
       display: inline-block; 
       padding: 10px 20px; 
       background-color: #e04949; 
       color: white; 
       font-weight: bold; 
       border-radius: 6px; 
       text-decoration: none;
       transition: background-color 0.3s ease;
     "
     onmouseover="this.style.backgroundColor='#b03a3a';"
     onmouseout="this.style.backgroundColor='#e04949';"
  >
      leave Status
  </a>

    <a href="employee-attendance-record.php" 
     style="
       display: inline-block; 
       padding: 10px 20px; 
       background-color: #e04949; 
       color: white; 
       font-weight: bold; 
       border-radius: 6px; 
       text-decoration: none;
       transition: background-color 0.3s ease;
     "
     onmouseover="this.style.backgroundColor='#b03a3a';"
     onmouseout="this.style.backgroundColor='#e04949';"
  >
    📊 Attendance Record
  </a>


</div>



        <div class="user-info">
            <h2>Welcome, <?php echo $fullName; ?>!</h2>
            <p>Branch: <?php echo htmlspecialchars($branch); ?></p>
            <p>Date: <?php echo date('l, F j, Y'); ?></p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="attendance-status">
            <h3 style="text-align: center; color: #19336a;">Today's Attendance</h3>
            <div class="status-grid">
                <div class="status-item">
                    <div class="status-label">Check In</div>
                    <div class="status-value">
                        <?php echo $todayAttendance && $todayAttendance['punch_in_datetime'] 
                            ? date('H:i:s', strtotime($todayAttendance['punch_in_datetime']))
                            : 'Not punched'; ?>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-label">Check Out</div>
                    <div class="status-value">
                        <?php echo $todayAttendance && $todayAttendance['punch_out_datetime'] 
                            ? date('H:i:s', strtotime($todayAttendance['punch_out_datetime']))
                            : 'Not punched'; ?>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-label">Status</div>
                    <div class="status-value">
                        <?php 
                        if ($todayAttendance) {
                            if ($todayAttendance['punch_out_datetime']) {
                                echo 'Completed';
                            } else {
                                echo 'Working';
                            }
                        } else {
                            echo 'Not started';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Camera Section -->
        <div class="camera-section">
            <h3 style="color: #19336a; margin-bottom: 20px;">
                <i class="fas fa-camera"></i> Take Photo for Attendance
            </h3>
            <div id="cameraContainer">
                <video id="video" autoplay muted playsinline></video>
                <canvas id="canvas"></canvas>
                <div class="camera-controls">
                    <button type="button" id="startCamera" class="camera-btn capture">
                        <i class="fas fa-video"></i> Start Camera
                    </button>
                    <button type="button" id="capturePhoto" class="camera-btn capture" style="display: none;">
                        <i class="fas fa-camera"></i> Capture Photo
                    </button>
                    <button type="button" id="retakePhoto" class="camera-btn retake" style="display: none;">
                        <i class="fas fa-redo"></i> Retake
                    </button>
                </div>
                <img id="capturedImage" class="captured-photo" style="display: none;">
            </div>
        </div>

        <?php if ($branchLocation && $branchLocation['latitude'] && $branchLocation['longitude']): ?>
        <div id="map"></div>
        
        <div class="location-info">
            <div class="loading" id="locationStatus">Getting your location...</div>
            <div id="distanceInfo" style="display: none;"></div>
        </div>

        <form method="POST" id="attendanceForm">
            <input type="hidden" name="latitude" id="userLatitude">
            <input type="hidden" name="longitude" id="userLongitude">
            <input type="hidden" name="photo" id="photoData">
            
            <div class="punch-buttons">
                <button type="submit" name="action" value="punch_in" class="punch-btn punch-in" 
                        id="punchInBtn" disabled
                        <?php echo ($todayAttendance ? 'style="display:none;"' : ''); ?>>
                    <i class="fas fa-sign-in-alt"></i> Punch In
                </button>
                
                <button type="submit" name="action" value="punch_out" class="punch-btn punch-out" 
                        id="punchOutBtn" disabled
                        <?php echo ($todayAttendance && !$todayAttendance['punch_out_datetime'] ? '' : 'style="display:none;"'); ?>>
                    <i class="fas fa-sign-out-alt"></i> Punch Out
                </button>
            </div>
        </form>
        <?php else: ?>
        <div class="message error">
            Branch location is not configured. Please contact your administrator.
        </div>
        <?php endif; ?>
        
        <!-- Attendance History -->
        <?php if ($todayAttendance): ?>
        <div class="attendance-history">
            <h3 style="text-align: center; color: #19336a; margin-bottom: 20px;">
                <i class="fas fa-history"></i> Today's Records
            </h3>
            
            <?php if ($todayAttendance['punch_in_datetime']): ?>
            <div class="history-item">
                <div class="history-details">
                    <div class="history-time">
                        <i class="fas fa-sign-in-alt" style="color: #28a745;"></i>
                        Punch In: <?php echo date('h:i A', strtotime($todayAttendance['punch_in_datetime'])); ?>
                    </div>
                    <small><?php echo date('F j, Y', strtotime($todayAttendance['punch_in_datetime'])); ?></small>
                </div>
                <?php if (!empty($todayAttendance['punch_in_photo'])): ?>
                <img src="uploads/attendance_photos/<?php echo $todayAttendance['punch_in_photo']; ?>" 
                     class="history-photo" onclick="showModal(this.src)" alt="Punch In Photo">
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($todayAttendance['punch_out_datetime']): ?>
            <div class="history-item">
                <div class="history-details">
                    <div class="history-time">
                        <i class="fas fa-sign-out-alt" style="color: #dc3545;"></i>
                        Punch Out: <?php echo date('h:i A', strtotime($todayAttendance['punch_out_datetime'])); ?>
                    </div>
                    <small><?php echo date('F j, Y', strtotime($todayAttendance['punch_out_datetime'])); ?></small>
                </div>
                <?php if (!empty($todayAttendance['punch_out_photo'])): ?>
                <img src="uploads/attendance_photos/<?php echo $todayAttendance['punch_out_photo']; ?>" 
                     class="history-photo" onclick="showModal(this.src)" alt="Punch Out Photo">
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Location Permission Popup -->
    <div id="permissionPopup" class="permission-popup">
        <div class="permission-content">
            <div class="permission-icon">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <h3>Location Access Required</h3>
            <p>Please allow location access to mark your attendance. This helps verify you're at the correct workplace.</p>
            <button onclick="requestLocationPermission()" class="punch-btn punch-in" style="margin: 10px;">
                <i class="fas fa-location-arrow"></i> Allow Location
            </button>
        </div>
    </div>

    <!-- Photo Modal -->
    <div id="photoModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Global variables
        let map = null;
        let userMarker = null; 
        let branchCircle = null;
        let userLocation = null;
        let videoStream = null;
        let capturedPhotoData = null;
        let withinRange = false;
        
        // Branch location data
        <?php if ($branchLocation && $branchLocation['latitude']): ?>
        const branchLat = <?php echo $branchLocation['latitude']; ?>;
        const branchLng = <?php echo $branchLocation['longitude']; ?>;
        const branchRadius = <?php echo $branchLocation['radius']; ?>;
        const branchName = <?php echo json_encode($branchLocation['name']); ?>;
        <?php endif; ?>

        // Initialize map
        function initMap() {
            if (!document.getElementById('map') || !window.L) return;
            
            try {
                map = L.map('map').setView([branchLat, branchLng], 16);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                // Add branch marker
                L.marker([branchLat, branchLng])
                    .addTo(map)
                    .bindPopup('<b>' + branchName + '</b><br>Branch Location')
                    .openPopup();

                // Add radius circle
                branchCircle = L.circle([branchLat, branchLng], {
                    color: 'red',
                    fillColor: '#f03',
                    fillOpacity: 0.2,
                    radius: branchRadius
                }).addTo(map);

                branchCircle.bindPopup('Allowed area: ' + branchRadius + 'm radius');
            } catch (error) {
                console.error('Map initialization error:', error);
            }
        }

        // Location Permission
        function requestLocationPermission() {
            document.getElementById('permissionPopup').style.display = 'none';
            getUserLocation();
        }

        // Get user location
        function getUserLocation() {
            if (!navigator.geolocation) {
                document.getElementById('locationStatus').innerHTML = 
                    '<span style="color: red;">Geolocation not supported by this browser.</span>';
                showLocationPopup();
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const accuracy = position.coords.accuracy;

                    userLocation = {lat: lat, lng: lng};
                    
                    // Update form fields
                    document.getElementById('userLatitude').value = lat;
                    document.getElementById('userLongitude').value = lng;

                    // Add user marker if map is available
                    if (map && window.L) {
                        try {
                            if (userMarker) {
                                map.removeLayer(userMarker);
                            }
                            
                            userMarker = L.marker([lat, lng], {
                                icon: L.icon({
                                    iconUrl: 'data:image/svg+xml;base64,' + btoa(`
                                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="41" viewBox="0 0 25 41">
                                            <path fill="#007cff" d="M12.5 0C5.6 0 0 5.6 0 12.5c0 12.5 12.5 28.5 12.5 28.5s12.5-16 12.5-28.5C25 5.6 19.4 0 12.5 0z"/>
                                            <circle cx="12.5" cy="12.5" r="7.5" fill="white"/>
                                        </svg>
                                    `),
                                    iconSize: [25, 41],
                                    iconAnchor: [12, 41]
                                })
                            }).addTo(map);

                            userMarker.bindPopup('Your Location');

                            // Center map to show both locations
                            const group = new L.featureGroup([userMarker, L.marker([branchLat, branchLng])]);
                            map.fitBounds(group.getBounds().pad(0.1));
                        } catch (error) {
                            console.error('User marker error:', error);
                        }
                    }

                    // Calculate distance
                    const distance = calculateDistance(branchLat, branchLng, lat, lng);
                    
                    // Update UI
                    updateLocationStatus(distance, accuracy);
                },
                function(error) {
                    console.error('Location error:', error);
                    document.getElementById('locationStatus').innerHTML = 
                        '<span style="color: red;">Location access denied. Please enable location services.</span>';
                    showLocationPopup();
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 60000
                }
            );
        }

        // Show location permission popup
        function showLocationPopup() {
            document.getElementById('permissionPopup').style.display = 'flex';
        }

        // Calculate distance between two points
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000; // Earth's radius in meters
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                     Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                     Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        // Update location status
        function updateLocationStatus(distance, accuracy) {
            const statusEl = document.getElementById('locationStatus');
            const distanceEl = document.getElementById('distanceInfo');

            if (!statusEl || !distanceEl) return;

            statusEl.innerHTML = 'Location found (Accuracy: ±' + Math.round(accuracy) + 'm)';
            
            distanceEl.style.display = 'block';
            distanceEl.innerHTML = 'Distance from branch: <strong>' + Math.round(distance) + 'm</strong> (Max allowed: ' + branchRadius + 'm)';

            if (distance <= branchRadius) {
                distanceEl.innerHTML += ' <span style="color: green;">✓ Within range</span>';
                distanceEl.style.color = 'green';
                withinRange = true;
                updatePunchButtons();
            } else {
                distanceEl.innerHTML += ' <span style="color: red;">✗ Outside range</span>';
                distanceEl.style.color = 'red';
                withinRange = false;
                updatePunchButtons();
            }
        }

        // Camera Functions
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const capturedImage = document.getElementById('capturedImage');
        const startCameraBtn = document.getElementById('startCamera');
        const captureBtn = document.getElementById('capturePhoto');
        const retakeBtn = document.getElementById('retakePhoto');

        if (startCameraBtn) {
            startCameraBtn.addEventListener('click', async () => {
                try {
                    videoStream = await navigator.mediaDevices.getUserMedia({ 
                        video: { 
                            width: 300, 
                            height: 250,
                            facingMode: 'user' // Front camera
                        } 
                    });
                    if (video) {
                        video.srcObject = videoStream;
                        video.style.display = 'block';
                        startCameraBtn.style.display = 'none';
                        if (captureBtn) captureBtn.style.display = 'inline-block';
                        if (capturedImage) capturedImage.style.display = 'none';
                        if (retakeBtn) retakeBtn.style.display = 'none';
                    }
                } catch (error) {
                    console.error('Camera error:', error);
                    alert('Unable to access camera. Please allow camera permissions.');
                }
            });
        }

        if (captureBtn) {
            captureBtn.addEventListener('click', () => {
                if (!video || !canvas) return;
                
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0);
                
                // Convert to base64
                capturedPhotoData = canvas.toDataURL('image/jpeg', 0.8);
                
                // Show captured image
                if (capturedImage) {
                    capturedImage.src = capturedPhotoData;
                    capturedImage.style.display = 'block';
                }
                
                // Hide video and show retake button
                video.style.display = 'none';
                captureBtn.style.display = 'none';
                if (retakeBtn) retakeBtn.style.display = 'inline-block';
                
                // Stop camera stream
                if (videoStream) {
                    videoStream.getTracks().forEach(track => track.stop());
                    videoStream = null;
                }
                
                // Update form data
                const photoDataInput = document.getElementById('photoData');
                if (photoDataInput) photoDataInput.value = capturedPhotoData;
                
                // Update punch buttons
                updatePunchButtons();
            });
        }

        if (retakeBtn) {
            retakeBtn.addEventListener('click', () => {
                capturedPhotoData = null;
                const photoDataInput = document.getElementById('photoData');
                if (photoDataInput) photoDataInput.value = '';
                if (capturedImage) capturedImage.style.display = 'none';
                if (startCameraBtn) startCameraBtn.style.display = 'inline-block';
                retakeBtn.style.display = 'none';
                updatePunchButtons();
            });
        }

        // Update punch buttons based on conditions
        function updatePunchButtons() {
            const punchInBtn = document.getElementById('punchInBtn');
            const punchOutBtn = document.getElementById('punchOutBtn');
            
            if (!punchInBtn && !punchOutBtn) return;
            
            const hasLocation = userLocation !== null;
            const hasPhoto = capturedPhotoData !== null;
            const allConditionsMet = hasLocation && hasPhoto && withinRange;

            if (punchInBtn && punchInBtn.style.display !== 'none') {
                punchInBtn.disabled = !allConditionsMet;
            }
            
            if (punchOutBtn && punchOutBtn.style.display !== 'none') {
                punchOutBtn.disabled = !allConditionsMet;
            }
        }

        // Photo Modal Functions
        function showModal(src) {
            const modal = document.getElementById('photoModal');
            const modalImg = document.getElementById('modalImage');
            if (modal && modalImg) {
                modal.style.display = 'block';
                modalImg.src = src;
            }
        }

        function closeModal() {
            const modal = document.getElementById('photoModal');
            if (modal) modal.style.display = 'none';
        }

        // Form Submission Validation
        const attendanceForm = document.getElementById('attendanceForm');
        if (attendanceForm) {
            attendanceForm.addEventListener('submit', function(e) {
                const hasLocation = document.getElementById('userLatitude').value && 
                                   document.getElementById('userLongitude').value;
                const hasPhoto = document.getElementById('photoData').value;
                
                if (!hasLocation) {
                    e.preventDefault();
                    alert('Location is required for attendance. Please enable location services.');
                    return;
                }
                
                if (!hasPhoto) {
                    e.preventDefault();
                    alert('Photo is required for attendance. Please take a photo first.');
                    return;
                }
                
                if (!withinRange) {
                    e.preventDefault();
                    alert('You are outside the allowed range. Please move closer to the branch location.');
                    return;
                }
            });
        }

        // Initialize everything when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map if branch location is available
            <?php if ($branchLocation && $branchLocation['latitude']): ?>
            setTimeout(() => {
                initMap();
                getUserLocation();
            }, 500);
            
            // Refresh location every 30 seconds
            setInterval(getUserLocation, 30000);
            <?php endif; ?>
            
            // Initial button state update
            updatePunchButtons();
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('photoModal');
            if (event.target === modal) {
                closeModal();
            }
            
            const popup = document.getElementById('permissionPopup');
            if (event.target === popup) {
                popup.style.display = 'none';
            }
        });

        // Global functions for onclick events
        window.showModal = showModal;
        window.closeModal = closeModal;
        window.requestLocationPermission = requestLocationPermission;
    </script>
</body>
</html>
