<?php
@date_default_timezone_set('Asia/Manila');

// InfinityFree MySQL credentials
$dbHost = 'sql306.infinityfree.com';
$dbUser = 'if0_39554433'; // ✅ Replace if different
$dbPass = 'YOUR_PASSWORD_HERE'; // 🔐 Replace with your real password
$dbName = 'if0_39554433_time_app';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
