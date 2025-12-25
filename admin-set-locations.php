<?php
session_start();

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
$branches = [];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $branchId = $_POST['branch_id'];
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
        $radius = floatval($_POST['radius']);
        
        if ($latitude && $longitude && $radius) {
            $updateStmt = $pdo->prepare("UPDATE branches SET latitude = ?, longitude = ?, radius = ? WHERE id = ?");
            $result = $updateStmt->execute([$latitude, $longitude, $radius, $branchId]);
            
            if ($result) {
                $message = "Branch location updated successfully!";
            } else {
                $message = "Failed to update branch location.";
            }
        } else {
            $message = "Please provide valid coordinates and radius.";
        }
    }
    
    // Get all branches
    $branchStmt = $pdo->prepare("SELECT * FROM branches ORDER BY name");
    $branchStmt->execute();
    $branches = $branchStmt->fetchAll();
    
} catch (\PDOException $e) {
    $message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Branch Locations</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #3f4fd8ff 0%, #32670cff 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255,255,255,0.95);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .instructions {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 5px solid #2196f3;
        }
        .instructions h3 {
            margin-top: 0;
            color: #1976d2;
        }
        .instructions ul {
            margin-bottom: 0;
        }
        .instructions li {
            margin-bottom: 8px;
        }
        #map {
            height: 400px;
            margin-bottom: 30px;
            border-radius: 10px;
            border: 3px solid #ddd;
        }
        .clicked-coords {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
            text-align: center;
            display: none;
        }
        .branch-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }
        .branch-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        .branch-card:hover {
            border-color: #007bff;
            box-shadow: 0 4px 15px rgba(0,123,255,0.1);
        }
        .branch-name {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.2em;
            text-align: center;
            padding: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #495057;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        .btn {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,123,255,0.3);
        }
        .current-location {
            background: #e8f5e8;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 0.9em;
            color: #28a745;
        }
        .copy-coords {
            background: #17a2b8;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
        }
        .copy-coords:hover {
            background: #138496;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗺️ Branch Location Setup</h1>
            <p>Configure branch locations for attendance tracking</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'success') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="instructions">
            <h3>📍 How to Set Branch Locations:</h3>
            <ul>
                <li><strong>Click on the map</strong> where the branch is located</li>
                <li><strong>Copy coordinates</strong> from the popup that appears</li>
                <li><strong>Paste coordinates</strong> into the respective branch form below</li>
                <li><strong>Set radius</strong> (recommended: 20-50 meters)</li>
                <li><strong>Save</strong> the location for each branch</li>
            </ul>
        </div>

        <div id="map"></div>

        <div class="clicked-coords" id="clickedCoords">
            <strong>Clicked Location:</strong>
            <span id="coordsDisplay"></span>
            <button type="button" class="copy-coords" onclick="copyCoords()">Copy Coordinates</button>
        </div>

        <div class="branch-grid">
            <?php foreach ($branches as $branch): ?>
            <div class="branch-card">
                <div class="branch-name"><?php echo htmlspecialchars($branch['name']); ?></div>
                
                <?php if ($branch['latitude'] && $branch['longitude']): ?>
                    <div class="current-location">
                        ✅ <strong>Current Location:</strong><br>
                        Lat: <?php echo $branch['latitude']; ?><br>
                        Lng: <?php echo $branch['longitude']; ?><br>
                        Radius: <?php echo $branch['radius']; ?>m
                    </div>
                <?php else: ?>
                    <div style="color: #dc3545; font-weight: 500; margin-bottom: 15px;">
                        ❌ Location not set
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="branch_id" value="<?php echo $branch['id']; ?>">
                    
                    <div class="form-group">
                        <label>📍 Latitude:</label>
                        <input type="number" step="any" name="latitude" 
                               value="<?php echo $branch['latitude']; ?>" 
                               placeholder="e.g., 28.6139" required>
                    </div>
                    
                    <div class="form-group">
                        <label>📍 Longitude:</label>
                        <input type="number" step="any" name="longitude" 
                               value="<?php echo $branch['longitude']; ?>" 
                               placeholder="e.g., 77.2090" required>
                    </div>
                    
                    <div class="form-group">
                        <label>📏 Radius (meters):</label>
                        <input type="number" name="radius" 
                               value="<?php echo $branch['radius'] ?: 20; ?>" 
                               placeholder="20" min="5" max="200" required>
                    </div>
                    
                    <button type="submit" class="btn">💾 Save Location</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let map;
        let clickedLat = null;
        let clickedLng = null;

        // Initialize map (Delhi center)
        function initMap() {
            map = L.map('map').setView([28.6139, 77.2090], 12);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Add existing branch markers
            <?php foreach ($branches as $branch): ?>
                <?php if ($branch['latitude'] && $branch['longitude']): ?>
                    L.marker([<?php echo $branch['latitude']; ?>, <?php echo $branch['longitude']; ?>])
                        .addTo(map)
                        .bindPopup('<b><?php echo addslashes($branch['name']); ?></b><br>Radius: <?php echo $branch['radius']; ?>m');
                    
                    L.circle([<?php echo $branch['latitude']; ?>, <?php echo $branch['longitude']; ?>], {
                        color: 'green',
                        fillColor: '#90EE90',
                        fillOpacity: 0.3,
                        radius: <?php echo $branch['radius']; ?>
                    }).addTo(map);
                <?php endif; ?>
            <?php endforeach; ?>

            // Click event
            map.on('click', function(e) {
                clickedLat = e.latlng.lat.toFixed(6);
                clickedLng = e.latlng.lng.toFixed(6);
                
                // Show coordinates
                document.getElementById('clickedCoords').style.display = 'block';
                document.getElementById('coordsDisplay').innerHTML = 
                    `Lat: ${clickedLat}, Lng: ${clickedLng}`;
                
                // Add temporary marker
                if (window.tempMarker) {
                    map.removeLayer(window.tempMarker);
                }
                
                window.tempMarker = L.marker([clickedLat, clickedLng], {
                    icon: L.icon({
                        iconUrl: 'data:image/svg+xml;base64,' + btoa(`
                            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="41" viewBox="0 0 25 41">
                                <path fill="#ff0000" d="M12.5 0C5.6 0 0 5.6 0 12.5c0 12.5 12.5 28.5 12.5 28.5s12.5-16 12.5-28.5C25 5.6 19.4 0 12.5 0z"/>
                                <circle cx="12.5" cy="12.5" r="7.5" fill="white"/>
                            </svg>
                        `),
                        iconSize: [25, 41],
                        iconAnchor: [12, 41]
                    })
                }).addTo(map);
                
                window.tempMarker.bindPopup(`
                    <strong>Clicked Location</strong><br>
                    Lat: ${clickedLat}<br>
                    Lng: ${clickedLng}<br>
                    <button onclick="copyToForms(${clickedLat}, ${clickedLng})">Use This Location</button>
                `).openPopup();
            });
        }

        // Copy coordinates to clipboard
        function copyCoords() {
            const coords = `${clickedLat}, ${clickedLng}`;
            navigator.clipboard.writeText(coords).then(() => {
                alert('Coordinates copied to clipboard!');
            });
        }

        // Copy to all forms
        function copyToForms(lat, lng) {
            const latInputs = document.querySelectorAll('input[name="latitude"]');
            const lngInputs = document.querySelectorAll('input[name="longitude"]');
            
            // You can modify this to copy to specific form instead of all
            if (confirm('Copy these coordinates to the first empty form?')) {
                for (let i = 0; i < latInputs.length; i++) {
                    if (!latInputs[i].value) {
                        latInputs[i].value = lat;
                        lngInputs[i].value = lng;
                        latInputs[i].focus();
                        break;
                    }
                }
            }
        }

        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
        });
    </script>
</body>
</html>