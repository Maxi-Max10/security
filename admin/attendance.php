<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();
require_admin();

$conn = getDBConnection();

$search = trim($_GET['search'] ?? '');
$params = [];
$sql = "SELECT wa.id, wa.latitude, wa.longitude, wa.recorded_at, wa.attachment_path, wa.attachment_original, wa.created_at,
               w.first_name, w.last_name, w.email, w.work_place, w.dni
        FROM worker_attendance wa
        INNER JOIN workers w ON w.id = wa.worker_id";

if ($search !== '') {
    $sql .= " WHERE CONCAT_WS(' ', w.first_name, w.last_name) LIKE ?
              OR w.email LIKE ?
              OR w.dni LIKE ?
              OR w.work_place LIKE ?";
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
}

$sql .= " ORDER BY wa.recorded_at DESC LIMIT 150";

$stmt = $conn->prepare($sql);
if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = 'Asistencia';
include __DIR__ . '/partials/head.php';
?>
  <div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <?php include __DIR__ . '/partials/header.php'; ?>
    <main class="content container-fluid py-4">
      <?php display_flash_message(); ?>

      <div class="card mb-3 shadow-sm">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
          <div>
            <h1 class="h4 mb-1">Control de asistencia</h1>
            <p class="text-muted mb-0">Monitorea en tiempo real los registros enviados por cada trabajador.</p>
          </div>
          <form class="d-flex gap-2" method="get" action="attendance.php" autocomplete="off">
            <input type="text" class="form-control" name="search" placeholder="Buscar por nombre, correo, DNI o lugar" value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-primary" type="submit">Buscar</button>
          </form>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h6 class="mb-0">Registros recientes</h6>
          <span class="badge bg-primary-subtle text-primary">Total: <?php echo count($records); ?></span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Trabajador</th>
                <th>Documento</th>
                <th>Contacto</th>
                <th>Lugar de trabajo</th>
                <th>Fecha y hora</th>
                <th>Ubicación</th>
                <th>Adjunto</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$records): ?>
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">Sin registros de asistencia todavía.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($records as $row): ?>
                  <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td>
                      <div class="fw-semibold"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
                      <small class="text-muted">Registrado el <?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($row['dni'] ?: '—'); ?></td>
                    <td>
                      <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($row['email']); ?>
                      </a>
                    </td>
                    <td><?php echo htmlspecialchars($row['work_place'] ?: '—'); ?></td>
                    <td>
                      <div><?php echo date('d/m/Y', strtotime($row['recorded_at'])); ?></div>
                      <small class="text-muted"><?php echo date('H:i:s', strtotime($row['recorded_at'])); ?></small>
                    </td>
                    <td>
                      <a href="https://www.google.com/maps?q=<?php echo rawurlencode($row['latitude'] . ',' . $row['longitude']); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Ver mapa</a>
                      <div class="small text-muted mt-1">
                        Lat <?php echo number_format((float)$row['latitude'], 5); ?> · Lng <?php echo number_format((float)$row['longitude'], 5); ?>
                      </div>
                    </td>
                    <td>
                      <?php if (!empty($row['attachment_path'])): ?>
                        <a href="../<?php echo htmlspecialchars($row['attachment_path']); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Ver adjunto</a>
                        <?php if (!empty($row['attachment_original'])): ?>
                          <?php
                            $originalName = $row['attachment_original'];
                            $displayName = $originalName;
                            if (strlen($displayName) > 28) {
                              $displayName = substr($displayName, 0, 25) . '…';
                            }
                          ?>
                          <div class="small text-muted mt-1" title="<?php echo htmlspecialchars($originalName); ?>"><?php echo htmlspecialchars($displayName); ?></div>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
  <?php $conn->close(); ?>
</body>
</html>
