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

    // Normalizar fecha/hora enviada desde el cliente.
    // Si viene en formato 'YYYY-MM-DD HH:MM:SS' se asume hora local del servidor.
    $datetime = null;
    if ($recordedAtRaw) {
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $recordedAtRaw)) {
            $dt = DateTime::createFromFormat('Y-m-d H:i:s', $recordedAtRaw, new DateTimeZone(date_default_timezone_get()));
            if ($dt !== false) {
                $datetime = $dt;
            }
        } else {
            // Intentar parsear cualquier otro formato (ISO UTC, etc.)
            $datetime = date_create($recordedAtRaw);
        }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Panel del Trabajador - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Estilos específicos del worker dashboard */
        .worker-container {
            max-width: 100%;
            width: 100%;
            padding: 0;
        }

        .worker-dashboard {
            max-width: 680px;
            margin: 0 auto;
            padding: clamp(16px, 4vw, 24px);
        }

        .hero-section {
            text-align: center;
            margin-bottom: clamp(24px, 5vw, 32px);
        }

        .hero-section h1 {
            font-size: clamp(24px, 5vw, 32px);
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: clamp(14px, 3vw, 16px);
            color: var(--gray);
            margin-bottom: 20px;
        }

        .stats-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 24px;
        }

        .stat-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .stat-chip.success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border: 1.5px solid rgba(16,185,129,0.3);
        }

        .stat-chip.info {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
            border: 1.5px solid rgba(59,130,246,0.3);
        }

        .stat-chip.muted {
            background: #f3f4f6;
            color: #6b7280;
            border: 1.5px solid #e5e7eb;
        }

        .card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: var(--radius);
            padding: clamp(18px, 4vw, 24px);
            margin-bottom: clamp(16px, 4vw, 20px);
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.08);
            border: 1px solid rgba(99, 102, 241, 0.1);
        }

        .card-header {
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(99, 102, 241, 0.1);
        }

        .card-title {
            font-size: clamp(16px, 4vw, 18px);
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-subtitle {
            font-size: 13px;
            color: var(--gray);
        }

        .location-section {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .location-display {
            padding: 16px;
            border-radius: var(--radius-sm);
            border: 2px dashed rgba(99, 102, 241, 0.3);
            background: rgba(249, 250, 251, 0.5);
            transition: var(--transition);
        }

        .location-display.active {
            border-color: rgba(16, 185, 129, 0.5);
            background: rgba(209, 250, 229, 0.3);
        }

        .location-display.loading {
            border-color: rgba(59, 130, 246, 0.5);
            background: rgba(219, 234, 254, 0.3);
            animation: pulse 2s ease-in-out infinite;
        }

        .location-display.error {
            border-color: rgba(239, 68, 68, 0.5);
            background: rgba(254, 226, 226, 0.3);
        }

        .location-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--primary-color);
            margin-bottom: 6px;
        }

        .location-text {
            font-size: 14px;
            color: var(--dark);
            line-height: 1.5;
        }

        .time-display {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
            border-radius: var(--radius-sm);
            border: 1px solid rgba(99, 102, 241, 0.15);
        }

        .time-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .time-value {
            font-size: clamp(16px, 4vw, 20px);
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 0.5px;
            line-height: 1.4;
        }

        .time-note {
            font-size: 12px;
            color: var(--gray);
            margin-top: 6px;
        }

        .file-upload-area {
            border: 2px dashed rgba(99, 102, 241, 0.3);
            border-radius: var(--radius-sm);
            padding: 16px;
            text-align: center;
            background: rgba(249, 250, 251, 0.5);
            cursor: pointer;
            transition: var(--transition);
        }

        .file-upload-area:hover {
            border-color: rgba(99, 102, 241, 0.6);
            background: white;
        }

        .file-upload-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .file-upload-hint {
            font-size: 11px;
            color: var(--gray);
        }

        .file-input {
            display: none;
        }

        .btn-large {
            padding: 16px 32px;
            font-size: 16px;
            font-weight: 700;
            border-radius: var(--radius-sm);
            width: 100%;
            margin-top: 12px;
        }

        .history-card {
            background: white;
            border-radius: var(--radius-sm);
            padding: 16px;
            border-left: 4px solid var(--primary-color);
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: var(--transition);
        }

        .history-card:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.15);
        }

        .history-date {
            font-size: 15px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .history-coords {
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 10px;
        }

        .history-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
            flex: 1;
            min-width: 100px;
        }

        @media (max-width: 640px) {
            .worker-dashboard {
                padding: 12px;
            }

            .stat-chip {
                font-size: 12px;
                padding: 6px 12px;
            }

            .card {
                padding: 16px;
            }

            .btn-sm {
                width: 100%;
                flex: none;
            }

            .history-buttons {
                flex-direction: column;
            }
        }
    </style>
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

    <div class="container worker-container">
        <?php 
        // Limpiar la sesión de éxito después de mostrar
        $showSuccessModal = !empty($_SESSION['attendance_success']);
        if ($showSuccessModal) {
            unset($_SESSION['attendance_success']);
        }
        ?>

        <!-- Modal de Bootstrap para confirmación de asistencia -->
        <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true"<?php echo $showSuccessModal ? ' data-show="true"' : ''; ?>>
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center px-4 pb-4">
                        <div class="mb-3">
                            <div style="width: 80px; height: 80px; margin: 0 auto; background: linear-gradient(135deg, #d1fae5, #a7f3d0); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 0 10px rgba(16, 185, 129, 0.1);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="#059669" viewBox="0 0 16 16">
                                    <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="mb-2" id="attendanceModalLabel" style="color: #1f2937; font-weight: 700;">¡Asistencia registrada!</h3>
                        <p class="text-muted mb-4">Tu asistencia fue registrada correctamente. ¡Buen trabajo!</p>
                        <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" style="padding: 12px; font-weight: 600;">Entendido</button>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($attendanceErrors): ?>
            <div class="alert alert-error" style="max-width: 680px; margin: 20px auto;">
                <?php foreach ($attendanceErrors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="worker-dashboard">
            <!-- Hero Section -->
            <div class="hero-section">
                <h1>👋 Hola, <?php echo htmlspecialchars($worker['first_name']); ?></h1>
                <p class="hero-subtitle">Registra tu asistencia de forma rápida y segura</p>
                
                <div class="stats-row">
                    <?php if ($lastRecord): ?>
                        <span class="stat-chip success">
                            ✓ Último: <?php echo date('d/m/Y H:i', strtotime($lastRecord['recorded_at'])); ?>
                        </span>
                        <span class="stat-chip info">
                            📍 Lat <?php echo number_format((float)$lastRecord['latitude'], 4); ?>, Lng <?php echo number_format((float)$lastRecord['longitude'], 4); ?>
                        </span>
                    <?php else: ?>
                        <span class="stat-chip muted">📋 Sin registros previos</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ubicación asignada (si existe) -->
            <?php if ($mapLink || $address): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📍 Ubicación asignada</h3>
                    </div>
                    <?php if ($address): ?>
                        <p style="color: var(--gray); margin-bottom: 12px; font-size: 14px;"><?php echo htmlspecialchars($address); ?></p>
                    <?php endif; ?>
                    <?php if ($mapLink): ?>
                        <a href="<?php echo htmlspecialchars($mapLink); ?>" target="_blank" rel="noopener" class="btn btn-outline btn-small">
                            Ver en Google Maps →
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Formulario de registro -->
            <form class="card" method="POST" enctype="multipart/form-data" autocomplete="off" novalidate>
                <div class="card-header">
                    <h3 class="card-title">📝 Registrar asistencia</h3>
                    <p class="card-subtitle">Captura tu ubicación y confirma el registro</p>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                <input type="hidden" name="latitude" id="latitudeInput">
                <input type="hidden" name="longitude" id="longitudeInput">
                <input type="hidden" name="recorded_at" id="recordedAtInput">

                <div class="location-section">
                    <div class="location-display" id="locationDisplay">
                        <div class="location-label">📍 UBICACIÓN GPS</div>
                        <div class="location-text" id="locationText">Presiona el botón para capturar tu ubicación</div>
                    </div>
                    <button type="button" class="btn btn-outline" id="captureLocationBtn">
                        Obtener ubicación
                    </button>
                </div>

                <div class="time-display">
                    <div class="time-label">🕐 FECHA Y HORA</div>
                    <div class="time-value" id="clockDisplay">--:--:--</div>
                    <div class="time-note">Se guarda automáticamente al registrar</div>
                </div>

                <div class="file-upload-area" onclick="document.getElementById('attachment').click()">
                    <div class="file-upload-label">📎 Adjuntar archivo (opcional)</div>
                    <div class="file-upload-hint">JPG, PNG, WEBP, GIF, PDF · Máx 8 MB</div>
                    <input type="file" name="attachment" id="attachment" accept="image/*,application/pdf" class="file-input">
                </div>

                <button type="submit" class="btn btn-primary btn-large" id="submitAttendance" disabled>
                    Registrar asistencia
                </button>
            </form>

            <!-- Historial de registros -->
            <?php if ($latestRecords): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📜 Últimos registros</h3>
                    </div>
                    
                    <?php foreach ($latestRecords as $record): ?>
                        <div class="history-card">
                            <div class="history-date">
                                📅 <?php echo date('d/m/Y H:i', strtotime($record['recorded_at'])); ?>
                            </div>
                            <div class="history-coords">
                                Lat: <?php echo number_format((float)$record['latitude'], 5); ?> · Lng: <?php echo number_format((float)$record['longitude'], 5); ?>
                            </div>
                            <div class="history-buttons">
                                <a href="https://www.google.com/maps?q=<?php echo rawurlencode($record['latitude'] . ',' . $record['longitude']); ?>" 
                                   target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                                    🗺️ Ver mapa
                                </a>
                                <?php if (!empty($record['attachment_path'])): ?>
                                    <a href="../<?php echo htmlspecialchars($record['attachment_path']); ?>" 
                                       target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                                        📄 Ver archivo
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    </div>

    <script>
        (function() {
            const captureBtn = document.getElementById('captureLocationBtn');
            const submitBtn = document.getElementById('submitAttendance');
            const locationDisplay = document.getElementById('locationDisplay');
            const locationText = document.getElementById('locationText');
            const latitudeInput = document.getElementById('latitudeInput');
            const longitudeInput = document.getElementById('longitudeInput');
            const recordedAtInput = document.getElementById('recordedAtInput');
            const clockDisplay = document.getElementById('clockDisplay');
            const modal = document.getElementById('attendanceModal');

            // Manejador del modal Bootstrap
            if (modal && modal.hasAttribute('data-show')) {
                const bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
            }

            let locationCaptured = false;

            // Helper: obtener timestamp local en formato SQL (YYYY-MM-DD HH:MM:SS)
            const getLocalSqlTimestamp = (d = new Date()) => {
                const pad = n => String(n).padStart(2, '0');
                return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
            };

            // Actualizar reloj con fecha y hora
            const updateClock = () => {
                const now = new Date();
                
                // Formatear fecha
                const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                
                const dayName = days[now.getDay()];
                const day = now.getDate();
                const month = months[now.getMonth()];
                const year = now.getFullYear();
                
                // Formatear hora
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                
                // Mostrar: "Lunes, 13 Nov 2025 - 14:30:45"
                clockDisplay.textContent = `${dayName}, ${day} ${month} ${year} - ${hours}:${minutes}:${seconds}`;
                
                if (locationCaptured) {
                    // Guardar siempre hora local (evita desfases UTC)
                    recordedAtInput.value = getLocalSqlTimestamp(now);
                }
            };

            setInterval(updateClock, 1000);
            updateClock();

            // Manejo de ubicación
            function handleLocationSuccess(position) {
                const { latitude, longitude } = position.coords;
                latitudeInput.value = latitude.toFixed(7);
                longitudeInput.value = longitude.toFixed(7);
                locationCaptured = true;
                recordedAtInput.value = getLocalSqlTimestamp();
                submitBtn.disabled = false;
                
                locationDisplay.className = 'location-display active';
                locationText.textContent = `✓ Ubicación capturada: Lat ${latitude.toFixed(5)}, Lng ${longitude.toFixed(5)}`;
                captureBtn.textContent = 'Actualizar ubicación';
            }

            function handleLocationError(error) {
                submitBtn.disabled = true;
                locationCaptured = false;
                locationDisplay.className = 'location-display error';
                
                const messages = {
                    1: '✕ Debes activar los permisos de ubicación en tu dispositivo',
                    2: '✕ No se pudo obtener la ubicación. Verifica tu señal GPS',
                    3: '✕ La solicitud de ubicación expiró. Intenta nuevamente'
                };
                locationText.textContent = messages[error.code] || '✕ Error al obtener la ubicación';
            }

            captureBtn.addEventListener('click', function() {
                if (!navigator.geolocation) {
                    locationDisplay.className = 'location-display error';
                    locationText.textContent = '✕ Tu dispositivo no soporta geolocalización';
                    return;
                }

                locationDisplay.className = 'location-display loading';
                locationText.textContent = '⏳ Obteniendo ubicación, espera unos segundos...';
                submitBtn.disabled = true;

                navigator.geolocation.getCurrentPosition(handleLocationSuccess, handleLocationError, {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                });
            });

            // Antes de enviar, fijar timestamp definitivo (por si pasó 1 segundo entre último tick y submit)
            const form = document.querySelector('form.card');
            if (form) {
                form.addEventListener('submit', () => {
                    recordedAtInput.value = getLocalSqlTimestamp();
                });
            }

            // Auto-capturar ubicación al cargar
            window.addEventListener('load', () => {
                setTimeout(() => captureBtn.click(), 500);
            });
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
