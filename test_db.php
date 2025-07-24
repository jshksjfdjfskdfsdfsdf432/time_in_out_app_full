<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "time_app";
$port = 8889;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
} else {
    echo "✅ Connected successfully to MySQL and database!";
}
?>
