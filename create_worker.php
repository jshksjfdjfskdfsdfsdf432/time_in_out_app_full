<?php
/**
 * create_worker.php (final, corrected)
 * Adds a new worker with extended info.
 */
require_once 'db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}

function back($msg) {
    header('Location: Admin.php?msg=' . urlencode($msg));
    exit();
}

$name              = trim($_POST['name'] ?? '');
$pin               = trim($_POST['pin'] ?? '');
$rate_per_hr_raw   = trim($_POST['rate_per_hr'] ?? '');
$skill_type        = trim($_POST['skill_type'] ?? '');
$address           = trim($_POST['address'] ?? '');
$phone_number      = trim($_POST['phone_number'] ?? '');
$age_raw           = trim($_POST['age'] ?? '');
$emergency_contact = trim($_POST['emergency_contact'] ?? '');
$emergency_phone   = trim($_POST['emergency_phone'] ?? '');

$errors = [];
if ($name === '') $errors[] = 'Name';
if ($pin === '' || !preg_match('/^\\d{4}$/', $pin)) $errors[] = '4-digit PIN';
if ($rate_per_hr_raw === '' || !is_numeric($rate_per_hr_raw)) $errors[] = 'Rate/hr';
if ($skill_type === '') $errors[] = 'Skill';
if ($address === '') $errors[] = 'Address';
if ($phone_number === '') $errors[] = 'Phone';
if ($age_raw === '' || !ctype_digit($age_raw) || (int)$age_raw <= 0) $errors[] = 'Age';
if ($emergency_contact === '') $errors[] = 'Emergency Contact';
if ($emergency_phone === '') $errors[] = 'Emergency Phone';

if ($errors) {
    back('Missing/invalid: ' . implode(', ', $errors));
}

$rate_per_hr = (float)$rate_per_hr_raw;
$age         = (int)$age_raw;

$sql = "
    INSERT INTO workers
      (name, pin, rate_per_hr, skill_type, address, phone_number, age, emergency_contact, emergency_phone)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    back('DB prepare error: ' . $conn->error);
}

$stmt->bind_param(
    'ssdsssiss',  // 9 letters: s s d s s s i s s
    $name,
    $pin,
    $rate_per_hr,
    $skill_type,
    $address,
    $phone_number,
    $age,
    $emergency_contact,
    $emergency_phone
);

if (!$stmt->execute()) {
    back('DB exec error: ' . $stmt->error);
}

$stmt->close();
back('Worker added successfully');
