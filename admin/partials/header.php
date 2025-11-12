<?php
if (!isset($current_user)) { $current_user = get_user_data(); }
?>
<nav class="navbar navbar-expand-lg header">
	<div class="container-fluid">
		<div class="d-flex align-items-center gap-2">
			<button class="btn btn-outline-muted d-inline-flex d-lg-none" type="button" data-sidebar-toggle aria-label="Abrir menú">
				<i class="bi bi-list"></i>
			</button>
			<button class="btn btn-outline-muted d-none d-lg-inline-flex" type="button" data-sidebar-collapse aria-label="Colapsar barra lateral">
				<i class="bi bi-layout-sidebar"></i>
			</button>
			<a class="navbar-brand m-0" href="dashboard.php">Panel Administrativo</a>
		</div>
		<div class="ms-auto d-flex align-items-center gap-2">
			<button class="btn btn-outline-muted" data-toggle-theme type="button">🌓 Tema</button>
			<span class="text-muted small"><?php echo htmlspecialchars($current_user['username'] ?? ''); ?></span>
			<a class="btn btn-outline-danger" href="../logout.php">Salir</a>
		</div>
	</div>
 </nav>