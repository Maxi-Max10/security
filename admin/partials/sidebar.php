<?php
$active = basename($_SERVER['PHP_SELF']);
function active_link($file){ global $active; return $active === $file ? 'active' : ''; }
?>
<aside class="sidebar border-end">
	<div class="p-3">
		<a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php"><div class="logo">A</div><span>Admin</span></a>
	</div>
	<nav class="nav flex-column px-2">
		<a class="nav-link <?php echo active_link('dashboard.php'); ?>" href="dashboard.php">🏠 <span>Dashboard</span></a>
		<a class="nav-link <?php echo active_link('workers.php'); ?>" href="workers.php">👷 <span>Trabajadores</span></a>
		<a class="nav-link <?php echo active_link('users.php'); ?>" href="users.php">👥 <span>Usuarios</span></a>
		<a class="nav-link <?php echo active_link('reports.php'); ?>" href="#">📑 <span>Reportes</span></a>
		<a class="nav-link <?php echo active_link('locations.php'); ?>" href="#">📍 <span>Ubicaciones</span></a>
		<a class="nav-link <?php echo active_link('attendance.php'); ?>" href="attendance.php">🕒 <span>Asistencia</span></a>
	</nav>
</aside>