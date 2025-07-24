<?php
require_once 'db.php';

$message = '';
if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Worker Punch</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    #video { width:100%; max-height:250px; background:#000; border-radius:4px; }
    #canvas { display:none; }
  </style>
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="col-md-6 mx-auto bg-white p-4 rounded shadow">
    <h4 class="mb-3">Time In / Out</h4>

    <?php if ($message): ?>
      <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <form id="punchForm" method="POST" action="punch_submit.php">
      <div class="mb-3">
        <label for="pin" class="form-label">Enter PIN</label>
        <input type="text" name="pin" id="pin" class="form-control" required />
      </div>

      <div class="mb-3">
        <label for="action_type" class="form-label">Action</label>
        <select name="action_type" class="form-select" required>
          <option value="time_in">Time In</option>
          <option value="time_out">Time Out</option>
        </select>
      </div>

      <input type="hidden" name="photo" id="photoInput">
      <input type="hidden" name="latitude" id="latitude">
      <input type="hidden" name="longitude" id="longitude">

      <div class="mb-3">
        <video id="video" autoplay></video>
        <canvas id="canvas" width="640" height="480"></canvas>
      </div>

      <div class="d-grid gap-2 mt-2">
        <button type="submit" class="btn btn-primary">Submit</button>
        <a id="showRecordsBtn" href="#" class="btn btn-outline-secondary d-none">Show Records</a>
      </div>
    </form>
  </div>
</div>

<script>
// 📸 Webcam & photo capture
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const photoInput = document.getElementById('photoInput');

navigator.mediaDevices.getUserMedia({ video: true }).then(stream => {
  video.srcObject = stream;
});

// 📍 GPS silently captured
navigator.geolocation.getCurrentPosition(pos => {
  document.getElementById('latitude').value = pos.coords.latitude;
  document.getElementById('longitude').value = pos.coords.longitude;
}, err => {
  // No UI display if GPS fails
}, { enableHighAccuracy: true });

// 📨 Capture photo before form submit
document.getElementById('punchForm').addEventListener('submit', function(e) {
  const context = canvas.getContext('2d');
  context.drawImage(video, 0, 0, canvas.width, canvas.height);
  const photoData = canvas.toDataURL('image/png');
  photoInput.value = photoData;
});

// ✅ Show "Show Records" button when valid PIN is entered
const pinInput = document.getElementById('pin');
const showBtn = document.getElementById('showRecordsBtn');

pinInput.addEventListener('input', function () {
  const pin = pinInput.value.trim();
  if (pin.length >= 4 && /^\d+$/.test(pin)) {
    showBtn.classList.remove('d-none');
    showBtn.href = `worker_records.php?pin=${encodeURIComponent(pin)}`;
  } else {
    showBtn.classList.add('d-none');
    showBtn.href = '#';
  }
});
</script>
</body>
</html>
