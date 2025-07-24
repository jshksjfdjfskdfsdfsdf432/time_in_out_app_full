<?php
// Optional: set default timezone
@date_default_timezone_set('Asia/Manila');

// Fetch DB credentials from environment variables (for Vercel or .env file)
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'time_in_out_db';

// Create the database connection
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

// Check for connection errors
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
