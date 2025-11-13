<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (worker_is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Token de seguridad inválido. Actualiza la página e intenta nuevamente.';
    } else {
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Ingresa tu correo y contraseña para continuar.';
        } else {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password FROM workers WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $worker = $result->fetch_assoc();

                if (empty($worker['password'])) {
                    $error = 'Aún no tienes una contraseña asignada. Comunícate con el administrador.';
                } elseif (password_verify($password, $worker['password'])) {
                    $_SESSION['worker_id'] = $worker['id'];
                    $_SESSION['worker_name'] = $worker['first_name'] . ' ' . $worker['last_name'];
                    $_SESSION['worker_email'] = $worker['email'];

                    $stmt->close();
                    $conn->close();

                    redirect('dashboard.php', '¡Bienvenido!', 'success');
                } else {
                    $error = 'Credenciales incorrectas.';
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
    <title>Acceso Trabajadores - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h1>Acceso Trabajadores</h1>
            <p style="text-align:center;color:var(--gray);margin-bottom:20px;">Ingresa con el correo y la contraseña que el administrador te asignó.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php display_flash_message(); ?>

            <form method="POST" action="login.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-group">
                    <label for="email">Correo</label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
            </form>

            <div class="auth-links">
                <p>¿Necesitas ayuda? Contacta al administrador del sistema.</p>
                <p><a href="../login.php">Volver al acceso principal</a></p>
            </div>
        </div>
    </div>
</body>
</html>
