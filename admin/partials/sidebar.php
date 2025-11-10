<?php
$active = basename($_SERVER['PHP_SELF']);
function active_link($file){ global $active; return $active === $file ? 'active' : ''; }
?>
<div class="sidebar">
	<div class="brand"><div class="logo">A</div><span>Admin</span></div>
	<nav class="nav">
		<a class="<?php echo active_link('dashboard.php'); ?>" href="dashboard.php">🏠 <span>Dashboard</span></a>
		<a class="<?php echo active_link('workers.php'); ?>" href="workers.php">👷 <span>Trabajadores</span></a>
		<a class="<?php echo active_link('users.php'); ?>" href="users.php">👥 <span>Usuarios</span></a>
		<a class="<?php echo active_link('reports.php'); ?>" href="#">📑 <span>Reportes</span></a>
		<a class="<?php echo active_link('locations.php'); ?>" href="#">📍 <span>Ubicaciones</span></a>
		<a class="<?php echo active_link('attendance.php'); ?>" href="#">🕒 <span>Asistencia</span></a>
	</nav>
</div>