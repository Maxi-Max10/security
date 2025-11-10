<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Solo admins
require_login();
require_admin();

$conn = getDBConnection();
$csrf_token = generate_csrf_token();
$current_user = get_user_data();

// Helpers de validación
function validate_worker_input($data, $is_update = false, $existing_id = null, $conn = null) {
    $errors = [];

    $first_name = trim($data['first_name'] ?? '');
    $last_name = trim($data['last_name'] ?? '');
    $dni = preg_replace('/\D+/', '', $data['dni'] ?? '');
    $email = trim($data['email'] ?? '');
    $cvu_alias = trim($data['cvu_alias'] ?? '');
    $age = trim($data['age'] ?? '');
    $work_place = trim($data['work_place'] ?? '');
    $address_input = trim($data['address'] ?? '');

    if ($first_name === '') { $errors['first_name'] = 'Nombre es obligatorio.'; }
    if ($last_name === '') { $errors['last_name'] = 'Apellido es obligatorio.'; }

    if ($dni === '' || !preg_match('/^\d{7,10}$/', $dni)) {
        $errors['dni'] = 'DNI inválido (solo números, 7 a 10 dígitos).';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email inválido.';
    }

    if ($age !== '') {
        if (!ctype_digit($age)) { $errors['age'] = 'Edad debe ser un número.'; }
        else {
            $ageNum = intval($age);
            if ($ageNum < 16 || $ageNum > 100) { $errors['age'] = 'Edad fuera de rango (16-100).'; }
        }
    }

    if ($work_place === '') { $errors['work_place'] = 'Lugar de trabajo es obligatorio.'; }

    if ($cvu_alias !== '' && !preg_match('/^[A-Za-z0-9._-]{3,}$/', $cvu_alias)) {
        $errors['cvu_alias'] = 'CVU/Alias inválido.';
    }

    // Unicidad DNI y Email
    if ($conn) {
        // DNI
        if ($stmt = $conn->prepare($existing_id ? "SELECT id FROM workers WHERE dni = ? AND id <> ? LIMIT 1" : "SELECT id FROM workers WHERE dni = ? LIMIT 1")) {
            if ($existing_id) { $stmt->bind_param('si', $dni, $existing_id); } else { $stmt->bind_param('s', $dni); }
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) { $errors['dni'] = 'DNI ya existe.'; }
            $stmt->close();
        }
        // Email
        if ($stmt = $conn->prepare($existing_id ? "SELECT id FROM workers WHERE email = ? AND id <> ? LIMIT 1" : "SELECT id FROM workers WHERE email = ? LIMIT 1")) {
            if ($existing_id) { $stmt->bind_param('si', $email, $existing_id); } else { $stmt->bind_param('s', $email); }
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) { $errors['email'] = 'Email ya existe.'; }
            $stmt->close();
        }
    }

    // Dirección y/o URL de Google Maps
    $address_text = null; $address_url = null; $lat = null; $lng = null;
    if ($address_input !== '') {
        // Es URL de Google Maps?
        $is_gmaps = preg_match('#^https?://(www\.)?(google\.com/maps|maps\.google\.com)/#i', $address_input);
        if ($is_gmaps) {
            $address_url = $address_input;
            // Extraer @lat,lng o api=1 query
            if (preg_match('#@(-?\d+\.\d+),(-?\d+\.\d+)#', $address_input, $m)) {
                $lat = $m[1];
                $lng = $m[2];
            } elseif (preg_match('#[?&]query=(-?\d+\.\d+),(-?\d+\.\d+)#', $address_input, $m2)) {
                $lat = $m2[1];
                $lng = $m2[2];
            }
        } else {
            $address_text = $address_input;
        }
    }

    return [
        'errors' => $errors,
        'clean' => compact('first_name','last_name','dni','email','cvu_alias','age','work_place','address_text','address_url','lat','lng')
    ];
}

$message = '';
$message_type = '';

// Acciones POST: create, update, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Token de seguridad inválido.';
        $message_type = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM workers WHERE id = ?");
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $message = 'Trabajador eliminado correctamente.';
                    $message_type = 'success';
                } else {
                    $message = 'No se pudo eliminar el trabajador.';
                    $message_type = 'error';
                }
                $stmt->close();
            }
        } elseif (in_array($action, ['create','update'])) {
            $existing_id = $action === 'update' ? intval($_POST['id'] ?? 0) : null;
            [$valid, $clean] = (function() use ($conn, $action, $existing_id){
                $res = validate_worker_input($_POST, $action==='update', $existing_id, $conn);
                return [$res['errors'], $res['clean']];
            })();
            $errors = $valid;

            if (empty($errors)) {
                $first_name = $clean['first_name'];
                $last_name = $clean['last_name'];
                $dni = $clean['dni'];
                $email = $clean['email'];
                $cvu_alias = $clean['cvu_alias'] !== '' ? $clean['cvu_alias'] : null;
                $age = ($clean['age'] !== '') ? intval($clean['age']) : null;
                $work_place = $clean['work_place'];
                $address_text = $clean['address_text'];
                $address_url = $clean['address_url'];
                $lat = $clean['lat'];
                $lng = $clean['lng'];
                $uid = intval($_SESSION['user_id']);

                if ($action === 'create') {
                    $sql = "INSERT INTO workers (first_name,last_name,dni,email,cvu_alias,age,work_place,address_text,address_url,latitude,longitude,created_by,updated_by) VALUES (?,?,?,?,?,?,?,?,?,?,?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param(
                        'sssssisssddii',
                        $first_name,
                        $last_name,
                        $dni,
                        $email,
                        $cvu_alias,
                        $age,
                        $work_place,
                        $address_text,
                        $address_url,
                        $lat,
                        $lng,
                        $uid,
                        $uid
                    );
                    $ok = $stmt->execute();
                    $stmt->close();
                    if ($ok) {
                        $message = 'Trabajador creado correctamente.';
                        $message_type = 'success';
                    } else {
                        $message = 'No se pudo crear el trabajador.';
                        $message_type = 'error';
                    }
                } else { // update
                    $id = $existing_id;
                    $sql = "UPDATE workers SET first_name=?, last_name=?, dni=?, email=?, cvu_alias=?, age=?, work_place=?, address_text=?, address_url=?, latitude=?, longitude=?, updated_by=? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param(
                        'sssssisssddii',
                        $first_name,
                        $last_name,
                        $dni,
                        $email,
                        $cvu_alias,
                        $age,
                        $work_place,
                        $address_text,
                        $address_url,
                        $lat,
                        $lng,
                        $uid,
                        $id
                    );
                    $ok = $stmt->execute();
                    $stmt->close();
                    if ($ok) {
                        $message = 'Trabajador actualizado correctamente.';
                        $message_type = 'success';
                    } else {
                        $message = 'No se pudo actualizar el trabajador.';
                        $message_type = 'error';
                    }
                }
            } else {
                // Guardar errores en sesión para mostrarlos
                $_SESSION['form_errors'] = $errors;
                $_SESSION['form_old'] = $_POST;
            }
        }
    }
}

// Parámetros de lista: paginación, ordenamiento y búsqueda
$per_page = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$allowed_sort = ['first_name','last_name','dni','email','cvu_alias','age','work_place'];
$sort_by = $_GET['sort'] ?? 'last_name';
if (!in_array($sort_by, $allowed_sort)) { $sort_by = 'last_name'; }
$sort_dir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$q = trim($_GET['q'] ?? '');
$where = '';
$params = [];
$types = '';
if ($q !== '') {
    $where = "WHERE (first_name LIKE ? OR last_name LIKE ? OR dni LIKE ?)";
    $like = "%$q%";
    $params = [$like, $like, $like];
    $types = 'sss';
}

// Total
if ($where) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM workers $where");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
} else {
    $total = $conn->query("SELECT COUNT(*) as total FROM workers")->fetch_assoc()['total'];
}

$total_pages = max(1, ceil($total / $per_page));

// Datos página
if ($where) {
    $sql = "SELECT * FROM workers $where ORDER BY $sort_by $sort_dir LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    // agregar tipos para LIMIT/OFFSET
    $bindTypes = $types . 'ii';
    $params2 = array_merge($params, [$per_page, $offset]);
    $stmt->bind_param($bindTypes, ...$params2);
    $stmt->execute();
    $workers = $stmt->get_result();
} else {
    $sql = "SELECT * FROM workers ORDER BY $sort_by $sort_dir LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $per_page, $offset);
    $stmt->execute();
    $workers = $stmt->get_result();
}

// Errores anteriores del form
$form_errors = $_SESSION['form_errors'] ?? [];
$form_old = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_old']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trabajadores - Panel Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .toolbar { display:flex; gap:12px; justify-content: space-between; align-items:center; }
        .search-box { display:flex; gap:8px; }
        .grid-actions { display:flex; gap:8px; flex-wrap: wrap; }
        .table-responsive { overflow-x:auto; }
        .error-text { color: #dc2626; font-size: 12px; margin-top: 4px; }
        .modal { display:none; position: fixed; inset:0; background: rgba(0,0,0,.5); align-items:center; justify-content:center; }
        .modal.active { display:flex; }
        .modal-content { background:#fff; padding:24px; border-radius:10px; width:100%; max-width:700px; }
        .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; }
        .close { cursor:pointer; font-size:22px; }
        .sortable a { color:#fff; text-decoration:none; }
    </style>
    <script>
        function openModal(id){ document.getElementById(id).classList.add('active'); }
        function closeModal(id){ document.getElementById(id).classList.remove('active'); }
        function editWorker(worker){
            // Rellenar formulario de edición
            for (const [k,v] of Object.entries(worker)){
                const el = document.querySelector('#editForm [name="'+k+'"]');
                if (el) el.value = v ?? '';
            }
            openModal('editModal');
        }
        function parseAddressOnBlur(inputId, hintId){
            const val = document.getElementById(inputId).value.trim();
            const hint = document.getElementById(hintId);
            if (val.startsWith('https://www.google.com/maps') || val.startsWith('https://maps.google.com')){
                const m = val.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
                if (m){ hint.textContent = 'Detectado Google Maps. Lat: '+m[1]+' Lng: '+m[2]; }
                else { hint.textContent = 'URL de Google Maps detectada.'; }
            } else if (val.length){ hint.textContent = 'Dirección en texto'; }
            else { hint.textContent = ''; }
        }
    </script>
</head>
<body>
    <div class="navbar">
        <div class="container">
            <div class="nav-content">
                <h2>Trabajadores</h2>
                <div class="nav-right">
                    <span class="user-info"><?php echo htmlspecialchars($current_user['username']); ?></span>
                    <a href="dashboard.php" class="btn btn-small">Dashboard</a>
                    <a href="users.php" class="btn btn-small btn-outline">Usuarios</a>
                    <a href="../logout.php" class="btn btn-small btn-secondary">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="dashboard-box" style="max-width:1200px;">
            <h1>👷 Gestión de Trabajadores</h1>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="toolbar">
                <form class="search-box" method="GET" action="workers.php">
                    <input type="text" name="q" placeholder="Buscar por nombre, apellido o DNI" value="<?php echo htmlspecialchars($q); ?>">
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                    <input type="hidden" name="dir" value="<?php echo strtolower($sort_dir); ?>">
                    <button class="btn btn-primary btn-small" type="submit">Buscar</button>
                </form>
                <button class="btn btn-success btn-small" onclick="openModal('createModal')">+ Nuevo trabajador</button>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th class="sortable"><a href="?<?php echo http_build_query(['q'=>$q,'sort'=>'first_name','dir'=>$sort_by==='first_name' && $sort_dir==='ASC'?'desc':'asc']); ?>">Nombre</a></th>
                            <th class="sortable"><a href="?<?php echo http_build_query(['q'=>$q,'sort'=>'last_name','dir'=>$sort_by==='last_name' && $sort_dir==='ASC'?'desc':'asc']); ?>">Apellido</a></th>
                            <th class="sortable"><a href="?<?php echo http_build_query(['q'=>$q,'sort'=>'dni','dir'=>$sort_by==='dni' && $sort_dir==='ASC'?'desc':'asc']); ?>">DNI</a></th>
                            <th class="sortable"><a href="?<?php echo http_build_query(['q'=>$q,'sort'=>'email','dir'=>$sort_by==='email' && $sort_dir==='ASC'?'desc':'asc']); ?>">Email</a></th>
                            <th>CVU/Alias</th>
                            <th class="sortable"><a href="?<?php echo http_build_query(['q'=>$q,'sort'=>'age','dir'=>$sort_by==='age' && $sort_dir==='ASC'?'desc':'asc']); ?>">Edad</a></th>
                            <th class="sortable"><a href="?<?php echo http_build_query(['q'=>$q,'sort'=>'work_place','dir'=>$sort_by==='work_place' && $sort_dir==='ASC'?'desc':'asc']); ?>">Lugar de trabajo</a></th>
                            <th>Dirección</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($w = $workers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($w['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($w['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($w['dni']); ?></td>
                            <td><?php echo htmlspecialchars($w['email']); ?></td>
                            <td><?php echo htmlspecialchars($w['cvu_alias'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($w['age'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($w['work_place']); ?></td>
                            <td>
                                <?php if ($w['address_url']): ?>
                                    <a href="<?php echo htmlspecialchars($w['address_url']); ?>" target="_blank">Mapa</a>
                                    <?php if ($w['latitude'] && $w['longitude']): ?>
                                        <small>(<?php echo $w['latitude'] . ', ' . $w['longitude']; ?>)</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($w['address_text'] ?? ''); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="grid-actions">
                                    <button class="btn btn-small btn-outline" onclick='editWorker(<?php echo json_encode([
                                        'id' => $w['id'],
                                        'first_name' => $w['first_name'],
                                        'last_name' => $w['last_name'],
                                        'dni' => $w['dni'],
                                        'email' => $w['email'],
                                        'cvu_alias' => $w['cvu_alias'],
                                        'age' => $w['age'],
                                        'work_place' => $w['work_place'],
                                        'address' => $w['address_url'] ? $w['address_url'] : ($w['address_text'] ?? ''),
                                    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>✏️ Editar</button>
                                    <form method="POST" onsubmit="return confirm('¿Eliminar este trabajador?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $w['id']; ?>">
                                        <button class="btn btn-small btn-danger" type="submit">🗑️ Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <strong>Total:</strong> <?php echo $total; ?> | Página <?php echo $page; ?> de <?php echo $total_pages; ?>
                </div>
                <div style="display:flex; gap:8px;">
                    <?php if ($page > 1): ?>
                        <a class="btn btn-small btn-outline" href="?<?php echo http_build_query(['q'=>$q,'sort'=>$sort_by,'dir'=>strtolower($sort_dir),'page'=>$page-1]); ?>">← Anterior</a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a class="btn btn-small btn-outline" href="?<?php echo http_build_query(['q'=>$q,'sort'=>$sort_by,'dir'=>strtolower($sort_dir),'page'=>$page+1]); ?>">Siguiente →</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear -->
    <div class="modal" id="createModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nuevo Trabajador</h3>
                <span class="close" onclick="closeModal('createModal')">✕</span>
            </div>
            <form method="POST" id="createForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="first_name" required value="<?php echo htmlspecialchars($form_old['first_name'] ?? ''); ?>">
                    <?php if (isset($form_errors['first_name'])) echo '<div class="error-text">'.$form_errors['first_name'].'</div>'; ?>
                </div>
                <div class="form-group">
                    <label>Apellido *</label>
                    <input type="text" name="last_name" required value="<?php echo htmlspecialchars($form_old['last_name'] ?? ''); ?>">
                    <?php if (isset($form_errors['last_name'])) echo '<div class="error-text">'.$form_errors['last_name'].'</div>'; ?>
                </div>
                <div class="form-group">
                    <label>DNI *</label>
                    <input type="text" name="dni" required pattern="\d{7,10}" value="<?php echo htmlspecialchars($form_old['dni'] ?? ''); ?>">
                    <small>Solo números, 7 a 10 dígitos</small>
                    <?php if (isset($form_errors['dni'])) echo '<div class="error-text">'.$form_errors['dni'].'</div>'; ?>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($form_old['email'] ?? ''); ?>">
                    <?php if (isset($form_errors['email'])) echo '<div class="error-text">'.$form_errors['email'].'</div>'; ?>
                </div>
                <div class="form-group">
                    <label>CVU / Alias</label>
                    <input type="text" name="cvu_alias" value="<?php echo htmlspecialchars($form_old['cvu_alias'] ?? ''); ?>">
                    <?php if (isset($form_errors['cvu_alias'])) echo '<div class="error-text">'.$form_errors['cvu_alias'].'</div>'; ?>
                </div>
                <div class="form-group">
                    <label>Edad</label>
                    <input type="number" name="age" min="16" max="100" value="<?php echo htmlspecialchars($form_old['age'] ?? ''); ?>">
                    <?php if (isset($form_errors['age'])) echo '<div class="error-text">'.$form_errors['age'].'</div>'; ?>
                </div>
                <div class="form-group">
                    <label>Lugar de trabajo *</label>
                    <input type="text" name="work_place" required value="<?php echo htmlspecialchars($form_old['work_place'] ?? ''); ?>">
                    <?php if (isset($form_errors['work_place'])) echo '<div class="error-text">'.$form_errors['work_place'].'</div>'; ?>
                </div>
                <div class="form-group">
                    <label>Dirección o URL de Google Maps</label>
                    <input type="text" id="createAddress" name="address" onblur="parseAddressOnBlur('createAddress','createHint')" value="<?php echo htmlspecialchars($form_old['address'] ?? ''); ?>">
                    <small id="createHint"></small>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Trabajador</h3>
                <span class="close" onclick="closeModal('editModal')">✕</span>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Apellido *</label>
                    <input type="text" name="last_name" required>
                </div>
                <div class="form-group">
                    <label>DNI *</label>
                    <input type="text" name="dni" required pattern="\d{7,10}">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>CVU / Alias</label>
                    <input type="text" name="cvu_alias">
                </div>
                <div class="form-group">
                    <label>Edad</label>
                    <input type="number" name="age" min="16" max="100">
                </div>
                <div class="form-group">
                    <label>Lugar de trabajo *</label>
                    <input type="text" name="work_place" required>
                </div>
                <div class="form-group">
                    <label>Dirección o URL de Google Maps</label>
                    <input type="text" id="editAddress" name="address" onblur="parseAddressOnBlur('editAddress','editHint')">
                    <small id="editHint"></small>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </div>

    <?php $conn->close(); ?>
</body>
</html>
