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
<?php $page_title = 'Usuarios'; include __DIR__.'/partials/head.php'; ?>
  <div class="admin-layout">
    <?php include __DIR__.'/partials/sidebar.php'; ?>
    <?php include __DIR__.'/partials/header.php'; ?>
    <main class="content container-fluid py-4">
      <?php include __DIR__.'/partials/breadcrumb.php'; ?>
      <h2 class="mt-0">👥 Gestión de Usuarios</h2>

      <?php if ($message): ?>
          <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>">
              <?php echo $message; ?>
          </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center"><strong>Usuarios</strong><span class="small text-muted">Administración</span></div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
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
                                <?php if ($user['role'] === 'admin'): ?>
                                  <span class="badge bg-primary-subtle text-primary">ADMIN</span>
                                <?php else: ?>
                                  <span class="badge bg-secondary-subtle text-secondary">USER</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td><?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Nunca'; ?></td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                  <span class="badge bg-success-subtle text-success">ACTIVO</span>
                                <?php else: ?>
                                  <span class="badge bg-danger-subtle text-danger">INACTIVO</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <div class="btn-group btn-group-sm" role="group">
                                    <!-- Toggle Estado -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-outline-secondary" onclick="return confirm('¿Cambiar estado del usuario?')">
                                            <?php echo $user['is_active'] ? 'Desactivar' : 'Activar'; ?>
                                        </button>
                                    </form>
                                    <!-- Cambiar Rol -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="change_role">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="role" value="<?php echo $user['role'] === 'admin' ? 'user' : 'admin'; ?>">
                                        <button type="submit" class="btn btn-outline-primary" onclick="return confirm('¿Cambiar rol del usuario?')">
                                            <?php echo $user['role'] === 'admin' ? 'Hacer Usuario' : 'Hacer Admin'; ?>
                                        </button>
                                    </form>
                                    <!-- Eliminar -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <span class="text-muted small">Tu cuenta</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card mt-4">
          <div class="card-body">
            <h5 class="card-title">💡 Información</h5>
            <p class="mb-2">Desde aquí puedes gestionar todos los usuarios del sistema. Puedes activar/desactivar cuentas, cambiar roles entre usuario y administrador, o eliminar usuarios si es necesario.</p>
            <p class="mb-0 text-muted"><strong>Nota:</strong> No puedes modificar tu propia cuenta desde esta sección por seguridad.</p>
          </div>
        </div>
        </main>
    </div>
    <?php $conn->close(); ?>
