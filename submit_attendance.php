<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $worker_id = $_POST['worker_id'];
    $pin = $_POST['pin'];
    $action = $_POST['action']; // "in" or "out"
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;

    // Validate photo
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        exit("❌ Photo is required for attendance.");
    }

    // Validate worker and PIN
    $stmt = $conn->prepare("SELECT * FROM workers WHERE id = ? AND pin = ?");
    $stmt->bind_param("is", $worker_id, $pin);
    $stmt->execute();
    $worker = $stmt->get_result()->fetch_assoc();

    if (!$worker) {
        exit("❌ Invalid worker or PIN.");
    }

    $date_today = date('Y-m-d');

    // Check for existing entry
    $stmt = $conn->prepare("SELECT * FROM attendance_logs WHERE worker_id = ? AND DATE(time_in) = ?");
    $stmt->bind_param("is", $worker_id, $date_today);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    // Logic: Prevent double in/out
    if ($action === 'in') {
        if ($existing) {
            exit("❌ You've already Time In today.");
        }
    } elseif ($action === 'out') {
        if (!$existing) {
            exit("❌ You haven't Time In yet.");
        }
        if ($existing['time_out']) {
            exit("❌ You've already Time Out today.");
        }
    }

    // Save photo
    $photo_name = time() . '_' . basename($_FILES["photo"]["name"]);
    $target_dir = "uploads/";
    $target_file = $target_dir . $photo_name;
    move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);

    if ($action === 'in') {
        $stmt = $conn->prepare("INSERT INTO attendance_logs (worker_id, time_in, latitude, longitude, photo) VALUES (?, NOW(), ?, ?, ?)");
        $stmt->bind_param("idds", $worker_id, $latitude, $longitude, $photo_name);
    } else {
        $stmt = $conn->prepare("UPDATE attendance_logs SET time_out = NOW(), latitude = ?, longitude = ?, photo = ? WHERE id = ?");
        $log_id = $existing['id'];
        $stmt->bind_param("ddsi", $latitude, $longitude, $photo_name, $log_id);
    }

    if ($stmt->execute()) {
        echo "✅ Time " . ucfirst($action) . " recorded successfully.";
    } else {
        echo "❌ Something went wrong.";
    }
}
?>
