<?php
$segments = [];
// Basic breadcrumb using script name; could expand with routing later
$current = basename($_SERVER['PHP_SELF']);
$map = [
  'dashboard.php' => 'Dashboard',
  'workers.php' => 'Trabajadores',
  'users.php' => 'Usuarios',
<?php
$segments = [];
$current = basename($_SERVER['PHP_SELF']);
$map = [
  'dashboard.php' => 'Dashboard',
  'workers.php' => 'Trabajadores',
  'users.php' => 'Usuarios',
];
if (isset($map[$current])) { $segments[] = $map[$current]; }
?>
<nav aria-label="breadcrumb" class="breadcrumb-wrapper mb-3">
  <ol class="breadcrumb breadcrumb-soft mb-0">
    <li class="breadcrumb-item"><span>Admin</span></li>
    <?php foreach ($segments as $label): ?>
      <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($label); ?></li>
    <?php endforeach; ?>
  </ol>
</nav>