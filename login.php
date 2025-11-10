<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Si ya está autenticado, redirigir
if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Token de seguridad inválido.';
    } else {
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Por favor, completa todos los campos.';
        } else {
            $conn = getDBConnection();
            
            // Buscar usuario
            $stmt = $conn->prepare("SELECT id, username, email, password, role, is_active FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (!$user['is_active']) {
                    $error = 'Tu cuenta ha sido desactivada.';
                } elseif (verify_password($password, $user['password'])) {
                    // Login exitoso
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    // Actualizar último login
                    $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $update_stmt->bind_param("i", $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    // Registrar intento exitoso
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $log_stmt = $conn->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, 1)");
                    $log_stmt->bind_param("ss", $email, $ip);
                    $log_stmt->execute();
                    $log_stmt->close();
                    
                    $stmt->close();
                    $conn->close();
                    
                    // Redirigir según el rol
                    if ($user['role'] === 'admin') {
                        redirect('admin/dashboard.php', '¡Bienvenido, Administrador!', 'success');
                    } else {
                        redirect('dashboard.php', '¡Bienvenido de nuevo!', 'success');
                    }
                } else {
                    $error = 'Credenciales incorrectas.';
                    
                    // Registrar intento fallido
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $log_stmt = $conn->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, 0)");
                    $log_stmt->bind_param("ss", $email, $ip);
                    $log_stmt->execute();
                    $log_stmt->close();
                }
            } else {
                $error = 'Credenciales incorrectas.';
            }
            
            $stmt->close();
            $conn->close();
        }
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h1>Iniciar Sesión</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php display_flash_message(); ?>
            
            <form method="POST" action="login.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
            </form>
            
            <div class="auth-links">
                <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
            </div>
        </div>
    </div>
</body>
</html>
