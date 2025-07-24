<?php
include 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $worker_id = $_POST['worker_id'];
    $pin = $_POST['pin'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $photo_path = '';

    $stmt = $conn->prepare("SELECT * FROM workers WHERE id = ? AND pin = ?");
    $stmt->bind_param("is", $worker_id, $pin);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result->fetch_assoc()) {
        $message = "Invalid worker or PIN.";
    } else {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $photo_name = time() . '_' . basename($_FILES["photo"]["name"]);
            $target_dir = "uploads/";
            $target_file = $target_dir . $photo_name;
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
            $photo_path = $target_file;
        }

        $today = date("Y-m-d");
        $check = $conn->query("SELECT * FROM attendance_logs WHERE worker_id = $worker_id AND DATE(time_in) = '$today'");
        if ($check->num_rows > 0) {
            $conn->query("UPDATE attendance_logs SET time_out = NOW(), latitude_out = '$latitude', longitude_out = '$longitude', photo_out = '$photo_path' WHERE worker_id = $worker_id AND DATE(time_in) = '$today'");
            $message = "Time-out recorded.";
        } else {
            $conn->query("INSERT INTO attendance_logs (worker_id, time_in, latitude_in, longitude_in, photo_in) VALUES ($worker_id, NOW(), '$latitude', '$longitude', '$photo_path')");
            $message = "Time-in recorded.";
        }
    }
}

$workers = $conn->query("SELECT id, name FROM workers");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Worker Time In/Out</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
<div class="container py-4">
  <h4>Worker Time In/Out</h4>
  <?php if ($message): ?>
    <div class="alert alert-info"><?= $message ?></div>
  <?php endif; ?>
  <form method="POST" enctype="multipart/form-data" onsubmit="return getLocation();">
    <div class="mb-3">
      <label class="form-label">Select Worker</label>
      <select name="worker_id" class="form-select" required>
        <option value="">-- Choose --</option>
        <?php while ($w = $workers->fetch_assoc()): ?>
          <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">PIN</label>
      <input type="password" name="pin" class="form-control" required />
    </div>
    <div class="mb-3">
      <label class="form-label">Photo</label>
      <input type="file" name="photo" accept="image/*" capture="environment" class="form-control" required />
    </div>
    <input type="hidden" name="latitude" id="latitude" />
    <input type="hidden" name="longitude" id="longitude" />
    <button type="submit" class="btn btn-primary">Submit</button>
  </form>
</div>
<script>
function getLocation() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
      document.getElementById('latitude').value = position.coords.latitude;
      document.getElementById('longitude').value = position.coords.longitude;
    });
    return true;
  } else {
    alert("Geolocation not supported.");
    return false;
  }
}
</script>
</body>
</html>