<?php
/**
 * punch_state.php (no auto-check on typing)
 * --------------------------------------------------------------
 * Returns JSON describing worker state (clocked IN/OUT) for punch.php.
 * The frontend will call this only when the user hits "Check".
 */

require_once 'db.php';
session_start();
@date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

function jsend($arr){ echo json_encode($arr); exit(); }

$rawPin = $_POST['pin'] ?? $_GET['pin'] ?? '';
$rawPin = (string)$rawPin;
$pinClean = trim($rawPin);

if ($pinClean === '') {
    jsend(['ok'=>false,'msg'=>'No PIN provided.']);
}

$stmt=$conn->prepare('SELECT id,name,pin FROM workers WHERE pin=? LIMIT 1');
if(!$stmt){ jsend(['ok'=>false,'msg'=>'DB error: '.$conn->error]); }
$stmt->bind_param('s',$pinClean);
$stmt->execute();
$worker=$stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$worker){
    jsend(['ok'=>false,'msg'=>'Invalid PIN.']);
}

$workerId=(int)$worker['id'];
$stmt=$conn->prepare('SELECT id,time_in FROM attendance_logs WHERE worker_id=? AND time_out IS NULL ORDER BY time_in DESC LIMIT 1');
$stmt->bind_param('i',$workerId);
$stmt->execute();
$open=$stmt->get_result()->fetch_assoc();
$stmt->close();

jsend([
  'ok'        => true,
  'name'      => $worker['name'],
  'workerId'  => $workerId,
  'isTimedIn' => $open?true:false,
  'lastIn'    => $open?$open['time_in']:null,
]);
