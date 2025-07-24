<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $pin  = trim($_POST['pin'] ?? '');

    if ($name === '' || $pin === '') {
        $error = "Both name and PIN are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO workers (name, pin) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $pin);
        $stmt->execute();
        header("Location: admin.php?msg=" . urlencode("Worker registered."));
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title>Register Worker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="col-md-6 mx-auto bg-white p-4 rounded shadow">
    <h4 class="mb-3">Register Worker</h4>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="mb-3">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" required />
      </div>
      <div class="mb-3">
        <label for="pin" class="form-label">PIN</label>
        <input type="text" name="pin" class="form-control" required />
      </div>
      <button type="submit" class="btn btn-primary">Register</button>
    </form>
  </div>
</div>
</body>
</html>
