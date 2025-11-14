<?php
// Exportación de trabajadores en CSV, TXT o Excel (XLS simple)
session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    http_response_code(403);
    echo 'No autorizado';
    exit;
}

$format = strtolower(trim($_GET['format'] ?? 'csv'));
$allowed = ['csv','txt','excel'];
if (!in_array($format, $allowed, true)) {
    $format = 'csv';
}

$conn = getDBConnection();

// Filtros (mismos que API list)
$q = trim($_GET['q'] ?? '');
$age_min = isset($_GET['age_min']) && $_GET['age_min'] !== '' ? intval($_GET['age_min']) : null;
$age_max = isset($_GET['age_max']) && $_GET['age_max'] !== '' ? intval($_GET['age_max']) : null;
$work_place_f = trim($_GET['work_place'] ?? '');
$has_geo = isset($_GET['has_geo']) && $_GET['has_geo'] == '1';
$sort = $_GET['sort'] ?? 'last_name';
$dir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
$allowed_sort = ['first_name','last_name','dni','email','age','work_place','created_at'];
if (!in_array($sort, $allowed_sort)) { $sort = 'last_name'; }

$whereParts = [];
$params = [];
$types = '';
if ($q !== '') {
    $whereParts[] = '(first_name LIKE ? OR last_name LIKE ? OR dni LIKE ?)';
    $like = "%$q%"; $params[] = $like; $params[] = $like; $params[] = $like; $types .= 'sss';
}
if (!is_null($age_min)) { $whereParts[] = '(age IS NOT NULL AND age >= ?)'; $params[] = $age_min; $types .= 'i'; }
if (!is_null($age_max)) { $whereParts[] = '(age IS NOT NULL AND age <= ?)'; $params[] = $age_max; $types .= 'i'; }
if ($work_place_f !== '') { $whereParts[] = 'work_place LIKE ?'; $params[] = "%$work_place_f%"; $types .= 's'; }
if ($has_geo) { $whereParts[] = '((latitude IS NOT NULL AND longitude IS NOT NULL) OR address_url IS NOT NULL)'; }
$whereSQL = count($whereParts) ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

$sql = "SELECT id, first_name, last_name, dni, email, cvu_alias, age, work_place, address_text, address_url, latitude, longitude, created_at, updated_at FROM workers $whereSQL ORDER BY $sort $dir";
if ($whereSQL) {
    $stmt = $conn->prepare($sql);
    if ($types !== '') { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($sql);
}

$rows = [];
if ($res) {
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }
}
if (isset($stmt)) { $stmt->close(); }
$conn->close();

$filenameBase = 'workers_' . date('Ymd_His');

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filenameBase . '.csv');
    // UTF-8 BOM para Excel
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, array_keys($rows[0] ?? [
        'id'=>'','first_name'=>'','last_name'=>'','dni'=>'','email'=>'','cvu_alias'=>'','age'=>'','work_place'=>'','address_text'=>'','address_url'=>'','latitude'=>'','longitude'=>'','created_at'=>'','updated_at'=>''
    ]));
    foreach ($rows as $r) {
        fputcsv($out, $r);
    }
    fclose($out);
    exit;
}

if ($format === 'txt') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filenameBase . '.txt');
    // Header line
    $headers = array_keys($rows[0] ?? [
        'id'=>'','first_name'=>'','last_name'=>'','dni'=>'','email'=>'','cvu_alias'=>'','age'=>'','work_place'=>'','address_text'=>'','address_url'=>'','latitude'=>'','longitude'=>'','created_at'=>'','updated_at'=>''
    ]);
    echo implode("\t", $headers) . "\n";
    foreach ($rows as $r) {
        $line = [];
        foreach ($headers as $h) { $line[] = str_replace(["\t","\n","\r"], ' ', (string)($r[$h] ?? '')); }
        echo implode("\t", $line) . "\n";
    }
    exit;
}

// Excel (XLS simple vía HTML table, compatible)
if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filenameBase . '.xls');
    echo "<html><head><meta charset=\"utf-8\"><style>table{border-collapse:collapse}td,th{border:1px solid #999;padding:4px;font-size:12px}</style></head><body>";
    echo '<table>';    
    $headers = array_keys($rows[0] ?? [
        'id'=>'','first_name'=>'','last_name'=>'','dni'=>'','email'=>'','cvu_alias'=>'','age'=>'','work_place'=>'','address_text'=>'','address_url'=>'','latitude'=>'','longitude'=>'','created_at'=>'','updated_at'=>''
    ]);
    echo '<tr>'; foreach ($headers as $h) { echo '<th>' . htmlspecialchars($h) . '</th>'; } echo '</tr>';
    foreach ($rows as $r) {
        echo '<tr>'; foreach ($headers as $h) { echo '<td>' . htmlspecialchars((string)($r[$h] ?? '')) . '</td>'; } echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

// Fallback improbable
http_response_code(400);
echo 'Formato inválido';
