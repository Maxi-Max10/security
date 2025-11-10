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
$success = '';

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Token de seguridad inválido.';
    } else {
        $username = sanitize_input($_POST['username'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validaciones
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = 'Por favor, completa todos los campos.';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $error = 'El nombre de usuario debe tener entre 3 y 50 caracteres.';
        } elseif (!validate_email($email)) {
            $error = 'El correo electrónico no es válido.';
        } elseif (!validate_password($password)) {
            $error = 'La contraseña debe tener al menos 8 caracteres, incluyendo mayúsculas, minúsculas y números.';
        } elseif ($password !== $confirm_password) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            $conn = getDBConnection();
            
            // Verificar si el email ya existe
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Este correo electrónico ya está registrado.';
            } else {
                // Verificar si el username ya existe
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = 'Este nombre de usuario ya está en uso.';
                } else {
                    // Crear nuevo usuario
                    $hashed_password = hash_password($password);
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $username, $email, $hashed_password);
                    
                    if ($stmt->execute()) {
                        $success = '¡Registro exitoso! Redirigiendo al login...';
                        header("refresh:2;url=login.php");
                    } else {
                        $error = 'Error al crear la cuenta. Por favor, intenta nuevamente.';
                    }
                }
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
    <title>Registrarse - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h1>Crear Cuenta</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="register.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="username">Nombre de Usuario</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           minlength="3" maxlength="50">
                </div>
                
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required minlength="8">
                    <small>Mínimo 8 caracteres, incluyendo mayúsculas, minúsculas y números.</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Registrarse</button>
            </form>
            
            <div class="auth-links">
                <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
            </div>
        </div>
    </div>
</body>
</html>
