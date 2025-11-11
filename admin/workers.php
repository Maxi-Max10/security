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

// Con AJAX implementado, toda la mutación y listado se hace vía /admin/api/workers.php

$form_errors = [];
$form_old = [];
?>
<?php $page_title = 'Trabajadores'; include __DIR__.'/partials/head.php'; ?>
    <div class="admin-layout">
        <?php include __DIR__.'/partials/sidebar.php'; ?>
        <?php include __DIR__.'/partials/header.php'; ?>
        <main class="content container-fluid py-4">
            <?php include __DIR__.'/partials/breadcrumb.php'; ?>
            <h2 class="mt-0">👷 Gestión de Trabajadores</h2>

            <div id="flash" class="alert d-none" role="alert"></div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <form class="d-flex gap-2" id="searchForm">
                            <input type="text" class="form-control form-control-sm" id="searchQ" placeholder="Buscar por nombre, apellido o DNI">
                            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search"></i> Buscar</button>
                        </form>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filtersCollapse" aria-expanded="false" aria-controls="filtersCollapse"><i class="bi bi-funnel"></i> Filtros</button>
                            <button class="btn btn-success btn-sm" onclick="openModal('createModal')"><i class="bi bi-plus-lg"></i> Nuevo</button>
                        </div>
                    </div>
                    <div class="collapse" id="filtersCollapse">
                        <form id="filtersForm" class="border rounded p-3 bg-body-tertiary small">
                            <div class="row g-3">
                                <div class="col-6 col-md-3">
                                    <label class="form-label mb-1">Edad mín.</label>
                                    <input type="number" min="16" max="100" class="form-control form-control-sm" id="age_min" placeholder="16">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label mb-1">Edad máx.</label>
                                    <input type="number" min="16" max="100" class="form-control form-control-sm" id="age_max" placeholder="60">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label mb-1">Lugar trabajo</label>
                                    <input type="text" class="form-control form-control-sm" id="work_place_filter" placeholder="Ej: Planta">
                                </div>
                                <div class="col-12 col-md-2 d-flex align-items-end">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="has_geo">
                                        <label class="form-check-label" for="has_geo">Con mapa</label>
                                    </div>
                                </div>
                                <div class="col-12 d-flex gap-2 justify-content-end">
                                    <button type="button" id="btnClearFilters" class="btn btn-outline-secondary btn-sm">Limpiar</button>
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-filter"></i> Aplicar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Listado</strong>
                    <div class="small text-muted">Ordenar: clic en encabezados</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-clean align-middle mb-0" id="workersTable">
                    <thead>
                        <tr>
                            <th class="sortable"><a href="#" class="th-sort" data-sort="first_name">Nombre <span class="sort-icon bi"></span></a></th>
                            <th class="sortable"><a href="#" class="th-sort" data-sort="last_name">Apellido <span class="sort-icon bi"></span></a></th>
                            <th class="sortable"><a href="#" class="th-sort" data-sort="dni">DNI <span class="sort-icon bi"></span></a></th>
                            <th class="sortable"><a href="#" class="th-sort" data-sort="email">Email <span class="sort-icon bi"></span></a></th>
                            <th>CVU/Alias</th>
                            <th class="sortable"><a href="#" class="th-sort" data-sort="age">Edad <span class="sort-icon bi"></span></a></th>
                            <th class="sortable"><a href="#" class="th-sort" data-sort="work_place">Lugar de trabajo <span class="sort-icon bi"></span></a></th>
                            <th>Dirección</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                                        <tbody id="workersBody"></tbody>
                    </table>
                </div>
            </div>

            <nav class="mt-3"><ul class="pagination pagination-sm" id="pagination"></ul></nav>
        </main>
    </div>

        <!-- Modal Crear (Bootstrap) -->
        <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nuevo Trabajador</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <form id="createForm">
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="action" value="create">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" name="first_name" required value="<?php echo htmlspecialchars($form_old['first_name'] ?? ''); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Apellido *</label>
                                    <input type="text" class="form-control" name="last_name" required value="<?php echo htmlspecialchars($form_old['last_name'] ?? ''); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">DNI *</label>
                                    <input type="text" class="form-control" name="dni" required pattern="\d{7,10}" value="<?php echo htmlspecialchars($form_old['dni'] ?? ''); ?>">
                                    <div class="form-text">Solo números, 7 a 10 dígitos</div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($form_old['email'] ?? ''); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Contraseña *</label>
                                    <input type="password" class="form-control" name="password" required minlength="8" autocomplete="new-password">
                                    <div class="form-text">Mínimo 8 caracteres</div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">CVU / Alias</label>
                                    <input type="text" class="form-control" name="cvu_alias" value="<?php echo htmlspecialchars($form_old['cvu_alias'] ?? ''); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Edad</label>
                                    <input type="number" class="form-control" name="age" min="16" max="100" value="<?php echo htmlspecialchars($form_old['age'] ?? ''); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label">Lugar de trabajo *</label>
                                    <input type="text" class="form-control" name="work_place" required value="<?php echo htmlspecialchars($form_old['work_place'] ?? ''); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Dirección o URL de Google Maps</label>
                                    <input type="text" class="form-control" id="createAddress" name="address" onblur="parseAddressOnBlur('createAddress','createHint')" value="<?php echo htmlspecialchars($form_old['address'] ?? ''); ?>">
                                    <div class="form-text" id="createHint"></div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Editar (Bootstrap) -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Trabajador</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <form id="editForm">
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" name="first_name" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Apellido *</label>
                                    <input type="text" class="form-control" name="last_name" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">DNI *</label>
                                    <input type="text" class="form-control" name="dni" required pattern="\d{7,10}">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" name="password" minlength="8" autocomplete="new-password" placeholder="Dejar vacío para no cambiar">
                                    <div class="form-text">Deja en blanco para mantener la actual</div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">CVU / Alias</label>
                                    <input type="text" class="form-control" name="cvu_alias">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Edad</label>
                                    <input type="number" class="form-control" name="age" min="16" max="100">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label">Lugar de trabajo *</label>
                                    <input type="text" class="form-control" name="work_place" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Dirección o URL de Google Maps</label>
                                    <input type="text" class="form-control" id="editAddress" name="address" onblur="parseAddressOnBlur('editAddress','editHint')">
                                    <div class="form-text" id="editHint"></div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Eliminar (Bootstrap) -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">Eliminar Trabajador</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-1">¿Seguro que deseas eliminar a <strong id="deleteWorkerName">este trabajador</strong>?</p>
                        <p class="mb-0 small text-muted">DNI: <span id="deleteWorkerDni">-</span></p>
                        <div class="alert alert-warning mt-3 mb-0" role="alert">
                            Esta acción no se puede deshacer.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>

    <script>
        let createModal, editModal, deleteModal;
        let deleteTarget = null;
        document.addEventListener('DOMContentLoaded', ()=>{
          if (window.bootstrap){
            const cm = document.getElementById('createModal');
            const em = document.getElementById('editModal');
            const dm = document.getElementById('deleteModal');
                        if (cm) { createModal = new bootstrap.Modal(cm); }
                        if (em) { editModal = new bootstrap.Modal(em); }
                        if (dm) {
                                deleteModal = new bootstrap.Modal(dm);
                                dm.addEventListener('hidden.bs.modal', ()=>{
                                        deleteTarget = null;
                                        const lbl = document.getElementById('deleteWorkerName');
                                        if (lbl) lbl.textContent = 'este trabajador';
                                        const dni = document.getElementById('deleteWorkerDni');
                                        if (dni) dni.textContent = '-';
                                });
                        }
          }
        });
        function openModal(id){
            if (id==='createModal' && createModal) { createModal.show(); return; }
            if (id==='editModal' && editModal) { editModal.show(); return; }
            if (id==='deleteModal' && deleteModal) { deleteModal.show(); }
        }
        function closeModal(id){
            if (id==='createModal' && createModal) { createModal.hide(); return; }
            if (id==='editModal' && editModal) { editModal.hide(); return; }
            if (id==='deleteModal' && deleteModal) { deleteModal.hide(); }
        }
        function editWorker(worker){
            // Rellenar formulario de edición
            for (const [k,v] of Object.entries(worker)){
                const el = document.querySelector('#editForm [name="'+k+'"]');
                if (el) el.value = v ?? '';
            }
            const pw = document.querySelector('#editForm [name="password"]');
            if (pw) pw.value = '';
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
        const csrfToken = <?php echo json_encode($csrf_token); ?>;
    let state = { page: 1, per_page: 10, sort: 'last_name', dir: 'asc', q: '', age_min: '', age_max: '', work_place: '', has_geo: '' };

        function showFlash(text, type='success'){
            const box = document.getElementById('flash');
            box.className = 'alert alert-' + (type==='success'?'success':type);
            box.textContent = text;
            box.classList.remove('d-none');
            setTimeout(()=>{ box.classList.add('d-none'); }, 3000);
        }

        async function fetchList(){
            const base = { action:'list', page: state.page, limit: state.per_page, sort: state.sort, dir: state.dir, q: state.q };
            if (state.age_min) base.age_min = state.age_min;
            if (state.age_max) base.age_max = state.age_max;
            if (state.work_place) base.work_place = state.work_place;
            if (state.has_geo) base.has_geo = state.has_geo;
            const params = new URLSearchParams(base);
            const json = await safeFetchJSON('./api/workers.php?' + params.toString(), { credentials: 'same-origin' });
            if (!json.ok) { showFlash(json.error || 'Error al cargar', 'error'); return; }
            renderTable(json.data);
            renderPagination(json);
        }

        function renderTable(rows){
            const tbody = document.getElementById('workersBody');
            tbody.innerHTML = '';
            if (!rows.length){
                const tr = document.createElement('tr');
                tr.innerHTML = '<td colspan="9" class="text-center text-muted py-4"><em>Sin resultados para los filtros aplicados.</em></td>';
                tbody.appendChild(tr);
                return;
            }
            rows.forEach(w => {
                const tr = document.createElement('tr');
                const addr = w.address_url ? `<a href="${w.address_url}" target="_blank" class="text-decoration-none"><i class=\"bi bi-geo-alt\"></i> Mapa</a>${(w.latitude && w.longitude)?` <small class=\"text-muted\">(${w.latitude}, ${w.longitude})</small>`:''}` : (w.address_text||'');
                const ageBadge = w.age ? `<span class=\"badge bg-secondary-subtle text-secondary\">${w.age}</span>` : '<span class=\"badge bg-light text-muted\">-</span>';
                const workerNameAttr = escapeHtml([w.first_name, w.last_name].filter(Boolean).join(' '));
                const workerDniAttr = escapeHtml(w.dni || '');
                tr.innerHTML = `
                    <td>${escapeHtml(w.first_name||'')}</td>
                    <td>${escapeHtml(w.last_name||'')}</td>
                    <td>${escapeHtml(w.dni||'')}</td>
                    <td>${escapeHtml(w.email||'')}</td>
                    <td>${escapeHtml(w.cvu_alias||'')}</td>
                    <td>${ageBadge}</td>
                    <td>${escapeHtml(w.work_place||'')}</td>
                    <td>${addr}</td>
                    <td>
                        <div class=\"btn-group btn-group-sm\" role=\"group\">
                            <button class=\"btn btn-outline-primary\" data-bs-toggle=\"tooltip\" title=\"Editar\" data-action=\"edit\" data-id=\"${w.id}\"><i class=\"bi bi-pencil\"></i></button>
                            <button class=\"btn btn-outline-danger\" data-bs-toggle=\"tooltip\" title=\"Eliminar\" data-action=\"delete\" data-id=\"${w.id}\" data-worker-name=\"${workerNameAttr}\" data-worker-dni=\"${workerDniAttr}\"><i class=\"bi bi-trash\"></i></button>
                        </div>
                    </td>`;
                tbody.appendChild(tr);
            });
            if (window.bootstrap){
              const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
              tooltipTriggerList.map(el => new bootstrap.Tooltip(el));
            }
        }

        function renderPagination(meta){
            const ul = document.getElementById('pagination');
            ul.innerHTML = '';
            const info = document.createElement('li'); info.className = 'page-item disabled'; info.innerHTML = `<span class="page-link">Total ${meta.total} · Página ${meta.page}/${meta.total_pages}</span>`; ul.appendChild(info);
            if (meta.page > 1){
                const prev = document.createElement('li'); prev.className='page-item'; prev.innerHTML = `<a class=\"page-link\" href=\"#\">&laquo;</a>`; prev.onclick = (e)=>{ e.preventDefault(); state.page -= 1; fetchList(); }; ul.appendChild(prev);
            }
            if (meta.page < meta.total_pages){
                const next = document.createElement('li'); next.className='page-item'; next.innerHTML = `<a class=\"page-link\" href=\"#\">&raquo;</a>`; next.onclick = (e)=>{ e.preventDefault(); state.page += 1; fetchList(); }; ul.appendChild(next);
            }
        }

        function attachEvents(){
            // Sort links
            document.querySelectorAll('th.sortable a').forEach(a => {
                a.addEventListener('click', (e)=>{
                    e.preventDefault();
                    const s = a.getAttribute('data-sort');
                    if (state.sort === s){ state.dir = state.dir === 'asc' ? 'desc' : 'asc'; } else { state.sort = s; state.dir = 'asc'; }
                    state.page = 1; fetchList();
                });
            });
            updateSortIndicators();
            // Search
            document.getElementById('searchForm').addEventListener('submit', (e)=>{
                e.preventDefault();
                state.q = document.getElementById('searchQ').value.trim();
                state.page = 1; fetchList();
            });
                        // Filters
                        document.getElementById('filtersForm').addEventListener('submit', e => {
                            e.preventDefault();
                            state.age_min = document.getElementById('age_min').value.trim();
                            state.age_max = document.getElementById('age_max').value.trim();
                            state.work_place = document.getElementById('work_place_filter').value.trim();
                            state.has_geo = document.getElementById('has_geo').checked ? '1' : '';
                            state.page = 1;
                            fetchList();
                        });
                        document.getElementById('btnClearFilters').addEventListener('click', () => {
                            ['age_min','age_max','work_place_filter'].forEach(id => document.getElementById(id).value='');
                            document.getElementById('has_geo').checked = false;
                            state.age_min = state.age_max = state.work_place = state.has_geo = '';
                            state.page = 1;
                            fetchList();
                        });
                        // Delegated actions (edit/delete)
            document.getElementById('workersBody').addEventListener('click', async (e)=>{
                const btn = e.target.closest('button'); if (!btn) return;
                const act = btn.getAttribute('data-action'); const id = btn.getAttribute('data-id');
                if (act === 'edit') {
                    // load data by id (optional) or reuse row; we'll query API
                    const j = await safeFetchJSON('./api/workers.php?action=get&id=' + id);
                    if (j.ok){ editWorker({ id: j.data.id, first_name: j.data.first_name, last_name: j.data.last_name, dni: j.data.dni, email: j.data.email, cvu_alias: j.data.cvu_alias, age: j.data.age, work_place: j.data.work_place, address: j.data.address_url ? j.data.address_url : (j.data.address_text||'') }); }
                } else if (act === 'delete') {
                    deleteTarget = {
                        id,
                        name: btn.dataset.workerName || '',
                        dni: btn.dataset.workerDni || ''
                    };
                    const nameLabel = document.getElementById('deleteWorkerName');
                    if (nameLabel) nameLabel.textContent = deleteTarget.name || 'este trabajador';
                    const dniLabel = document.getElementById('deleteWorkerDni');
                    if (dniLabel) dniLabel.textContent = deleteTarget.dni || '-';
                    openModal('deleteModal');
                }
            });
            // Create
            document.getElementById('createForm').addEventListener('submit', async (e)=>{
                e.preventDefault();
                clearFormErrors(e.target);
                const fd = new FormData(e.target); fd.append('csrf_token', csrfToken);
                const j = await safeFetchJSON('./api/workers.php', { method:'POST', body: fd, credentials:'same-origin' });
                if (j.ok){
                    console.info('Creado trabajador id=', j.id);
                    closeModal('createModal');
                    showFlash('Trabajador creado');
                    resetSearchAndFilters();
                    fetchList();
                    e.target.reset();
                }
                else {
                    if (j.errors) showFormErrors(e.target, j.errors);
                    showFlash(j.error||'Hay errores en el formulario', 'error');
                    if (j.db_error) console.warn('DB:', j.db_error);
                }
            });
            // Update
            document.getElementById('editForm').addEventListener('submit', async (e)=>{
                e.preventDefault();
                clearFormErrors(e.target);
                const fd = new FormData(e.target); fd.append('csrf_token', csrfToken);
                const j = await safeFetchJSON('./api/workers.php', { method:'POST', body: fd, credentials:'same-origin' });
                if (j.ok){ closeModal('editModal'); showFlash('Trabajador actualizado'); fetchList(); }
                else {
                    if (j.errors) showFormErrors(e.target, j.errors);
                    showFlash(j.error||'Hay errores en el formulario', 'error');
                    if (j.db_error) console.warn('DB:', j.db_error);
                }
            });
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const confirmDeleteDefault = confirmDeleteBtn ? confirmDeleteBtn.innerHTML : '';
            if (confirmDeleteBtn){
                confirmDeleteBtn.addEventListener('click', async ()=>{
                    if (!deleteTarget) { return; }
                    confirmDeleteBtn.disabled = true;
                    confirmDeleteBtn.innerHTML = 'Eliminando...';
                    const form = new FormData();
                    form.append('action', 'delete');
                    form.append('id', deleteTarget.id);
                    form.append('csrf_token', csrfToken);
                    const j = await safeFetchJSON('./api/workers.php', { method:'POST', body: form, credentials:'same-origin' });
                    confirmDeleteBtn.disabled = false;
                    confirmDeleteBtn.innerHTML = confirmDeleteDefault;
                    if (j.ok) {
                        closeModal('deleteModal');
                        showFlash('Trabajador eliminado');
                        fetchList();
                    } else {
                        showFlash(j.error || 'Error al eliminar', 'error');
                        if (j.db_error) console.warn('DB:', j.db_error);
                    }
                });
            }
        }

        function escapeHtml(str){
            return String(str).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
        }

                function updateSortIndicators(){
                    // Clear
                    document.querySelectorAll('.th-sort').forEach(el=>{ el.classList.remove('active'); const ic = el.querySelector('.sort-icon'); if (ic){ ic.className='sort-icon bi'; } });
                    const active = document.querySelector(`.th-sort[data-sort="${state.sort}"]`);
                    if (active){
                        active.classList.add('active');
                        const ic = active.querySelector('.sort-icon');
                        if (ic){ ic.classList.add(state.dir==='asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill'); }
                    }
                }

                document.addEventListener('DOMContentLoaded', ()=>{ attachEvents(); fetchList(); });

        function clearFormErrors(form){
            form.querySelectorAll('.is-invalid').forEach(el=> el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el=> el.textContent='');
        }
        function showFormErrors(form, errors){
            Object.entries(errors).forEach(([field, msg])=>{
                const input = form.querySelector(`[name="${field}"]`);
                if (input){
                    input.classList.add('is-invalid');
                    const fb = input.closest('.col-md-6, .col-md-4, .col-md-3, .col-md-9, .col-12')?.querySelector('.invalid-feedback') || input.nextElementSibling;
                    if (fb && fb.classList.contains('invalid-feedback')) fb.textContent = msg;
                }
            });
        }
        function resetSearchAndFilters(){
            // limpiar búsqueda y filtros para asegurar que se vea el nuevo registro
            const q = document.getElementById('searchQ'); if (q) q.value = '';
            ['age_min','age_max','work_place_filter'].forEach(id => { const el = document.getElementById(id); if (el) el.value=''; });
            const hg = document.getElementById('has_geo'); if (hg) hg.checked = false;
            state.q=''; state.age_min=''; state.age_max=''; state.work_place=''; state.has_geo=''; state.page=1;
        }
        async function safeFetchJSON(url, options={}){
            let resolved = url;
            try {
                resolved = new URL(url, window.location.href).toString();
                const res = await fetch(resolved, options);
                const raw = await res.text();
                try {
                    return JSON.parse(raw);
                } catch (parseErr) {
                    console.error('Respuesta no-JSON', { url: resolved, status: res.status, raw });
                    return { ok:false, error: extractHtmlError(raw) || raw.slice(0,200) || ('HTTP '+res.status) };
                }
            } catch (err) {
                console.error('Fetch error', {url, resolved, err});
                return { ok:false, error: 'No se pudo conectar con el servidor: ' + (err.message || 'Error de red') };
            }
        }
        function extractHtmlError(html){
            try {
                const div = document.createElement('div');
                div.innerHTML = html;
                const b = div.querySelector('b');
                if (b) return b.textContent;
                return div.textContent.trim().slice(0,200);
            } catch { return null; }
        }
    </script>
    <?php $conn->close(); ?>
</body>
</html>
