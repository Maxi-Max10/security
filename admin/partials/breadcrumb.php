<?php
$segments = [];
// Basic breadcrumb using script name; could expand with routing later
$current = basename($_SERVER['PHP_SELF']);
$map = [
  'dashboard.php' => 'Dashboard',
  'workers.php' => 'Trabajadores',
  'users.php' => 'Usuarios',
];
if (isset($map[$current])) { $segments[] = $map[$current]; }
?>
<nav aria-label="breadcrumb" style="font-size:12px;color:var(--text-muted);margin:8px 0 16px 0;">
  <span style="opacity:.7;">Admin</span>
  <?php foreach ($segments as $i => $seg): ?>
    <span style="opacity:.5;"> / </span><strong style="color:var(--text);font-weight:600;"><?php echo htmlspecialchars($seg); ?></strong>
  <?php endforeach; ?>
</nav>