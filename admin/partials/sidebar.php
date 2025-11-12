<?php
$active = basename($_SERVER['PHP_SELF']);
function active_link($file){ global $active; return $active === $file ? 'active' : ''; }
?>
<aside class="sidebar">
	<div class="sidebar-header">
		<a class="sidebar-brand d-flex align-items-center gap-2" href="dashboard.php">
			<div class="logo">A</div>
			<span class="sidebar-brand-text">Admin</span>
		</a>
		<button class="btn btn-outline-muted sidebar-close d-inline-flex d-lg-none" type="button" data-sidebar-toggle aria-label="Cerrar menú">
			<i class="bi bi-x-lg"></i>
		</button>
	</div>
	<nav class="sidebar-nav nav flex-column">
		<a class="nav-link <?php echo active_link('dashboard.php'); ?>" href="dashboard.php" title="Dashboard" data-bs-toggle="tooltip" data-bs-placement="right">
			<span class="nav-icon">🏠</span>
			<span class="nav-label">Dashboard</span>
		</a>
		<a class="nav-link <?php echo active_link('workers.php'); ?>" href="workers.php" title="Trabajadores" data-bs-toggle="tooltip" data-bs-placement="right">
			<span class="nav-icon">👷</span>
			<span class="nav-label">Trabajadores</span>
		</a>
		<a class="nav-link <?php echo active_link('users.php'); ?>" href="users.php" title="Usuarios" data-bs-toggle="tooltip" data-bs-placement="right">
			<span class="nav-icon">👥</span>
			<span class="nav-label">Usuarios</span>
		</a>
		<a class="nav-link <?php echo active_link('reports.php'); ?>" href="#" title="Reportes" data-bs-toggle="tooltip" data-bs-placement="right">
			<span class="nav-icon">📑</span>
			<span class="nav-label">Reportes</span>
		</a>
		<a class="nav-link <?php echo active_link('locations.php'); ?>" href="#" title="Ubicaciones" data-bs-toggle="tooltip" data-bs-placement="right">
			<span class="nav-icon">📍</span>
			<span class="nav-label">Ubicaciones</span>
		</a>
		<a class="nav-link <?php echo active_link('attendance.php'); ?>" href="#" title="Asistencia" data-bs-toggle="tooltip" data-bs-placement="right">
			<span class="nav-icon">🕒</span>
			<span class="nav-label">Asistencia</span>
		</a>
	</nav>
</aside>