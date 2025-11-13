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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <a href="worker/login.php" class="btn btn-outline">Acceso Trabajadores</a>
            </div>
        </div>
    </div>
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
