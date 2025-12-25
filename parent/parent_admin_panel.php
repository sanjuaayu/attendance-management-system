<?php
session_start();
include 'config.php';

// Branches fetch karein
$branchQuery = "SELECT id, name FROM branches ORDER BY name ASC";
$branchResult = $conn->query($branchQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Admin Panel</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            width: 100%;
            max-width: 450px;
            margin: 50px auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
        }
        .company-logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        label {
            font-weight: bold;
            display: block;
            margin: 15px 0 5px;
            text-align: left;
            color: #555;
        }
        select, input[type="date"] {
            width: 100%;
            padding: 14px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            font-size: 16px;
        }
        
        /* ============ CALENDAR SIZE FIX ============ */
        /* Date input ko bada karne ke liye */
        input[type="date"] {
            height: 50px;
            font-size: 18px;
            padding: 14px 12px;
            cursor: pointer;
        }
        
        /* Chrome, Edge, Safari ke liye */
        input[type="date"]::-webkit-calendar-picker-indicator {
            font-size: 24px;
            cursor: pointer;
            padding: 5px;
        }
        
        /* Mobile devices pe calendar size */
        @media (max-width: 768px) {
            input[type="date"] {
                height: 55px;
                font-size: 20px;
            }
            
            input[type="date"]::-webkit-calendar-picker-indicator {
                font-size: 28px;
            }
        }
        
        /* Touch devices ke liye better hit area */
        @media (hover: none) and (pointer: coarse) {
            input[type="date"] {
                min-height: 60px;
                font-size: 22px;
            }
        }
        
        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: opacity 0.3s ease;
            font-weight: 600;
        }
        button:hover {
            opacity: 0.9;
        }
        button:active {
            transform: scale(0.98);
        }
        .link-btn {
            margin-top: 15px;
            display: block;
            text-align: center;
            background: #e9ecef;
            padding: 14px;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            transition: background-color 0.3s ease;
            font-weight: 600;
        }
        .link-btn:hover {
            background: #dee2e6;
        }
        
        /* Form elements spacing */
        .form-group {
            margin-bottom: 20px;
        }
        
        /* Loading state */
        select:disabled {
            background-color: #f0f2f5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
<div class="container">
    <img src="rupeeq.png" alt="Company Logo" class="company-logo">
    <h2>Parent Admin Panel</h2>

    <form method="POST" action="download_report.php">
        <div class="form-group">
            <label for="branch">Branch Select Karein:</label>
            <select name="branch" id="branch">
                <option value="all">-- All Branches --</option>
                <?php while ($row = $branchResult->fetch_assoc()) { ?>
                    <option value="<?= htmlspecialchars($row['id']) ?>"><?= htmlspecialchars($row['name']) ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="form-group">
            <label for="user">User Select Karein:</label>
            <select name="user" id="user">
                <option value="all">-- Sabhi Branches Ke Liye Applicable --</option>
            </select>
        </div>

        <div class="form-group">
            <label for="start_date">Start Date:</label>
            <input type="date" name="start_date" id="start_date" required>
        </div>

        <div class="form-group">
            <label for="end_date">End Date:</label>
            <input type="date" name="end_date" id="end_date" required>
        </div>

        <button type="submit">Download Report</button>
    </form>

    <a href="logout.php" class="link-btn">Logout</a>
</div>

<script>
$(document).ready(function() {
    // Yeh function select ki gayi branch ke liye users ko fetch karta hai
    function updateUsersDropdown(branchId) {
        $.ajax({
            url: "get_users.php", // Script jo users ki list return karti hai
            type: "POST",
            data: {
                branch_id: branchId
            },
            dataType: 'json', // Ise add karein behtar response handling ke liye
            beforeSend: function() {
                // "Loading..." message dikhayein
                $("#user").html('<option value="">Loading...</option>').prop('disabled', true);
            },
            success: function(users) {
                var options = '<option value="all">-- All Users --</option>';
                if (Array.isArray(users) && users.length > 0) {
                    $.each(users, function(index, user) {
                        // *** YAHAN BADLAV KIYA GAYA HAI: value mein user.id ka istemal karein ***
                        options += '<option value="' + user.id + '">' + user.username + '</option>';
                    });
                } else {
                    options = '<option value="all">-- Is Branch Mein Koi User Nahi Hai --</option>';
                }
                $("#user").html(options).prop('disabled', false);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // AJAX error hone par error message dikhayein
                console.error("AJAX error: ", textStatus, errorThrown);
                console.log("Server response: ", jqXHR.responseText);
                $("#user").html('<option value="all">-- Users load karne mein error --</option>').prop('disabled', false);
            }
        });
    }

    // Jab bhi branch selection change ho, yeh event fire hoga
    $("#branch").on('change', function() {
        var selectedBranchId = $(this).val();
        if (selectedBranchId && selectedBranchId !== "all") {
            updateUsersDropdown(selectedBranchId);
        } else {
            // Yadi "All Branches" chuna gaya hai, to dropdown ko reset karein
            $("#user").html('<option value="all">-- Sabhi Branches Ke Liye Applicable --</option>').prop('disabled', false);
        }
    });

    // Page load hone par "All Branches" ke liye user dropdown ko default state mein rakhein
    $("#user").html('<option value="all">-- Sabhi Branches Ke Liye Applicable --</option>');
    
    // Auto-fill today's date as start and end date
    const today = new Date().toISOString().split('T')[0];
    $('#start_date').val(today);
    $('#end_date').val(today);
});
</script>

</body>
</html>
