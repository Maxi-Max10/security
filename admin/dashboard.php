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


// Total de trabajadores
$total_workers = 0;
if ($conn->query("SHOW TABLES LIKE 'workers'")->num_rows === 1) {
  $total_workers = $conn->query("SELECT COUNT(*) as total FROM workers")->fetch_assoc()['total'];
}

// Últimos usuarios
$recent_users = $conn->query("SELECT id, username, email, role, created_at, is_active FROM users ORDER BY created_at DESC LIMIT 10");

$user = get_user_data();
?>
<?php $page_title = 'Dashboard'; include __DIR__.'/partials/head.php'; ?>
  <div class="admin-layout">
    <?php include __DIR__.'/partials/sidebar.php'; ?>
    <?php include __DIR__.'/partials/header.php'; ?>
    <main class="content container-fluid py-4">
      <?php display_flash_message(); ?>

  <div class="row g-3 mb-4 dashboard-kpis">
        <div class="col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100"><div class="card-body"><h6 class="text-muted">Usuarios</h6><h3 class="fw-bold mb-0"><?php echo $total_users; ?></h3><span class="badge badge-soft">Registrados</span></div></div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100"><div class="card-body"><h6 class="text-muted">Activos</h6><h3 class="fw-bold mb-0"><?php echo $active_users; ?></h3><span class="badge bg-success-subtle text-success">Activos</span></div></div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100"><div class="card-body"><h6 class="text-muted">Trabajadores</h6><h3 class="fw-bold mb-0"><?php echo $total_workers; ?></h3><span class="badge bg-primary-subtle text-primary">Total</span></div></div>
        </div>
        
      </div>

  <div class="row g-3 mb-4 dashboard-shortcuts">
        <div class="col-md-4">
          <div class="card h-100"><div class="card-body"><h5 class="card-title">👷 Trabajadores</h5><p class="text-muted small">Gestiona altas, bajas y ubicaciones</p><a class="btn btn-primary btn-sm" href="workers.php">Abrir</a></div></div>
        </div>
        <div class="col-md-4">
          <div class="card h-100"><div class="card-body"><h5 class="card-title">👥 Usuarios</h5><p class="text-muted small">Permisos y estados</p><a class="btn btn-outline-primary btn-sm" href="users.php">Abrir</a></div></div>
        </div>
        <div class="col-md-4">
          <div class="card h-100"><div class="card-body"><h5 class="card-title">📑 Reportes</h5><p class="text-muted small">Indicadores y descargas</p><button class="btn btn-outline-secondary btn-sm" disabled>Próximamente</button></div></div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center"><h6 class="mb-0">Usuarios recientes</h6><span class="text-muted small">Últimos 10</span></div>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
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
      </div>

      

      
    </main>
  </div>
  <?php $conn->close(); ?>
</body>
</html>
