<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

require_worker_login();

$worker = get_worker_profile();

if (!$worker) {
    worker_logout();
    redirect('login.php', 'No pudimos cargar tus datos. Vuelve a iniciar sesión.', 'warning');
}

$attendanceErrors = [];
$latestRecords = [];
$uploadDir = rtrim(dirname(__DIR__), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'attendance' . DIRECTORY_SEPARATOR;

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $attendanceErrors[] = 'Token de seguridad inválido. Por favor, vuelve a intentarlo.';
    }

    $latRaw = trim($_POST['latitude'] ?? '');
    $lngRaw = trim($_POST['longitude'] ?? '');
    $latitude = $latRaw !== '' && is_numeric($latRaw) ? (float) $latRaw : null;
    $longitude = $lngRaw !== '' && is_numeric($lngRaw) ? (float) $lngRaw : null;
    $recordedAtRaw = trim($_POST['recorded_at'] ?? '');

    if ($latitude === null || $longitude === null) {
        $attendanceErrors[] = 'No pudimos detectar tu ubicación. Activa el GPS e inténtalo nuevamente.';
    }

    $datetime = null;
    if ($recordedAtRaw) {
        $datetime = date_create($recordedAtRaw);
    }
    if (!$datetime) {
        $datetime = new DateTime('now', new DateTimeZone(date_default_timezone_get()));
    }

    $attachmentPath = null;
    $attachmentOriginal = null;

    if (!empty($_FILES['attachment']['name'])) {
        $file = $_FILES['attachment'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $maxSize = 8 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                $attendanceErrors[] = 'El archivo es demasiado grande. Máximo 8 MB.';
            } else {
                $mime = null;
                if (class_exists('finfo')) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($file['tmp_name']);
                }
                if (!$mime && function_exists('mime_content_type')) {
                    $mime = mime_content_type($file['tmp_name']);
                }
                if (!$mime) {
                    $mime = 'application/octet-stream';
                }
                $allowedMimes = [
                    'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                    'application/pdf', 'image/heic', 'image/heif'
                ];
                if (!in_array($mime, $allowedMimes, true)) {
                    $attendanceErrors[] = 'Formato de archivo no permitido. Sube una imagen o PDF.';
                } else {
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $extension = strtolower(preg_replace('/[^a-z0-9]/', '', $extension));
                    if (!$extension) {
                        $extension = $mime === 'application/pdf' ? 'pdf' : 'jpg';
                    }
                    $safeName = 'att_' . $worker['id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                    $destination = $uploadDir . $safeName;

                    if (!move_uploaded_file($file['tmp_name'], $destination)) {
                        $attendanceErrors[] = 'No pudimos guardar el archivo adjunto. Intenta nuevamente.';
                    } else {
                        $attachmentPath = 'uploads/attendance/' . $safeName;
                        $originalName = preg_replace('/["\\<>\r\n]+/', '', $file['name']);
                        $attachmentOriginal = substr($originalName, 0, 200);
                    }
                }
            }
        } else {
            $attendanceErrors[] = 'Ocurrió un error al subir el archivo. Código: ' . intval($file['error']);
        }
    }

    if (!$attendanceErrors) {
        $conn = getDBConnection();
        $stmt = $conn->prepare(
            "INSERT INTO worker_attendance (worker_id, latitude, longitude, recorded_at, attachment_path, attachment_original)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $recordedAtFormatted = $datetime->format('Y-m-d H:i:s');
        $stmt->bind_param(
            'iddsss',
            $worker['id'],
            $latitude,
            $longitude,
            $recordedAtFormatted,
            $attachmentPath,
            $attachmentOriginal
        );

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            $_SESSION['attendance_success'] = true;
            redirect('dashboard.php');
        } else {
            $attendanceErrors[] = 'No pudimos registrar tu asistencia. Intenta nuevamente.';
            error_log('worker_attendance_insert_error: ' . $stmt->error);
            $stmt->close();
            $conn->close();
        }
    }
}

$latestRecords = get_worker_attendance($worker['id']);
$lastRecord = $latestRecords[0] ?? null;

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

        <!-- Modal de confirmación de asistencia -->
        <div class="modal<?php echo !empty($_SESSION['attendance_success']) ? ' is-visible' : ''; ?>" id="attendanceModal" aria-hidden="<?php echo !empty($_SESSION['attendance_success']) ? 'false' : 'true'; ?>">
            <div class="modal-backdrop" data-modal-close></div>
            <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="attendanceModalTitle">
                <div class="modal-icon">✓</div>
                <h2 id="attendanceModalTitle">Asistencia registrada</h2>
                <p class="modal-text">Tu asistencia fue registrada correctamente. ¡Buen trabajo!</p>
                <button type="button" class="btn btn-primary btn-block" data-modal-close>Entendido</button>
            </div>
        </div>

        <?php if ($attendanceErrors): ?>
            <div class="alert alert-error">
                <?php foreach ($attendanceErrors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-box">
            <h1>Bienvenido/a <?php echo htmlspecialchars($worker['first_name']); ?> 👋</h1>
            <p style="text-align:center;color:var(--gray);margin-bottom:16px;">Registra tu asistencia de forma rápida y segura.</p>

            <div class="quick-stats">
                <?php if ($lastRecord): ?>
                    <div class="chip chip-success">Último registro: <?php echo date('d/m/Y H:i', strtotime($lastRecord['recorded_at'])); ?></div>
                    <div class="chip">Lat <?php echo htmlspecialchars(number_format((float)$lastRecord['latitude'], 5)); ?> · Lng <?php echo htmlspecialchars(number_format((float)$lastRecord['longitude'], 5)); ?></div>
                <?php else: ?>
                    <div class="chip chip-muted">Aún no registraste asistencia</div>
                <?php endif; ?>
            </div>

            <div class="welcome-message">
                <h3>Indicaciones</h3>
                <p>Presiona “Obtener ubicación” y luego “Registrar asistencia”.</p>
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

            <div class="attendance-box">
                <div class="attendance-header">
                    <h2>Registrar asistencia</h2>
                    <p>Captura tu ubicación actual, confirma hora y agrega una foto o documento si es necesario.</p>
                </div>

                <form class="attendance-form" method="POST" enctype="multipart/form-data" autocomplete="off" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                    <input type="hidden" name="latitude" id="latitudeInput">
                    <input type="hidden" name="longitude" id="longitudeInput">
                    <input type="hidden" name="recorded_at" id="recordedAtInput">

                    <div class="status-card" id="locationStatus" data-state="idle">
                        <div>
                            <h4>Ubicación</h4>
                            <p id="locationSummary">Listo para captar tu ubicación actual.</p>
                        </div>
                        <button class="btn btn-outline btn-small" id="captureLocationBtn" type="button">Obtener ubicación</button>
                    </div>

                    <div class="time-card">
                        <h4>Fecha y hora</h4>
                        <p id="clockDisplay">--:--</p>
                        <small>Se guarda automáticamente al enviar.</small>
                    </div>

                    <div class="file-card">
                        <label for="attachment" class="file-label">Foto o archivo (opcional)</label>
                        <input type="file" name="attachment" id="attachment" accept="image/*,application/pdf" class="file-input">
                        <small>Formatos permitidos: JPG, PNG, WEBP, GIF, PDF. Máximo 8 MB.</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="submitAttendance" disabled>Registrar asistencia</button>
                </form>
            </div>

            <?php if ($latestRecords): ?>
                <div class="history-box">
                    <h2>Últimos registros</h2>
                    <div class="history-list">
                        <?php foreach ($latestRecords as $record): ?>
                            <article class="history-item">
                                <div class="history-meta">
                                    <strong><?php echo date('d/m/Y H:i', strtotime($record['recorded_at'])); ?></strong>
                                    <span>Lat: <?php echo htmlspecialchars(number_format((float)$record['latitude'], 5)); ?> | Lng: <?php echo htmlspecialchars(number_format((float)$record['longitude'], 5)); ?></span>
                                </div>
                                <div class="history-actions">
                                    <a href="https://www.google.com/maps?q=<?php echo rawurlencode($record['latitude'] . ',' . $record['longitude']); ?>" target="_blank" rel="noopener" class="btn btn-outline btn-small">Ver mapa</a>
                                    <?php if (!empty($record['attachment_path'])): ?>
                                        <a href="../<?php echo htmlspecialchars($record['attachment_path']); ?>" class="btn btn-outline btn-small" target="_blank" rel="noopener">Ver archivo</a>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        (function() {
            const captureBtn = document.getElementById('captureLocationBtn');
            const submitBtn = document.getElementById('submitAttendance');
            const locationSummary = document.getElementById('locationSummary');
            const latitudeInput = document.getElementById('latitudeInput');
            const longitudeInput = document.getElementById('longitudeInput');
            const recordedAtInput = document.getElementById('recordedAtInput');
            const clockDisplay = document.getElementById('clockDisplay');
            const locationStatus = document.getElementById('locationStatus');
            const modal = document.getElementById('attendanceModal');

            if (modal) {
                const closeElements = modal.querySelectorAll('[data-modal-close]');
                closeElements.forEach(function(el) {
                    el.addEventListener('click', function() {
                        modal.classList.remove('is-visible');
                        modal.setAttribute('aria-hidden', 'true');
                    });
                });
            }

            let locationCaptured = false;

            const updateClock = () => {
                const now = new Date();
                const options = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
                clockDisplay.textContent = now.toLocaleTimeString('es-ES', options);
                if (locationCaptured) {
                    recordedAtInput.value = now.toISOString();
                }
            };

            setInterval(updateClock, 1000);
            updateClock();

            function setStatus(message, status) {
                locationSummary.textContent = message;
                locationStatus.dataset.state = status;
            }

            function handleLocationSuccess(position) {
                const { latitude, longitude } = position.coords;
                latitudeInput.value = latitude.toFixed(7);
                longitudeInput.value = longitude.toFixed(7);
                locationCaptured = true;
                recordedAtInput.value = new Date().toISOString();
                submitBtn.disabled = false;
                captureBtn.textContent = 'Actualizar ubicación';
                setStatus(`Ubicación actualizada ✔ Lat ${latitude.toFixed(5)}, Lng ${longitude.toFixed(5)}`, 'success');
            }

            function handleLocationError(error) {
                submitBtn.disabled = true;
                locationCaptured = false;
                const messages = {
                    1: 'Activa los permisos de ubicación en tu dispositivo.',
                    2: 'No pudimos obtener tu ubicación. Verifica la señal.',
                    3: 'La solicitud de ubicación expiró. Intenta nuevamente.'
                };
                setStatus(messages[error.code] || 'No pudimos obtener la ubicación. Intenta de nuevo.', 'error');
            }

            captureBtn.addEventListener('click', function() {
                if (!navigator.geolocation) {
                    setStatus('Tu dispositivo no admite geolocalización.', 'error');
                    return;
                }

                setStatus('Obteniendo ubicación, espera unos segundos...', 'loading');
                submitBtn.disabled = true;

                navigator.geolocation.getCurrentPosition(handleLocationSuccess, handleLocationError, {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                });
            });

            window.addEventListener('load', () => {
                captureBtn.click();
            });
        })();
    </script>
</body>
</html>
