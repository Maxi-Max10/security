<?php
// Simple API health/status for debugging on hosting
session_start();
header('Content-Type: application/json; charset=utf-8');
@ini_set('display_errors','0');
@ini_set('html_errors','0');
if (!defined('API_MODE')) define('API_MODE', true);

require_once '../../config/config.php';
require_once '../../includes/functions.php';
// Avoid getDBConnection() because on failure it exits; test manually
$status = [
  'ok' => true,
  'time' => date('c'),
  'session' => isset($_SESSION['user_id']) ? true : false,
  'user_id' => isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null,
  'is_admin' => is_admin(),
];

// DB test
$db = null; $db_ok = false; $db_error = null;
try {
  $db = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
  if ($db && !$db->connect_error) {
    $db->set_charset('utf8mb4');
    // Ping table
    $res = $db->query("SHOW TABLES LIKE 'workers'");
    $has_workers = $res && $res->num_rows > 0;
    $status['db'] = 'ok';
    $status['has_workers_table'] = $has_workers;
    if ($has_workers) {
      $cnt = $db->query("SELECT COUNT(*) c FROM workers");
      if ($cnt) { $status['workers_count'] = intval($cnt->fetch_assoc()['c']); }
    }
    $db_ok = true;
  } else {
    $db_error = $db ? $db->connect_error : 'mysqli init failed';
  }
} catch (Throwable $e) { $db_error = $e->getMessage(); }

if (!$db_ok) { $status['db'] = 'error'; $status['db_error'] = $db_error; }

// user data snapshot (safe)
try { $u = get_user_data(); $status['user'] = $u ? ['id'=>$u['id'],'email'=>$u['email'],'role'=>$u['role']] : null; } catch (Throwable $e) { $status['user_error'] = $e->getMessage(); }

echo json_encode($status);
