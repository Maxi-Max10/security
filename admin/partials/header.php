<?php
if (!isset($current_user)) { $current_user = get_user_data(); }
?>
<nav class="navbar navbar-expand-lg header">
	<div class="container-fluid">
		<button class="btn btn-outline-muted me-2" type="button" onclick="document.querySelector('.sidebar').classList.toggle('collapsed')">☰</button>
		<a class="navbar-brand" href="#">Panel Administrativo</a>
		<div class="ms-auto d-flex align-items-center gap-2">
			<button class="btn btn-outline-muted" data-toggle-theme type="button">🌓 Tema</button>
			<span class="text-muted small"><?php echo htmlspecialchars($current_user['username'] ?? ''); ?></span>
			<a class="btn btn-outline-danger" href="../logout.php">Salir</a>
		</div>
	</div>
 </nav>