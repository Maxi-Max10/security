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
                <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <form class="d-flex gap-2" id="searchForm">
                        <input type="text" class="form-control form-control-sm" id="searchQ" placeholder="Buscar por nombre, apellido o DNI">
                        <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search"></i> Buscar</button>
                    </form>
                    <button class="btn btn-success btn-sm" onclick="openModal('createModal')"><i class="bi bi-plus-lg"></i> Nuevo trabajador</button>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Listado</strong>
                    <div class="small text-muted">Ordenar: clic en encabezados</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="sortable"><a href="#" data-sort="first_name">Nombre</a></th>
                            <th class="sortable"><a href="#" data-sort="last_name">Apellido</a></th>
                            <th class="sortable"><a href="#" data-sort="dni">DNI</a></th>
                            <th class="sortable"><a href="#" data-sort="email">Email</a></th>
                            <th>CVU/Alias</th>
                            <th class="sortable"><a href="#" data-sort="age">Edad</a></th>
                            <th class="sortable"><a href="#" data-sort="work_place">Lugar de trabajo</a></th>
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
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Apellido *</label>
                                    <input type="text" class="form-control" name="last_name" required value="<?php echo htmlspecialchars($form_old['last_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">DNI *</label>
                                    <input type="text" class="form-control" name="dni" required pattern="\d{7,10}" value="<?php echo htmlspecialchars($form_old['dni'] ?? ''); ?>">
                                    <div class="form-text">Solo números, 7 a 10 dígitos</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($form_old['email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">CVU / Alias</label>
                                    <input type="text" class="form-control" name="cvu_alias" value="<?php echo htmlspecialchars($form_old['cvu_alias'] ?? ''); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Edad</label>
                                    <input type="number" class="form-control" name="age" min="16" max="100" value="<?php echo htmlspecialchars($form_old['age'] ?? ''); ?>">
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label">Lugar de trabajo *</label>
                                    <input type="text" class="form-control" name="work_place" required value="<?php echo htmlspecialchars($form_old['work_place'] ?? ''); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Dirección o URL de Google Maps</label>
                                    <input type="text" class="form-control" id="createAddress" name="address" onblur="parseAddressOnBlur('createAddress','createHint')" value="<?php echo htmlspecialchars($form_old['address'] ?? ''); ?>">
                                    <div class="form-text" id="createHint"></div>
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
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Apellido *</label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">DNI *</label>
                                    <input type="text" class="form-control" name="dni" required pattern="\d{7,10}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">CVU / Alias</label>
                                    <input type="text" class="form-control" name="cvu_alias">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Edad</label>
                                    <input type="number" class="form-control" name="age" min="16" max="100">
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label">Lugar de trabajo *</label>
                                    <input type="text" class="form-control" name="work_place" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Dirección o URL de Google Maps</label>
                                    <input type="text" class="form-control" id="editAddress" name="address" onblur="parseAddressOnBlur('editAddress','editHint')">
                                    <div class="form-text" id="editHint"></div>
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

    <script>
        let createModal, editModal;
        document.addEventListener('DOMContentLoaded', ()=>{
          if (window.bootstrap){
            const cm = document.getElementById('createModal');
            const em = document.getElementById('editModal');
            createModal = new bootstrap.Modal(cm);
            editModal = new bootstrap.Modal(em);
          }
        });
        function openModal(id){ if (id==='createModal' && createModal) createModal.show(); else if (id==='editModal' && editModal) editModal.show(); }
        function closeModal(id){ if (id==='createModal' && createModal) createModal.hide(); else if (id==='editModal' && editModal) editModal.hide(); }
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
        const csrfToken = <?php echo json_encode($csrf_token); ?>;
        let state = { page: 1, per_page: 10, sort: 'last_name', dir: 'asc', q: '' };

        function showFlash(text, type='success'){
            const box = document.getElementById('flash');
            box.className = 'alert alert-' + (type==='success'?'success':type);
            box.textContent = text;
            box.classList.remove('d-none');
            setTimeout(()=>{ box.classList.add('d-none'); }, 3000);
        }

        async function fetchList(){
            const params = new URLSearchParams({ action:'list', page: state.page, limit: state.per_page, sort: state.sort, dir: state.dir, q: state.q });
            const res = await fetch('api/workers.php?' + params.toString(), { credentials: 'same-origin' });
            const json = await res.json();
            if (!json.ok) { showFlash(json.error || 'Error al cargar', 'error'); return; }
            renderTable(json.data);
            renderPagination(json);
        }

        function renderTable(rows){
            const tbody = document.getElementById('workersBody');
            tbody.innerHTML = '';
            rows.forEach(w => {
                const tr = document.createElement('tr');
                const addr = w.address_url ? `<a href="${w.address_url}" target="_blank">Mapa</a>${(w.latitude && w.longitude)?` <small>(${w.latitude}, ${w.longitude})</small>`:''}` : (w.address_text||'');
                tr.innerHTML = `
                    <td>${escapeHtml(w.first_name||'')}</td>
                    <td>${escapeHtml(w.last_name||'')}</td>
                    <td>${escapeHtml(w.dni||'')}</td>
                    <td>${escapeHtml(w.email||'')}</td>
                    <td>${escapeHtml(w.cvu_alias||'')}</td>
                    <td>${w.age??''}</td>
                    <td>${escapeHtml(w.work_place||'')}</td>
                    <td>${addr}</td>
                    <td>
                        <div class="grid-actions">
                            <button class="btn btn-small btn-outline" data-action="edit" data-id="${w.id}">✏️ Editar</button>
                            <button class="btn btn-small btn-danger" data-action="delete" data-id="${w.id}">🗑️ Eliminar</button>
                        </div>
                    </td>`;
                tbody.appendChild(tr);
            });
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
            // Search
            document.getElementById('searchForm').addEventListener('submit', (e)=>{
                e.preventDefault();
                state.q = document.getElementById('searchQ').value.trim();
                state.page = 1; fetchList();
            });
            // Delegated actions (edit/delete)
            document.getElementById('workersBody').addEventListener('click', async (e)=>{
                const btn = e.target.closest('button'); if (!btn) return;
                const act = btn.getAttribute('data-action'); const id = btn.getAttribute('data-id');
                if (act === 'edit') {
                    // load data by id (optional) or reuse row; we'll query API
                    const res = await fetch('api/workers.php?action=get&id=' + id); const j = await res.json();
                    if (j.ok){ editWorker({ id: j.data.id, first_name: j.data.first_name, last_name: j.data.last_name, dni: j.data.dni, email: j.data.email, cvu_alias: j.data.cvu_alias, age: j.data.age, work_place: j.data.work_place, address: j.data.address_url ? j.data.address_url : (j.data.address_text||'') }); }
                } else if (act === 'delete') {
                    if (!confirm('¿Eliminar este trabajador?')) return;
                    const form = new FormData(); form.append('action','delete'); form.append('id', id); form.append('csrf_token', csrfToken);
                    const res = await fetch('api/workers.php', { method:'POST', body: form, credentials:'same-origin' }); const j = await res.json();
                    if (j.ok) { showFlash('Trabajador eliminado'); fetchList(); } else { showFlash(j.error||'Error al eliminar', 'error'); }
                }
            });
            // Create
            document.getElementById('createForm').addEventListener('submit', async (e)=>{
                e.preventDefault(); const fd = new FormData(e.target); fd.append('csrf_token', csrfToken);
                const res = await fetch('api/workers.php', { method:'POST', body: fd, credentials:'same-origin' }); const j = await res.json();
                if (j.ok){ closeModal('createModal'); showFlash('Trabajador creado'); fetchList(); e.target.reset(); }
                else { showFlash(j.error||'Hay errores en el formulario', 'error'); }
            });
            // Update
            document.getElementById('editForm').addEventListener('submit', async (e)=>{
                e.preventDefault(); const fd = new FormData(e.target); fd.append('csrf_token', csrfToken);
                const res = await fetch('api/workers.php', { method:'POST', body: fd, credentials:'same-origin' }); const j = await res.json();
                if (j.ok){ closeModal('editModal'); showFlash('Trabajador actualizado'); fetchList(); }
                else { showFlash(j.error||'Hay errores en el formulario', 'error'); }
            });
        }

        function escapeHtml(str){
            return String(str).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
        }

        document.addEventListener('DOMContentLoaded', ()=>{ attachEvents(); fetchList(); });
    </script>
    <?php $conn->close(); ?>
</body>
</html>
