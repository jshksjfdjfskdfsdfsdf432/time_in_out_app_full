<?php
require_once 'db.php';
@date_default_timezone_set('Asia/Manila');

// --- Rendered Hours Function with Break Deduction ---
function calculateRenderedHours(DateTime $timeIn, DateTime $timeOut, ?float &$rawHours = null): float {
    $breakWindows = [
        ["10:00", "10:30"],
        ["12:00", "13:00"],
        ["15:00", "15:30"],
        ["18:00", "19:00"],
        ["22:00", "22:30"],
        ["00:00", "01:00"],
        ["03:00", "03:30"]
    ];

    $totalBreakMinutes = 0;

    foreach ($breakWindows as [$start, $end]) {
        $bStart = DateTime::createFromFormat("Y-m-d H:i", $timeIn->format('Y-m-d') . ' ' . $start);
        $bEnd   = DateTime::createFromFormat("Y-m-d H:i", $timeIn->format('Y-m-d') . ' ' . $end);

        if ($start < "06:00" && $timeIn->format('H') >= 18) {
            $bStart->modify('+1 day');
            $bEnd->modify('+1 day');
        }

        $startTs = max($bStart->getTimestamp(), $timeIn->getTimestamp());
        $endTs   = min($bEnd->getTimestamp(), $timeOut->getTimestamp());

        if ($startTs < $endTs) {
            $minutes = round(($endTs - $startTs) / 60);
            $totalBreakMinutes += $minutes;
        }
    }

    $shiftMinutes = round(($timeOut->getTimestamp() - $timeIn->getTimestamp()) / 60);
    $netMinutes = max($shiftMinutes - $totalBreakMinutes, 0);

    if ($rawHours !== null) {
        $rawHours = round($shiftMinutes / 60, 2);
    }

    return round($netMinutes / 60, 2);
}

// --- Fetch logs for worker with matching PIN ---
$pin = $_GET['pin'] ?? '';

if (!$pin) {
    die("PIN is required.");
}

// Lookup worker
$stmt = $conn->prepare("SELECT id, name FROM workers WHERE pin = ?");
$stmt->bind_param("s", $pin);
$stmt->execute();
$worker = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$worker) {
    die("Invalid PIN.");
}

$workerId = $worker['id'];
$workerName = htmlspecialchars($worker['name']);

// Get logs
$stmt = $conn->prepare("SELECT * FROM attendance_logs WHERE worker_id = ? ORDER BY time_in DESC");
$stmt->bind_param("i", $workerId);
$stmt->execute();
$result = $stmt->get_result();
$logs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
  <title><?= $workerName ?>'s Records</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
<div class="container py-5">
  <h3 class="mb-4"><?= $workerName ?> – Attendance Records</h3>

  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>Time In</th>
        <th>Time Out</th>
        <th>Total Hours</th>
        <th>Rendered Hours</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($logs as $log): ?>
      <tr>
        <td><?= $log['time_in'] ?></td>
        <td><?= $log['time_out'] ?? '<span class="text-muted">Still working</span>' ?></td>
        <td>
          <?php if ($log['time_out']): 
            $raw = 0;
            calculateRenderedHours(new DateTime($log['time_in']), new DateTime($log['time_out']), $raw);
            echo $raw . ' hrs';
          else: ?>
            <span class="text-muted">-</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($log['time_out']): 
            $rendered = calculateRenderedHours(new DateTime($log['time_in']), new DateTime($log['time_out']));
            echo $rendered . ' hrs';
          else: ?>
            <span class="text-muted">-</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <a href="punch.php" class="btn btn-secondary mt-3">Back to Punch</a>
</div>
</body>
</html>
