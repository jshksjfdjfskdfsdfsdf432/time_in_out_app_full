<?php
require_once 'db.php';
date_default_timezone_set('Asia/Manila');

function redirectMsg($msg) {
    header('Location: punch.php?msg=' . urlencode($msg));
    exit();
}

function saveDataUrlImage(?string $dataUrl, int $workerId, string $phase): ?string {
    if (!$dataUrl || !str_starts_with($dataUrl, 'data:image/')) return null;
    $parts = explode(',', $dataUrl, 2);
    if (count($parts) !== 2) return null;
    $binary = base64_decode($parts[1]);
    if ($binary === false) return null;

    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);

    $fileName = sprintf('punch_%d_%s_%s.png', $workerId, date('Ymd_His'), $phase);
    $fullPath = $uploadDir . '/' . $fileName;
    if (file_put_contents($fullPath, $binary) === false) return null;

    return 'uploads/' . $fileName;
}

// Validate input
$pin = trim($_POST['pin'] ?? '');
$lat = trim($_POST['latitude'] ?? '');
$lon = trim($_POST['longitude'] ?? '');
$photoData = $_POST['photo'] ?? '';
$actionType = trim($_POST['action_type'] ?? '');

if ($pin === '') redirectMsg('PIN is required.');

// Get worker
$stmt = $conn->prepare('SELECT id, name FROM workers WHERE pin = ?');
$stmt->bind_param('s', $pin);
$stmt->execute();
$worker = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$worker) {
    redirectMsg('Invalid PIN.');
}

$workerId = (int)$worker['id'];
$name = $worker['name'];

// Get latest open shift (if any)
$stmt = $conn->prepare("SELECT * FROM attendance_logs WHERE worker_id = ? AND time_out IS NULL ORDER BY time_in DESC LIMIT 1");
$stmt->bind_param("i", $workerId);
$stmt->execute();
$openLog = $stmt->get_result()->fetch_assoc();
$stmt->close();

$validBreaks = [
    'break_start' => 'break_start',
    'break_end'   => 'break_end'
];

if (isset($validBreaks[$actionType])) {
    if (!$openLog) redirectMsg('You must Time In before recording a break.');
    $col = $validBreaks[$actionType];
    $photoPath = saveDataUrlImage($photoData, $workerId, $col);

    $sql = "UPDATE attendance_logs SET {$col} = NOW(), latitude_{$col} = ?, longitude_{$col} = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $lat, $lon, $openLog['id']);
    $stmt->execute();
    $stmt->close();
    redirectMsg("$name break recorded ($col).");
}

if ($actionType === 'time_out') {
    if (!$openLog) redirectMsg('No open shift to clock out.');
    $photoPath = saveDataUrlImage($photoData, $workerId, 'out');
    $stmt = $conn->prepare("UPDATE attendance_logs SET time_out = NOW(), latitude_out = ?, longitude_out = ?, photo_out = ? WHERE id = ?");
    $stmt->bind_param('sssi', $lat, $lon, $photoPath, $openLog['id']);
    $stmt->execute();
    $stmt->close();
    redirectMsg("Goodbye $name! Time Out recorded.");
}

// Time In (default)
if ($openLog) redirectMsg('Already clocked in. Use Time Out.');

$photoPath = saveDataUrlImage($photoData, $workerId, 'in');
$stmt = $conn->prepare("INSERT INTO attendance_logs (worker_id, time_in, latitude_in, longitude_in, photo_in) VALUES (?, NOW(), ?, ?, ?)");
$stmt->bind_param('isss', $workerId, $lat, $lon, $photoPath);
$stmt->execute();
$stmt->close();
redirectMsg("Welcome $name! Time In recorded.");
