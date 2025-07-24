<?php
/**
 * Admin Dashboard (Attendance Logs with Cash Advances and Breaks)
 */
require_once 'db.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}
@date_default_timezone_set('Asia/Manila');

$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8') : '';

$todayStr = date('Y-m-d');
$monthStartStr = date('Y-m-01');
$start    = isset($_GET['start']) && $_GET['start'] !== '' ? $_GET['start'] : $monthStartStr;
$end      = isset($_GET['end']) && $_GET['end'] !== '' ? $_GET['end'] : $todayStr;
$workerId = isset($_GET['worker_id']) ? (int)$_GET['worker_id'] : 0;

$sd = DateTime::createFromFormat('Y-m-d', $start) ?: new DateTime($monthStartStr);
$ed = DateTime::createFromFormat('Y-m-d', $end) ?: new DateTime($todayStr);
if ($ed < $sd) { $ed = clone $sd; }
$start = $sd->format('Y-m-d');
$end   = $ed->format('Y-m-d');

// Fetch workers for dropdown
$workers = [];
if ($stmtW = $conn->prepare("SELECT id, name FROM workers ORDER BY name ASC")) {
    $stmtW->execute();
    $res = $stmtW->get_result();
    while ($row = $res->fetch_assoc()) $workers[] = $row;
    $stmtW->close();
}

// Fetch attendance logs with new fields
if ($workerId > 0) {
    $sql = "SELECT l.*, w.name, w.rate_per_hr FROM attendance_logs l JOIN workers w ON l.worker_id=w.id WHERE DATE(l.time_in) BETWEEN ? AND ? AND l.worker_id=? ORDER BY l.time_in DESC, l.id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $start, $end, $workerId);
} else {
    $sql = "SELECT l.*, w.name, w.rate_per_hr FROM attendance_logs l JOIN workers w ON l.worker_id=w.id WHERE DATE(l.time_in) BETWEEN ? AND ? ORDER BY l.time_in DESC, l.id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $start, $end);
}
$stmt->execute();
$logsRes = $stmt->get_result();
$logs = [];
while ($r = $logsRes->fetch_assoc()) $logs[] = $r;
$stmt->close();

function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function hoursDiff($start,$end){
    if(!$start||!$end||$end==='0000-00-00 00:00:00')return 0.0;
    $ds=new DateTime($start);$de=new DateTime($end);
    return max(round(($de->getTimestamp()-$ds->getTimestamp())/3600,2),0);
}
$totalHours=0.0;$totalPay=0.0;
foreach($logs as $lg){
    if($lg['time_out'] && $lg['time_out']!=='0000-00-00 00:00:00'){
        $h=hoursDiff($lg['time_in'],$lg['time_out']);
        $p=$h*(float)$lg['rate_per_hr'];
        $totalHours+=$h;$totalPay+=$p;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Dashboard - Attendance Logs</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>.photo-thumb{height:40px;object-fit:cover;border-radius:4px;border:1px solid #ccc;}.open-shift{background:#fff3cd!important;}</style>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between mb-3">
    <h4>Admin Dashboard</h4>
    <div>
      <a href="workers.php" class="btn btn-secondary btn-sm">Manage Workers</a>
      <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </div>
  </div>
  <?php if($msg): ?><div class="alert alert-info"><?= $msg ?></div><?php endif; ?>

  <h5>Attendance Logs</h5>
  <form method="get" class="row g-2 mb-3">
    <div class="col-md-3"><label class="form-label">Start</label><input type="date" name="start" value="<?=e($start)?>" class="form-control form-control-sm"></div>
    <div class="col-md-3"><label class="form-label">End</label><input type="date" name="end" value="<?=e($end)?>" class="form-control form-control-sm"></div>
    <div class="col-md-3"><label class="form-label">Worker</label><select name="worker_id" class="form-select form-select-sm"><option value="0">All</option><?php foreach($workers as $w):?><option value="<?=$w['id']?>" <?= $workerId==$w['id']?'selected':''?>><?=e($w['name'])?></option><?php endforeach;?></select></div>
    <div class="col-md-3 d-flex align-items-end gap-2"><button class="btn btn-primary btn-sm">Filter</button><a href="Admin.php" class="btn btn-secondary btn-sm">Reset</a><a href="export_logs.php?start=<?=e($start)?>&end=<?=e($end)?>&worker_id=<?=$workerId?>" class="btn btn-success btn-sm">Export CSV</a></div>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered table-sm table-striped">
      <thead class="table-light">
        <tr>
          <th>Worker</th>
          <th>Time In</th>
          <th>Time Out</th>
          <th>Cash Advance</th>
          <th>Advance Date</th>
          <th>Approver</th>
          <th>10AM</th>
          <th>12N</th>
          <th>3PM</th>
          <th>6PM</th>
          <th>10PM</th>
          <th>12MN</th>
          <th>3AM</th>
          <th>Hours</th>
          <th>Pay</th>
          <th>Photo In</th>
          <th>Photo Out</th>
          <th>Map</th>
        </tr>
      </thead>
      <tbody>
        <?php if(!$logs): ?><tr><td colspan="18" class="text-center">No logs</td></tr><?php else: foreach($logs as $log): $closed=($log['time_out'] && $log['time_out']!=='0000-00-00 00:00:00'); $hrs=$closed?hoursDiff($log['time_in'],$log['time_out']):0; $pay=$hrs*(float)$log['rate_per_hr'];?>
        <tr class="<?=$closed?'':'open-shift'?>">
          <td><?=e($log['name'])?></td>
          <td><?=e($log['time_in'])?></td>
          <td><?=$closed?e($log['time_out']):'<span class="text-danger">OPEN</span>'?></td>
          <td><?=e($log['cash_advance_amount'])?></td>
          <td><?=e($log['cash_advance_date'])?></td>
          <td><?=e($log['approver_name'])?></td>
          <td><?=e($log['break_10am'])?></td>
          <td><?=e($log['break_12noon'])?></td>
          <td><?=e($log['break_3pm'])?></td>
          <td><?=e($log['break_6pm'])?></td>
          <td><?=e($log['break_10pm'])?></td>
          <td><?=e($log['break_12midnight'])?></td>
          <td><?=e($log['break_3am'])?></td>
          <td><?=$closed?number_format($hrs,2):''?></td>
          <td><?=$closed?number_format($pay,2):''?></td>
          <td><?php if($log['photo_in']):?><img src="<?=e($log['photo_in'])?>" class="photo-thumb"><?php endif;?></td>
          <td><?php if($log['photo_out']):?><img src="<?=e($log['photo_out'])?>" class="photo-thumb"><?php endif;?></td>
          <td><?php if($log['latitude_in'] && $log['longitude_in']):?><a class="map-link" target="_blank" href="map.php?lat=<?=e($log['latitude_in'])?>&lon=<?=e($log['longitude_in'])?>&label=In">In Map</a><br><?php endif;?><?php if($log['latitude_out'] && $log['longitude_out']):?><a class="map-link" target="_blank" href="map.php?lat=<?=e($log['latitude_out'])?>&lon=<?=e($log['longitude_out'])?>&label=Out">Out Map</a><?php endif;?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
      <?php if($logs): ?><tfoot><tr class="table-light"><th colspan="13" class="text-end">Totals:</th><th><?=number_format($totalHours,2)?></th><th><?=number_format($totalPay,2)?></th><th colspan="3"></th></tr></tfoot><?php endif; ?>
    </table>
  </div>
</div>
</body>
</html>