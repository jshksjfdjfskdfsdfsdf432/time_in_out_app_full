<?php
/**
 * workers.php
 * --------------------------------------------------------------
 * Worker Management Page (separate from Admin dashboard).
 * - Lists workers (searchable by name).
 * - Add new worker with full details (PIN, rate/hr, skill, address, phone, age, emergency contacts).
 * - Redirects back to self w/ status messages.
 * - Includes link back to Admin Dashboard (attendance logs).
 *
 * NOTE: This file handles its own INSERTs; you may retire create_worker.php
 * or keep it (but update its redirect). For now, workers.php does not
 * support edit/delete—ask and I’ll add those.
 */

require_once 'db.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}
@date_default_timezone_set('Asia/Manila');

// ------------------------------------------------------------------
// Helpers
// ------------------------------------------------------------------
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function back($msg){header('Location: workers.php?msg='.urlencode($msg));exit();}

// ------------------------------------------------------------------
// Handle POST (Add Worker)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name              = trim($_POST['name'] ?? '');
    $pin               = trim($_POST['pin'] ?? '');
    $rate_per_hr_raw   = trim($_POST['rate_per_hr'] ?? '');
    $skill_type        = trim($_POST['skill_type'] ?? '');
    $address           = trim($_POST['address'] ?? '');
    $phone_number      = trim($_POST['phone_number'] ?? '');
    $age_raw           = trim($_POST['age'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $emergency_phone   = trim($_POST['emergency_phone'] ?? '');

    $errs=[];
    if($name==='')$errs[]='Name';
    if($pin===''||!preg_match('/^\\d{4}$/',$pin))$errs[]='4-digit PIN';
    if($rate_per_hr_raw===''||!is_numeric($rate_per_hr_raw))$errs[]='Rate/hr';
    if($skill_type==='')$errs[]='Skill';
    if($address==='')$errs[]='Address';
    if($phone_number==='')$errs[]='Phone';
    if($age_raw===''||!ctype_digit($age_raw)|| (int)$age_raw<=0)$errs[]='Age';
    if($emergency_contact==='')$errs[]='Emergency Contact';
    if($emergency_phone==='')$errs[]='Emergency Phone';

    if($errs){back('Missing/invalid: '.implode(', ',$errs));}

    $rate_per_hr=(float)$rate_per_hr_raw;
    $age=(int)$age_raw;

    // Ensure PIN not already used
    $stmtChk=$conn->prepare('SELECT id FROM workers WHERE pin=? LIMIT 1');
    $stmtChk->bind_param('s',$pin);
    $stmtChk->execute();
    $stmtChk->store_result();
    if($stmtChk->num_rows>0){$stmtChk->close();back('PIN already in use');}
    $stmtChk->close();

    $sql='INSERT INTO workers (name,pin,rate_per_hr,skill_type,address,phone_number,age,emergency_contact,emergency_phone) VALUES (?,?,?,?,?,?,?,?,?)';
    $stmt=$conn->prepare($sql);
    if(!$stmt){back('DB error: '.$conn->error);}    
    $stmt->bind_param('ssdsssiss',$name,$pin,$rate_per_hr,$skill_type,$address,$phone_number,$age,$emergency_contact,$emergency_phone);
    if(!$stmt->execute()){ $stmt->close(); back('DB exec error: '.$stmt->error); }
    $stmt->close();
    back('Worker added');
}

// ------------------------------------------------------------------
// GET: Search Workers
// ------------------------------------------------------------------
$msg = isset($_GET['msg'])?$_GET['msg']:'';
$searchWorker = isset($_GET['search_worker'])?trim($_GET['search_worker']):'';

$workers=[];
$sqlWorkers='SELECT id,name,pin,rate_per_hr,skill_type,address,phone_number,age,emergency_contact,emergency_phone FROM workers';
$params=[];$types='';
if($searchWorker!==''){
    $sqlWorkers.=' WHERE name LIKE ?';
    $params[]="%$searchWorker%";$types.='s';
}
$sqlWorkers.=' ORDER BY name ASC';
$stmtW=$conn->prepare($sqlWorkers);
if($types){$stmtW->bind_param($types,...$params);}    
$stmtW->execute();
$res=$stmtW->get_result();
while($row=$res->fetch_assoc())$workers[]=$row;
$stmtW->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Manage Workers</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>.photo-thumb{height:40px;object-fit:cover;border-radius:4px;border:1px solid #ccc;}</style>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between mb-3">
    <h4>Manage Workers</h4>
    <div>
      <a href="Admin.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
      <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </div>
  </div>
  <?php if($msg):?><div class="alert alert-info"><?=e($msg)?></div><?php endif;?>

  <!-- Add Worker Form -->
  <h5>Add New Worker</h5>
  <form method="post" class="row g-2 mb-4">
    <div class="col-md-3"><input name="name" class="form-control" placeholder="Full Name" required></div>
    <div class="col-md-2"><input name="pin" class="form-control" placeholder="4-digit PIN" required pattern="\d{4}" maxlength="4"></div>
    <div class="col-md-2"><input type="number" step="0.01" min="0" name="rate_per_hr" class="form-control" placeholder="Rate/Hour" required></div>
    <div class="col-md-2"><input name="skill_type" class="form-control" placeholder="Skill Type" required></div>
    <div class="col-md-3"><input name="address" class="form-control" placeholder="Address" required></div>
    <div class="col-md-2"><input name="phone_number" class="form-control" placeholder="Phone Number" required></div>
    <div class="col-md-1"><input type="number" name="age" class="form-control" placeholder="Age" required></div>
    <div class="col-md-2"><input name="emergency_contact" class="form-control" placeholder="Emergency Contact" required></div>
    <div class="col-md-2"><input name="emergency_phone" class="form-control" placeholder="Emergency Phone" required></div>
    <div class="col-md-2"><button class="btn btn-primary">Add Worker</button></div>
  </form>

  <!-- Search Workers -->
  <h5>Workers</h5>
  <form method="get" class="row g-2 mb-3">
    <div class="col-md-4"><input type="text" name="search_worker" value="<?=e($searchWorker)?>" class="form-control form-control-sm" placeholder="Search worker by name"></div>
    <div class="col-md-2"><button class="btn btn-primary btn-sm">Search</button></div>
  </form>

  <div class="table-responsive mb-4">
    <table class="table table-sm table-bordered align-middle">
      <thead class="table-light"><tr><th>Name</th><th>PIN</th><th>Rate/hr</th><th>Skill</th><th>Address</th><th>Phone</th><th>Age</th><th>Emergency Contact</th><th>Emergency Phone</th></tr></thead>
      <tbody>
        <?php if(!$workers):?>
          <tr><td colspan="9" class="text-center text-muted">No workers found.</td></tr>
        <?php else: foreach($workers as $w):?>
          <tr>
            <td><?=e($w['name'])?></td>
            <td><?=e($w['pin'])?></td>
            <td><?=number_format($w['rate_per_hr'],2)?></td>
            <td><?=e($w['skill_type'])?></td>
            <td><?=e($w['address'])?></td>
            <td><?=e($w['phone_number'])?></td>
            <td><?=e($w['age'])?></td>
            <td><?=e($w['emergency_contact'])?></td>
            <td><?=e($w['emergency_phone'])?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
