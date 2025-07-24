<?php
$host = 'localhost';
$user = 'root';
$pass = 'root';
$db = 'time_app';
$port = 8889;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>