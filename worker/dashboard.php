<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

require_worker_login();

$worker = get_worker_profile();

if (!$worker) {
    worker_logout();
    redirect('login.php', 'No pudimos cargar tus datos. Vuelve a iniciar sesión.', 'warning');
}

$fullName = trim($worker['first_name'] . ' ' . $worker['last_name']);
$mapLink = $worker['address_url'] ?? null;
$address = $worker['address_text'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Trabajador - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="navbar">
        <div class="container">
            <div class="nav-content">
                <h2>Panel del Trabajador</h2>
                <div class="nav-right">
                    <span class="user-info"><?php echo htmlspecialchars($fullName); ?></span>
                    <a href="logout.php" class="btn btn-small btn-secondary">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php display_flash_message(); ?>

        <div class="dashboard-box">
            <h1>Hola <?php echo htmlspecialchars($worker['first_name']); ?> 👋</h1>
            <p style="text-align:center;color:var(--gray);margin-bottom:24px;">Aquí podrás ver tu información laboral asignada por el administrador.</p>

            <div class="user-profile">
                <h2>Tu Información</h2>
                <div class="info-grid">
                    <div class="info-item"><strong>Nombre completo</strong><span><?php echo htmlspecialchars($fullName); ?></span></div>
                    <div class="info-item"><strong>DNI</strong><span><?php echo htmlspecialchars($worker['dni']); ?></span></div>
                    <div class="info-item"><strong>Correo</strong><span><?php echo htmlspecialchars($worker['email']); ?></span></div>
                    <div class="info-item"><strong>Lugar de trabajo</strong><span><?php echo htmlspecialchars($worker['work_place']); ?></span></div>
                    <div class="info-item"><strong>CVU / Alias</strong><span><?php echo $worker['cvu_alias'] ? htmlspecialchars($worker['cvu_alias']) : '<em>No asignado</em>'; ?></span></div>
                    <div class="info-item"><strong>Edad</strong><span><?php echo $worker['age'] ? intval($worker['age']) . ' años' : '<em>No informado</em>'; ?></span></div>
                </div>
            </div>

            <div class="welcome-message">
                <h3>Indicaciones</h3>
                <p>Si necesitas actualizar tus datos personales o detectar información incorrecta, comunícate con el administrador de recursos humanos.</p>
                <?php if ($mapLink || $address): ?>
                    <p style="margin-top:16px;">
                        <strong>Ubicación asignada:</strong>
                        <?php if ($mapLink): ?>
                            <a href="<?php echo htmlspecialchars($mapLink); ?>" target="_blank" rel="noopener" class="btn btn-outline btn-small" style="margin-left:8px;">Ver en Google Maps</a>
                        <?php endif; ?>
                    </p>
                    <?php if ($address): ?>
                        <p style="color:var(--gray);margin-top:8px;">Dirección de referencia: <?php echo htmlspecialchars($address); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
