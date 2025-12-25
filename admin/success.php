<?php
$action = $_GET['action'] ?? 'unknown';

if ($action === 'punchin') {
    $message = "✅ Punch-in successful!";
} elseif ($action === 'punchout') {
    $message = "✅ Punch-out successful!";
} else {
    $message = "⚠️ Unknown action";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Success</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 100px;
            background: #f0f4f8;
        }
        h2 {
            font-size: 24px;
            padding: 20px;
            border-radius: 8px;
            display: inline-block;
            background: #e0ffe0;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <h2><?= $message ?></h2>
</body>
</html>
