<?php
if (!isset($current_user)) { $current_user = get_user_data(); }
?>
<div class="header">
	<div class="left">
		<button class="btn" onclick="document.querySelector('.sidebar').classList.toggle('collapsed')">☰</button>
		<strong>Panel Administrativo</strong>
	</div>
	<div class="right">
		<button class="btn" data-toggle-theme>🌓 Tema</button>
		<span style="color:#94a3b8;"><?php echo htmlspecialchars($current_user['username'] ?? ''); ?></span>
		<a class="btn" href="../logout.php">Salir</a>
	</div>
</div>