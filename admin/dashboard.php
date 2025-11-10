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

// Total de trabajadores
$total_workers = 0;
if ($conn->query("SHOW TABLES LIKE 'workers'")->num_rows === 1) {
  $total_workers = $conn->query("SELECT COUNT(*) as total FROM workers")->fetch_assoc()['total'];
}

// Intentos exitosos
$success_attempts = $conn->query("SELECT COUNT(*) as total FROM login_attempts WHERE success = 1")->fetch_assoc()['total'];

// Intentos fallidos
$failed_attempts = $conn->query("SELECT COUNT(*) as total FROM login_attempts WHERE success = 0")->fetch_assoc()['total'];

// Últimos usuarios
$recent_users = $conn->query("SELECT id, username, email, role, created_at, is_active FROM users ORDER BY created_at DESC LIMIT 10");

// Últimos intentos de login
$recent_attempts = $conn->query("SELECT la.*, u.username FROM login_attempts la LEFT JOIN users u ON la.email = u.email ORDER BY la.attempt_time DESC LIMIT 10");

$user = get_user_data();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script defer src="../assets/js/admin.js"></script>
</head>
<body>
  <div class="admin-layout">
    <?php include __DIR__.'/partials/sidebar.php'; ?>
    <?php include __DIR__.'/partials/header.php'; ?>
    <main class="content">
      <?php display_flash_message(); ?>

      <div class="grid kpis">
        <div class="card"><h4>Usuarios</h4><div class="kpi"><?php echo $total_users; ?></div><div class="meta">Registrados</div></div>
        <div class="card"><h4>Activos</h4><div class="kpi"><?php echo $active_users; ?></div><div class="meta">Usuarios activos</div></div>
        <div class="card"><h4>Trabajadores</h4><div class="kpi"><?php echo $total_workers; ?></div><div class="meta">Total registrados</div></div>
        <div class="card"><h4>Hoy</h4><div class="kpi"><?php echo $today_users; ?></div><div class="meta">Registros hoy</div></div>
        <div class="card"><h4>Intentos</h4><div class="kpi"><?php echo $total_attempts; ?></div><div class="meta">Logins totales</div></div>
      </div>

      <section class="section">
        <h3>Accesos rápidos</h3>
        <div class="grid">
          <div class="card" style="grid-column: span 4"><h4>👷 Trabajadores</h4><p class="meta">Gestiona altas, bajas y ubicaciones</p><p><a class="btn primary" href="workers.php">Abrir</a></p></div>
          <div class="card" style="grid-column: span 4"><h4>👥 Usuarios</h4><p class="meta">Permisos y estados</p><p><a class="btn" href="users.php">Abrir</a></p></div>
          <div class="card" style="grid-column: span 4"><h4>📑 Reportes</h4><p class="meta">Indicadores y descargas</p><p><a class="btn" href="#">Próximamente</a></p></div>
        </div>
      </section>

      <section class="section">
        <h3>Usuarios recientes</h3>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>ID</th><th>Usuario</th><th>Email</th><th>Rol</th><th>Registro</th><th>Estado</th></tr></thead>
            <tbody>
              <?php while ($usr = $recent_users->fetch_assoc()): ?>
              <tr>
                <td><?php echo $usr['id']; ?></td>
                <td><?php echo htmlspecialchars($usr['username']); ?></td>
                <td><?php echo htmlspecialchars($usr['email']); ?></td>
                <td><?php echo strtoupper($usr['role']); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($usr['created_at'])); ?></td>
                <td><?php echo $usr['is_active'] ? 'ACTIVO' : 'INACTIVO'; ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </section>

      <section class="section">
        <h3>Últimos intentos de login</h3>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Usuario</th><th>Email</th><th>IP</th><th>Fecha</th><th>Resultado</th></tr></thead>
            <tbody>
              <?php while ($attempt = $recent_attempts->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($attempt['username'] ?? 'Desconocido'); ?></td>
                <td><?php echo htmlspecialchars($attempt['email']); ?></td>
                <td><?php echo htmlspecialchars($attempt['ip_address']); ?></td>
                <td><?php echo date('d/m/Y H:i:s', strtotime($attempt['attempt_time'])); ?></td>
                <td><?php echo $attempt['success'] ? '✓ EXITOSO' : '✕ FALLIDO'; ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </section>

      <section class="grid" style="margin-top:16px">
        <div class="card" style="grid-column: span 6">
          <h4>Tasa de éxito</h4>
          <div class="kpi"><?php echo $total_attempts > 0 ? round(($success_attempts / $total_attempts) * 100) : 0; ?>%</div>
          <div class="meta">Éxito vs total</div>
        </div>
        <div class="card" style="grid-column: span 6">
          <h4>Fallidos</h4>
          <div class="kpi"><?php echo $failed_attempts; ?></div>
          <div class="meta">Últimos registros</div>
        </div>
      </section>
    </main>
  </div>
  <?php $conn->close(); ?>
</body>
</html>
