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
 * Obtener usuario actual
 */
function get_current_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    require_once __DIR__ . '/../config/database.php';
    $conn = getDBConnection();
    
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $user;
}
?>
