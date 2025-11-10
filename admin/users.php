<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Requerir autenticación y rol de administrador
require_login();
require_admin();

$conn = getDBConnection();
$message = '';
$message_type = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Token de seguridad inválido.';
        $message_type = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if ($action === 'toggle_status' && $user_id > 0) {
            // No permitir desactivar el propio usuario
            if ($user_id == $_SESSION['user_id']) {
                $message = 'No puedes desactivar tu propia cuenta.';
                $message_type = 'error';
            } else {
                $stmt = $conn->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $message = 'Estado del usuario actualizado correctamente.';
                    $message_type = 'success';
                }
                $stmt->close();
            }
        } elseif ($action === 'delete' && $user_id > 0) {
            // No permitir eliminar el propio usuario
            if ($user_id == $_SESSION['user_id']) {
                $message = 'No puedes eliminar tu propia cuenta.';
                $message_type = 'error';
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $message = 'Usuario eliminado correctamente.';
                    $message_type = 'success';
                }
                $stmt->close();
            }
        } elseif ($action === 'change_role' && $user_id > 0) {
            // No permitir cambiar el propio rol
            if ($user_id == $_SESSION['user_id']) {
                $message = 'No puedes cambiar tu propio rol.';
                $message_type = 'error';
            } else {
                $new_role = $_POST['role'] === 'admin' ? 'admin' : 'user';
                $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->bind_param("si", $new_role, $user_id);
                if ($stmt->execute()) {
                    $message = 'Rol del usuario actualizado correctamente.';
                    $message_type = 'success';
                }
                $stmt->close();
            }
        }
    }
}

// Obtener todos los usuarios
$users = $conn->query("SELECT id, username, email, role, created_at, last_login, is_active FROM users ORDER BY created_at DESC");

$current_user = get_user_data();
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - Panel Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .action-buttons button {
            padding: 6px 12px;
            font-size: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-toggle {
            background: var(--warning-color);
            color: white;
        }
        .btn-toggle:hover {
            background: #d97706;
        }
        .btn-delete {
            background: var(--error-color);
            color: white;
        }
        .btn-delete:hover {
            background: #dc2626;
        }
        .btn-promote {
            background: var(--success-color);
            color: white;
        }
        .btn-promote:hover {
            background: #059669;
        }
        .btn-demote {
            background: var(--info-color);
            color: white;
        }
        .btn-demote:hover {
            background: #2563eb;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="container">
            <div class="nav-content">
                <h2>Gestionar Usuarios</h2>
                <div class="nav-right">
                    <span class="user-info"><?php echo htmlspecialchars($current_user['username']); ?></span>
                    <a href="dashboard.php" class="btn btn-small">Dashboard</a>
                    <a href="../logout.php" class="btn btn-small btn-secondary">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="dashboard-box">
            <h1>👥 Gestión de Usuarios</h1>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Registro</th>
                            <th>Último Login</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['role']; ?>">
                                    <?php echo strtoupper($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td><?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Nunca'; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $user['is_active'] ? 'ACTIVO' : 'INACTIVO'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <div class="action-buttons">
                                    <!-- Toggle Estado -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn-toggle" onclick="return confirm('¿Cambiar estado del usuario?')">
                                            <?php echo $user['is_active'] ? '🔒 Desactivar' : '🔓 Activar'; ?>
                                        </button>
                                    </form>
                                    
                                    <!-- Cambiar Rol -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="change_role">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="role" value="<?php echo $user['role'] === 'admin' ? 'user' : 'admin'; ?>">
                                        <button type="submit" class="<?php echo $user['role'] === 'admin' ? 'btn-demote' : 'btn-promote'; ?>" 
                                                onclick="return confirm('¿Cambiar rol del usuario?')">
                                            <?php echo $user['role'] === 'admin' ? '👤 Hacer Usuario' : '⭐ Hacer Admin'; ?>
                                        </button>
                                    </form>
                                    
                                    <!-- Eliminar -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn-delete" onclick="return confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')">
                                            🗑️ Eliminar
                                        </button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <span style="color: var(--gray); font-size: 12px;">Tu cuenta</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="welcome-message" style="margin-top: 30px;">
                <h3>💡 Información</h3>
                <p>Desde aquí puedes gestionar todos los usuarios del sistema. Puedes activar/desactivar cuentas, cambiar roles entre usuario y administrador, o eliminar usuarios si es necesario.</p>
                <p style="margin-top: 10px;"><strong>Nota:</strong> No puedes modificar tu propia cuenta desde esta sección por seguridad.</p>
            </div>
        </div>
    </div>
    
    <?php $conn->close(); ?>
</body>
</html>
