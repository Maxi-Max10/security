<?php
session_start();

// Si el usuario ya está autenticado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido - Sistema de Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="welcome-box">
            <h1>Bienvenido al Sistema</h1>
            <p>Por favor, inicia sesión o regístrate para continuar</p>
            <div class="button-group">
                <a href="login.php" class="btn btn-primary">Iniciar Sesión</a>
                <a href="register.php" class="btn btn-secondary">Registrarse</a>
            </div>
        </div>
    </div>
</body>
</html>
