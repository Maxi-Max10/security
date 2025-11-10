<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Requerir autenticación y rol de administrador
require_login();
require_admin();

// Obtener estadísticas
$conn = getDBConnection();

// Total de usuarios
$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];

// Usuarios activos
$active_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1")->fetch_assoc()['total'];

// Usuarios registrados hoy
$today_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['total'];

// Total de intentos de login
$total_attempts = $conn->query("SELECT COUNT(*) as total FROM login_attempts")->fetch_assoc()['total'];

// Intentos exitosos
$success_attempts = $conn->query("SELECT COUNT(*) as total FROM login_attempts WHERE success = 1")->fetch_assoc()['total'];

// Intentos fallidos
$failed_attempts = $conn->query("SELECT COUNT(*) as total FROM login_attempts WHERE success = 0")->fetch_assoc()['total'];

// Últimos usuarios
$recent_users = $conn->query("SELECT id, username, email, role, created_at, is_active FROM users ORDER BY created_at DESC LIMIT 10");

// Últimos intentos de login
$recent_attempts = $conn->query("SELECT la.*, u.username FROM login_attempts la LEFT JOIN users u ON la.email = u.email ORDER BY la.attempt_time DESC LIMIT 10");

$user = get_current_user();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="navbar">
        <div class="container">
            <div class="nav-content">
                <h2>Panel de Administración</h2>
                <div class="nav-right">
                    <span class="user-info"><?php echo htmlspecialchars($user['username']); ?></span>
                    <a href="users.php" class="btn btn-small btn-outline">Gestionar Usuarios</a>
                    <a href="../dashboard.php" class="btn btn-small">Mi Perfil</a>
                    <a href="../logout.php" class="btn btn-small btn-secondary">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="dashboard-box">
            <?php display_flash_message(); ?>
            
            <h1>📊 Dashboard Administrativo</h1>
            
            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-icon">👥</span>
                    <span class="stat-value"><?php echo $total_users; ?></span>
                    <span class="stat-label">Total Usuarios</span>
                </div>
                
                <div class="stat-card success">
                    <span class="stat-icon">✓</span>
                    <span class="stat-value"><?php echo $active_users; ?></span>
                    <span class="stat-label">Usuarios Activos</span>
                </div>
                
                <div class="stat-card warning">
                    <span class="stat-icon">🆕</span>
                    <span class="stat-value"><?php echo $today_users; ?></span>
                    <span class="stat-label">Registros Hoy</span>
                </div>
                
                <div class="stat-card info">
                    <span class="stat-icon">🔐</span>
                    <span class="stat-value"><?php echo $total_attempts; ?></span>
                    <span class="stat-label">Intentos Login</span>
                </div>
            </div>
            
            <!-- Información del Admin -->
            <div class="user-profile">
                <h2>👤 Tu Información</h2>
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
                        <span class="badge badge-admin">ADMINISTRADOR</span>
                    </div>
                    <div class="info-item">
                        <strong>Miembro desde:</strong>
                        <span><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Usuarios Recientes -->
            <div class="user-profile">
                <h2>🆕 Usuarios Recientes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Registro</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($usr = $recent_users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $usr['id']; ?></td>
                            <td><?php echo htmlspecialchars($usr['username']); ?></td>
                            <td><?php echo htmlspecialchars($usr['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $usr['role']; ?>">
                                    <?php echo strtoupper($usr['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($usr['created_at'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $usr['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $usr['is_active'] ? 'ACTIVO' : 'INACTIVO'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Intentos de Login Recientes -->
            <div class="user-profile">
                <h2>🔐 Últimos Intentos de Login</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>IP</th>
                            <th>Fecha/Hora</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($attempt = $recent_attempts->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($attempt['username'] ?? 'Desconocido'); ?></td>
                            <td><?php echo htmlspecialchars($attempt['email']); ?></td>
                            <td><?php echo htmlspecialchars($attempt['ip_address']); ?></td>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($attempt['attempt_time'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $attempt['success'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $attempt['success'] ? '✓ EXITOSO' : '✕ FALLIDO'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Estadísticas de Login -->
            <div class="stats-grid" style="margin-top: 30px;">
                <div class="stat-card success">
                    <span class="stat-icon">✓</span>
                    <span class="stat-value"><?php echo $success_attempts; ?></span>
                    <span class="stat-label">Logins Exitosos</span>
                </div>
                
                <div class="stat-card" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <span class="stat-icon">✕</span>
                    <span class="stat-value"><?php echo $failed_attempts; ?></span>
                    <span class="stat-label">Logins Fallidos</span>
                </div>
                
                <div class="stat-card info">
                    <span class="stat-icon">📊</span>
                    <span class="stat-value"><?php echo $total_attempts > 0 ? round(($success_attempts / $total_attempts) * 100) : 0; ?>%</span>
                    <span class="stat-label">Tasa de Éxito</span>
                </div>
            </div>
        </div>
    </div>
    
    <?php $conn->close(); ?>
</body>
</html>
