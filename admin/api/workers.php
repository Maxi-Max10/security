<?php
// API JSON para gestión de trabajadores
session_start();
// For API responses, ensure clean JSON: no HTML error output
header('Content-Type: application/json; charset=utf-8');
@ini_set('display_errors', '0');
@ini_set('html_errors', '0');
@error_reporting(0);
// Iniciar buffer y capturar errores fatales para devolver JSON siempre
if (!defined('API_MODE')) define('API_MODE', true);
if (!defined('APP_API_FATAL_GUARD')) define('APP_API_FATAL_GUARD', true);
ob_start();
register_shutdown_function(function(){
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        // Limpiar cualquier salida previa (HTML de error) y responder JSON
        while (ob_get_level() > 0) { @ob_end_clean(); }
        if (!headers_sent()) { @header('Content-Type: application/json; charset=utf-8'); http_response_code(500); }
        echo json_encode(['ok'=>false,'error'=>'Error interno del servidor']);
    } else {
        // Volcar salida normal si no hubo fatal error
        @ob_end_flush();
    }
});
// Flag to let DB helper emit JSON on connection failure
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

// Solo métodos permitidos
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$conn = getDBConnection();

function api_validate_worker($data, $is_update = false, $existing_id = null, $conn = null) {
    $errors = [];
    $first_name = trim($data['first_name'] ?? '');
    $last_name = trim($data['last_name'] ?? '');
    $dni = preg_replace('/\D+/', '', $data['dni'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $cvu_alias = trim($data['cvu_alias'] ?? '');
    $age = trim($data['age'] ?? '');
    $work_place = trim($data['work_place'] ?? '');
    $address_input = trim($data['address'] ?? '');

    if ($first_name === '') { $errors['first_name'] = 'Nombre obligatorio.'; }
    if ($last_name === '') { $errors['last_name'] = 'Apellido obligatorio.'; }
    if ($dni === '' || !preg_match('/^\d{7,10}$/', $dni)) { $errors['dni'] = 'DNI inválido.'; }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Email inválido.'; }
    if ($password !== null && !is_string($password)) { $password = ''; }
    $password = trim($password);

    if ($work_place === '') { $errors['work_place'] = 'Lugar de trabajo obligatorio.'; }

    if (!$is_update) {
        if ($password === '' || strlen($password) < 8) {
            $errors['password'] = 'Contraseña mínima de 8 caracteres.';
        }
    } else {
        if ($password !== '' && strlen($password) < 8) {
            $errors['password'] = 'Contraseña mínima de 8 caracteres.';
        }
    }
    if ($age !== '') {
        if (!ctype_digit($age)) { $errors['age'] = 'Edad debe ser numérica.'; }
        else { $ageNum = intval($age); if ($ageNum < 16 || $ageNum > 100) { $errors['age'] = 'Edad fuera de rango.'; } }
    }
    if ($cvu_alias !== '' && !preg_match('/^[A-Za-z0-9._-]{3,}$/', $cvu_alias)) { $errors['cvu_alias'] = 'CVU/Alias inválido.'; }

    // Unicidad
    if ($conn) {
        if ($stmt = $conn->prepare($existing_id ? 'SELECT id FROM workers WHERE dni = ? AND id <> ? LIMIT 1' : 'SELECT id FROM workers WHERE dni = ? LIMIT 1')) {
            if ($existing_id) { $stmt->bind_param('si', $dni, $existing_id); } else { $stmt->bind_param('s', $dni); }
            $stmt->execute(); $stmt->store_result(); if ($stmt->num_rows > 0) { $errors['dni'] = 'DNI ya existente.'; } $stmt->close();
        }
        if ($stmt = $conn->prepare($existing_id ? 'SELECT id FROM workers WHERE email = ? AND id <> ? LIMIT 1' : 'SELECT id FROM workers WHERE email = ? LIMIT 1')) {
            if ($existing_id) { $stmt->bind_param('si', $email, $existing_id); } else { $stmt->bind_param('s', $email); }
            $stmt->execute(); $stmt->store_result(); if ($stmt->num_rows > 0) { $errors['email'] = 'Email ya existente.'; } $stmt->close();
        }
    }

    $address_text = null; $address_url = null; $lat = null; $lng = null;
    if ($address_input !== '') {
        $is_gmaps = preg_match('#^https?://(www\.)?(google\.com/maps|maps\.google\.com)/#i', $address_input);
        if ($is_gmaps) {
            $address_url = $address_input;
            if (preg_match('#@(-?\d+\.\d+),(-?\d+\.\d+)#', $address_input, $m)) { $lat = $m[1]; $lng = $m[2]; }
            elseif (preg_match('#[?&]query=(-?\d+\.\d+),(-?\d+\.\d+)#', $address_input, $m2)) { $lat = $m2[1]; $lng = $m2[2]; }
        } else { $address_text = $address_input; }
    }

    return ['errors' => $errors, 'clean' => compact('first_name','last_name','dni','email','password','cvu_alias','age','work_place','address_text','address_url','lat','lng')];
}

function ensure_user_id_or_null($conn, $uid){
    $uid = intval($uid);
    if ($uid < 1) return null;
    if (!$conn) return null;
    if ($stmt = $conn->prepare('SELECT id FROM users WHERE id=? LIMIT 1')){
        $stmt->bind_param('i',$uid); $stmt->execute(); $stmt->store_result();
        $ok = $stmt->num_rows > 0; $stmt->close();
        return $ok ? $uid : null;
    }
    return null;
}

function require_csrf_json() {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'CSRF inválido']);
        exit;
    }
}

try {
    if ($method === 'GET' && $action === 'list') {
        $per_page = max(1, min(100, intval($_GET['limit'] ?? 10)));
        $page = max(1, intval($_GET['page'] ?? 1));
        $offset = ($page - 1) * $per_page;
        $q = trim($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? 'last_name';
        $dir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
        $allowed_sort = ['first_name','last_name','dni','email','age','work_place','created_at'];
        if (!in_array($sort, $allowed_sort)) { $sort = 'last_name'; }

        // Extra filters
        $age_min = isset($_GET['age_min']) && $_GET['age_min'] !== '' ? intval($_GET['age_min']) : null;
        $age_max = isset($_GET['age_max']) && $_GET['age_max'] !== '' ? intval($_GET['age_max']) : null;
        $work_place_f = trim($_GET['work_place'] ?? '');
        $has_geo = isset($_GET['has_geo']) && $_GET['has_geo'] == '1';

        // WHERE builder
        $whereParts = [];
        $params = [];
        $types = '';
        if ($q !== '') {
            $whereParts[] = '(first_name LIKE ? OR last_name LIKE ? OR dni LIKE ?)';
            $like = "%$q%"; $params[] = $like; $params[] = $like; $params[] = $like; $types .= 'sss';
        }
        if (!is_null($age_min)) { $whereParts[] = '(age IS NOT NULL AND age >= ?)'; $params[] = $age_min; $types .= 'i'; }
        if (!is_null($age_max)) { $whereParts[] = '(age IS NOT NULL AND age <= ?)'; $params[] = $age_max; $types .= 'i'; }
        if ($work_place_f !== '') { $whereParts[] = 'work_place LIKE ?'; $params[] = "%$work_place_f%"; $types .= 's'; }
        if ($has_geo) { $whereParts[] = '((latitude IS NOT NULL AND longitude IS NOT NULL) OR address_url IS NOT NULL)'; }
        $whereSQL = count($whereParts) ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

        // Total
        if ($whereSQL) {
            $stmt = $conn->prepare("SELECT COUNT(*) total FROM workers $whereSQL");
            if ($types !== '') { $stmt->bind_param($types, ...$params); }
            $stmt->execute();
            $total = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
        } else {
            $total = $conn->query('SELECT COUNT(*) total FROM workers')->fetch_assoc()['total'];
        }

        // Datos
        if ($whereSQL) {
            $sql = "SELECT * FROM workers $whereSQL ORDER BY $sort $dir LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $types2 = $types . 'ii'; $params2 = array_merge($params, [$per_page,$offset]);
            $stmt->bind_param($types2, ...$params2);
        } else {
            $stmt = $conn->prepare("SELECT * FROM workers ORDER BY $sort $dir LIMIT ? OFFSET ?");
            $stmt->bind_param('ii', $per_page,$offset);
        }
        $stmt->execute(); $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            if (isset($r['password'])) unset($r['password']);
            $rows[] = $r;
        }
        $stmt->close();
        echo json_encode(['ok' => true,'data' => $rows,'page' => $page,'per_page' => $per_page,'total' => $total,'total_pages' => max(1,ceil($total/$per_page))]);
    }
    elseif ($method === 'GET' && $action === 'get') {
        $id = intval($_GET['id'] ?? 0); if ($id < 1) { throw new Exception('ID inválido'); }
        $stmt = $conn->prepare('SELECT * FROM workers WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id); $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'No encontrado']); }
        else {
            if (isset($row['password'])) unset($row['password']);
            echo json_encode(['ok'=>true,'data'=>$row]);
        }
    }
    elseif ($method === 'POST' && $action === 'create') {
        require_csrf_json();
        $val = api_validate_worker($_POST,false,null,$conn);
        if ($val['errors']) { http_response_code(422); echo json_encode(['ok'=>false,'errors'=>$val['errors']]); }
        else {
            $c = $val['clean'];
            $age = $c['age'] !== '' ? intval($c['age']) : null;
            $uid = ensure_user_id_or_null($conn, $_SESSION['user_id'] ?? 0);
            $first_name = $c['first_name'];
            $last_name = $c['last_name'];
            $dni = $c['dni'];
            $email = $c['email'];
            $password_plain = $c['password'];
            $cvu_alias = $c['cvu_alias'] !== '' ? $c['cvu_alias'] : null;
            $work_place = $c['work_place'];
            $address_text = $c['address_text'];
            $address_url = $c['address_url'];
            $latitude = $c['lat'] !== null ? floatval($c['lat']) : null;
            $longitude = $c['lng'] !== null ? floatval($c['lng']) : null;
            $created_by = $uid;
            $updated_by = $uid;
            $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

            $sql = 'INSERT INTO workers (first_name,last_name,dni,email,password,cvu_alias,age,work_place,address_text,address_url,latitude,longitude,created_by,updated_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                'ssssssisssddii',
                $first_name,
                $last_name,
                $dni,
                $email,
                $password_hash,
                $cvu_alias,
                $age,
                $work_place,
                $address_text,
                $address_url,
                $latitude,
                $longitude,
                $created_by,
                $updated_by
            );
            if ($stmt->execute()) { echo json_encode(['ok'=>true,'id'=>$stmt->insert_id]); } else { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Error al crear','db_error'=>$stmt->error?:$conn->error]); }
            $stmt->close();
        }
    }
    elseif ($method === 'POST' && $action === 'update') {
        require_csrf_json();
        $id = intval($_POST['id'] ?? 0); if ($id < 1) { throw new Exception('ID inválido'); }
        $val = api_validate_worker($_POST,true,$id,$conn);
        if ($val['errors']) { http_response_code(422); echo json_encode(['ok'=>false,'errors'=>$val['errors']]); }
        else {
            $c = $val['clean'];
            $age = $c['age'] !== '' ? intval($c['age']) : null;
            $uid = ensure_user_id_or_null($conn, $_SESSION['user_id'] ?? 0);
            $first_name = $c['first_name'];
            $last_name = $c['last_name'];
            $dni = $c['dni'];
            $email = $c['email'];
            $password_plain = $c['password'];
            $cvu_alias = $c['cvu_alias'] !== '' ? $c['cvu_alias'] : null;
            $work_place = $c['work_place'];
            $address_text = $c['address_text'];
            $address_url = $c['address_url'];
            $latitude = $c['lat'] !== null ? floatval($c['lat']) : null;
            $longitude = $c['lng'] !== null ? floatval($c['lng']) : null;
            $updated_by = $uid;

            if ($password_plain !== '') {
                $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
                $sql = 'UPDATE workers SET first_name=?, last_name=?, dni=?, email=?, password=?, cvu_alias=?, age=?, work_place=?, address_text=?, address_url=?, latitude=?, longitude=?, updated_by=? WHERE id=?';
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    'ssssssisssddii',
                    $first_name,
                    $last_name,
                    $dni,
                    $email,
                    $password_hash,
                    $cvu_alias,
                    $age,
                    $work_place,
                    $address_text,
                    $address_url,
                    $latitude,
                    $longitude,
                    $updated_by,
                    $id
                );
            } else {
                $sql = 'UPDATE workers SET first_name=?, last_name=?, dni=?, email=?, cvu_alias=?, age=?, work_place=?, address_text=?, address_url=?, latitude=?, longitude=?, updated_by=? WHERE id=?';
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    'sssssisssddii',
                    $first_name,
                    $last_name,
                    $dni,
                    $email,
                    $cvu_alias,
                    $age,
                    $work_place,
                    $address_text,
                    $address_url,
                    $latitude,
                    $longitude,
                    $updated_by,
                    $id
                );
            }
            if ($stmt->execute()) { echo json_encode(['ok'=>true]); } else { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Error al actualizar','db_error'=>$stmt->error?:$conn->error]); }
            $stmt->close();
        }
    }
    elseif ($method === 'POST' && $action === 'delete') {
        require_csrf_json();
        $id = intval($_POST['id'] ?? 0); if ($id < 1) { throw new Exception('ID inválido'); }
        $stmt = $conn->prepare('DELETE FROM workers WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) { echo json_encode(['ok'=>true]); } else { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Error al eliminar']); }
        $stmt->close();
    }
    else {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'Acción o método inválido']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}

$conn->close();
