<?php
// Test insert directo para diagnosticar problemas de creación
session_start();
header('Content-Type: application/json; charset=utf-8');
@ini_set('display_errors','0'); @ini_set('html_errors','0');
if (!defined('API_MODE')) define('API_MODE', true);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!is_logged_in() || !is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit; }

$conn = getDBConnection();

// Datos mínimos para insertar (evitamos FK para aislar problemas de conexión/insert)
$rand = substr(md5(uniqid('', true)),0,6);
$first = 'Test';
$last = 'Worker';
$dni = strval(mt_rand(10000000,99999999));
$email = 'test' . $rand . '@example.com';
$cvu_alias = 'alias' . $rand;
$age = mt_rand(20,60);
$work_place = 'Prueba API';

$sql = 'INSERT INTO workers (first_name,last_name,dni,email,cvu_alias,age,work_place) VALUES (?,?,?,?,?,?,?)';
$stmt = $conn->prepare($sql);
$stmt->bind_param('sssssis', $first,$last,$dni,$email,$cvu_alias,$age,$work_place);
if ($stmt->execute()) {
  echo json_encode(['ok'=>true,'id'=>$stmt->insert_id,'dni'=>$dni,'email'=>$email]);
} else {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Fallo insert','db_error'=>$stmt->error?:$conn->error]);
}
$stmt->close();
$conn->close();
