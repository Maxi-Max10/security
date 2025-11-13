<?php
/**
 * Funciones auxiliares del sistema
 */

/**
 * Sanitizar entrada de datos
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validar email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validar contraseña
 */
function validate_password($password) {
    // Mínimo 8 caracteres, al menos una mayúscula, una minúscula y un número
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

/**
 * Hash de contraseña
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verificar contraseña
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generar token CSRF
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redireccionar con mensaje
 */
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit();
}

/**
 * Mostrar mensaje flash
 */
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        echo "<div class='alert alert-$type'>$message</div>";
    }
}

/**
 * Verificar si el usuario está autenticado
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Requerir autenticación
 */
function require_login() {
    if (!is_logged_in()) {
        redirect('login.php', 'Debes iniciar sesión para acceder.', 'warning');
    }
}

/**
 * Obtener información del usuario actual
 */
function get_user_data() {
    if (!is_logged_in()) {
        return null;
    }
    
    require_once __DIR__ . '/../config/database.php';
    $conn = getDBConnection();
    
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, username, email, role, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $user;
}

/**
 * Verificar si el usuario es administrador
 */
function is_admin() {
    if (!is_logged_in()) {
        return false;
    }
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Requerir rol de administrador
 */
function require_admin() {
    if (!is_admin()) {
        redirect('dashboard.php', 'No tienes permisos para acceder a esta sección.', 'error');
    }
}

/**
 * ==============================
 * Autenticación de trabajadores
 * ==============================
 */

function worker_is_logged_in() {
    return isset($_SESSION['worker_id']);
}

function require_worker_login() {
    if (!worker_is_logged_in()) {
        $loginUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') . '/worker/login.php' : '/worker/login.php';
        redirect($loginUrl, 'Debes iniciar sesión como trabajador para continuar.', 'warning');
    }
}

function get_worker_profile($worker_id = null) {
    if ($worker_id === null) {
        if (!worker_is_logged_in()) {
            return null;
        }
        $worker_id = intval($_SESSION['worker_id']);
    }

    require_once __DIR__ . '/../config/database.php';
    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, dni, cvu_alias, age, work_place, address_text, address_url, latitude, longitude, created_at, updated_at FROM workers WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $worker_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $worker = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    return $worker ?: null;
}

function worker_logout() {
    unset($_SESSION['worker_id'], $_SESSION['worker_name'], $_SESSION['worker_email']);
}

function get_worker_attendance($worker_id, $limit = 10) {
    require_once __DIR__ . '/../config/database.php';
    $conn = getDBConnection();

    $limit = max(1, min(intval($limit), 50));

    $stmt = $conn->prepare(
        "SELECT id, latitude, longitude, recorded_at, attachment_path, attachment_original, created_at
         FROM worker_attendance
         WHERE worker_id = ?
         ORDER BY recorded_at DESC
         LIMIT ?"
    );
    $stmt->bind_param('ii', $worker_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $records = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

    return $records;
}
?>
