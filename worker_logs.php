<?php
require_once 'db.php';
@date_default_timezone_set('Asia/Manila');

// FUNCTION: calculate rendered hours minus overlapping scheduled breaks
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

        // Handle overnight breaks (e.g., 00:00–06:00) by rolling into next day
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
        $rawHours = round($shiftMinutes / 60, 2); // send back original hours
    }

    return round($netMinutes / 60, 2); // net hours
}

// Fetch logs
$stmt = $conn->query("SELECT l.*, w.name FROM attendance_logs l JOIN workers w ON l.worker_id = w.id ORDER BY l.time_in DESC");
$logs = $stmt->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Worker Logs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
<div class="container py-5">
  <h3 class="mb-4">Attendance Logs with Break Deduction</h3>
  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>Worker</th>
        <th>Time In</th>
        <th>Time Out</th>
        <th>Total Hours</th>
        <th>Rendered Hours</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($logs as $log): ?>
      <tr>
        <td><?= htmlspecialchars($log['name']) ?></td>
        <td><?= $log['time_in'] ?></td>
        <td><?= $log['time_out'] ?? '<span class="text-muted">Still working</span>' ?></td>
        <td>
          <?php if ($log['time_in'] && $log['time_out']): 
            $raw = 0;
            calculateRenderedHours(new DateTime($log['time_in']), new DateTime($log['time_out']), $raw);
            echo $raw . ' hrs';
          else: ?>
            <span class="text-muted">-</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($log['time_in'] && $log['time_out']): 
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
</div>
</body>
</html>
