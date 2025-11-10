<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Requerir autenticación
require_login();

// Obtener información del usuario
$user = get_current_user();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="navbar">
        <div class="container">
            <div class="nav-content">
                <h2><?php echo SITE_NAME; ?></h2>
                <div class="nav-right">
                    <span class="user-info"><?php echo htmlspecialchars($user['username']); ?></span>
                    <?php if (is_admin()): ?>
                        <a href="admin/dashboard.php" class="btn btn-small btn-primary">Panel Admin</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-small btn-secondary">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php display_flash_message(); ?>
        
        <div class="dashboard-box">
            <h1>🎯 Panel de Control</h1>
            
            <div class="user-profile">
                <h2>👤 Información de la Cuenta</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Usuario:</strong>
                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Email:</strong>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Rol:</strong>
                        <span class="badge badge-<?php echo $user['role']; ?>">
                            <?php echo strtoupper($user['role']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <strong>Miembro desde:</strong>
                        <span><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="welcome-message">
                <h3>✨ ¡Bienvenido a tu panel!</h3>
                <p>Has iniciado sesión correctamente. Desde aquí puedes gestionar tu cuenta y acceder a las funcionalidades del sistema.</p>
                <?php if (is_admin()): ?>
                <p style="margin-top: 15px;">
                    <strong>🔐 Acceso Administrativo:</strong> Como administrador, tienes acceso al panel de control avanzado donde puedes gestionar usuarios, ver estadísticas y monitorear la actividad del sistema.
                </p>
                <div style="margin-top: 20px;">
                    <a href="admin/dashboard.php" class="btn btn-primary">Ir al Panel de Administración →</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
