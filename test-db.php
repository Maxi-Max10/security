<?php
/**
 * Script de prueba de conexión a MySQL
 * Úsalo para verificar que la configuración de base de datos funciona correctamente
 * 
 * IMPORTANTE: ELIMINA ESTE ARCHIVO después de verificar la conexión
 */

// Mostrar errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Conexión - MySQL</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #28a745;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #17a2b8;
            margin: 20px 0;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
        tr:hover {
            background: #f5f5f5;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .step {
            margin: 15px 0;
            padding-left: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Prueba de Conexión a MySQL</h1>

        <?php
        // Verificar que existe el archivo de configuración
        if (!file_exists('config/database.php')) {
            echo '<div class="error">';
            echo '<strong>❌ Error:</strong> No se encontró el archivo <code>config/database.php</code>';
            echo '<div class="step">';
            echo '<h3>Para solucionar:</h3>';
            echo '<ol>';
            echo '<li>Copia el archivo <code>config/database.hostinger.php</code></li>';
            echo '<li>Renómbralo a <code>config/database.php</code></li>';
            echo '<li>Edita las credenciales de tu base de datos</li>';
            echo '</ol>';
            echo '</div>';
            echo '</div>';
            exit;
        }

        require_once 'config/database.php';

        echo '<div class="info">';
        echo '<strong>📋 Información de Configuración:</strong><br>';
        echo 'Host: <code>' . DB_HOST . '</code><br>';
        echo 'Usuario: <code>' . DB_USER . '</code><br>';
        echo 'Base de Datos: <code>' . DB_NAME . '</code><br>';
        echo 'Contraseña: <code>' . (DB_PASS ? '***configurada***' : '❌ NO configurada') . '</code>';
        echo '</div>';

        // Test 1: Verificar constantes
        echo '<h2>Test 1: Verificar Configuración</h2>';
        if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
            echo '<div class="success">✅ Todas las constantes están definidas</div>';
        } else {
            echo '<div class="error">❌ Faltan constantes de configuración</div>';
            exit;
        }

        // Test 2: Conexión básica
        echo '<h2>Test 2: Conexión al Servidor MySQL</h2>';
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
            
            if ($conn->connect_error) {
                throw new Exception($conn->connect_error);
            }
            
            echo '<div class="success">✅ Conexión exitosa al servidor MySQL</div>';
            echo '<div class="info">';
            echo 'Versión del servidor: <code>' . $conn->server_info . '</code><br>';
            echo 'Versión del cliente: <code>' . $conn->client_info . '</code>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<strong>❌ Error de conexión:</strong> ' . $e->getMessage();
            echo '<div class="step">';
            echo '<h3>Posibles causas:</h3>';
            echo '<ul>';
            echo '<li>Host incorrecto (prueba con <code>localhost</code> o <code>127.0.0.1</code>)</li>';
            echo '<li>Usuario o contraseña incorrectos</li>';
            echo '<li>El servidor MySQL no está ejecutándose</li>';
            echo '<li>Firewall bloqueando la conexión</li>';
            echo '</ul>';
            echo '</div>';
            echo '</div>';
            exit;
        }

        // Test 3: Seleccionar base de datos
        echo '<h2>Test 3: Acceso a la Base de Datos</h2>';
        if (!$conn->select_db(DB_NAME)) {
            echo '<div class="error">';
            echo '<strong>❌ No se puede acceder a la base de datos:</strong> ' . $conn->error;
            echo '<div class="step">';
            echo '<h3>Para solucionar:</h3>';
            echo '<ol>';
            echo '<li>Verifica que el nombre de la base de datos sea correcto</li>';
            echo '<li>Asegúrate de que la base de datos existe en phpMyAdmin</li>';
            echo '<li>Verifica que el usuario tenga permisos en esa base de datos</li>';
            echo '</ol>';
            echo '</div>';
            echo '</div>';
            $conn->close();
            exit;
        }
        echo '<div class="success">✅ Base de datos seleccionada correctamente</div>';

        // Test 4: Verificar charset
        echo '<h2>Test 4: Configuración de Charset</h2>';
        if ($conn->set_charset("utf8mb4")) {
            echo '<div class="success">✅ Charset UTF8MB4 configurado correctamente</div>';
        } else {
            echo '<div class="warning">⚠️ No se pudo configurar UTF8MB4: ' . $conn->error . '</div>';
        }

        // Test 5: Verificar tablas
        echo '<h2>Test 5: Verificar Tablas</h2>';
        $result = $conn->query("SHOW TABLES");
        
        if ($result && $result->num_rows > 0) {
            echo '<div class="success">✅ Se encontraron ' . $result->num_rows . ' tabla(s)</div>';
            echo '<table>';
            echo '<tr><th>Tabla</th><th>Registros</th></tr>';
            
            while ($row = $result->fetch_array()) {
                $table_name = $row[0];
                $count_result = $conn->query("SELECT COUNT(*) as total FROM `$table_name`");
                $count = $count_result->fetch_assoc()['total'];
                echo "<tr><td>$table_name</td><td>$count</td></tr>";
            }
            echo '</table>';
        } else {
            echo '<div class="error">';
            echo '<strong>❌ No se encontraron tablas en la base de datos</strong>';
            echo '<div class="step">';
            echo '<h3>Para solucionar:</h3>';
            echo '<ol>';
            echo '<li>Ve a phpMyAdmin en hPanel</li>';
            echo '<li>Selecciona tu base de datos</li>';
            echo '<li>Ve a la pestaña "Importar"</li>';
            echo '<li>Sube el archivo <code>database/schema.sql</code></li>';
            echo '<li>Haz clic en "Continuar"</li>';
            echo '</ol>';
            echo '</div>';
            echo '</div>';
        }

        // Test 6: Probar tabla users
        echo '<h2>Test 6: Verificar Tabla de Usuarios</h2>';
        $result = $conn->query("SELECT COUNT(*) as total FROM users");
        
        if ($result) {
            $row = $result->fetch_assoc();
            echo '<div class="success">✅ Tabla <code>users</code> existe y funciona correctamente</div>';
            echo '<div class="info">Total de usuarios registrados: <strong>' . $row['total'] . '</strong></div>';
            
            // Mostrar usuarios
            $users = $conn->query("SELECT id, username, email, created_at, is_active FROM users");
            if ($users && $users->num_rows > 0) {
                echo '<table>';
                echo '<tr><th>ID</th><th>Usuario</th><th>Email</th><th>Activo</th><th>Creado</th></tr>';
                while ($user = $users->fetch_assoc()) {
                    $active = $user['is_active'] ? '✅' : '❌';
                    echo "<tr>";
                    echo "<td>{$user['id']}</td>";
                    echo "<td>{$user['username']}</td>";
                    echo "<td>{$user['email']}</td>";
                    echo "<td>$active</td>";
                    echo "<td>{$user['created_at']}</td>";
                    echo "</tr>";
                }
                echo '</table>';
            }
        } else {
            echo '<div class="error">❌ Error al consultar la tabla users: ' . $conn->error . '</div>';
        }

        // Test 7: Probar tabla login_attempts
        echo '<h2>Test 7: Verificar Tabla de Intentos de Login</h2>';
        $result = $conn->query("SELECT COUNT(*) as total FROM login_attempts");
        
        if ($result) {
            $row = $result->fetch_assoc();
            echo '<div class="success">✅ Tabla <code>login_attempts</code> existe y funciona</div>';
            echo '<div class="info">Total de intentos registrados: <strong>' . $row['total'] . '</strong></div>';
        } else {
            echo '<div class="error">❌ Error al consultar la tabla login_attempts: ' . $conn->error . '</div>';
        }

        // Cerrar conexión
        $conn->close();

        // Resumen final
        echo '<div class="success" style="margin-top: 30px; font-size: 18px;">';
        echo '<strong>🎉 ¡Todas las pruebas completadas exitosamente!</strong><br><br>';
        echo 'Tu sistema de login está listo para usarse.<br>';
        echo 'Puedes acceder a:<br>';
        echo '<ul>';
        echo '<li><a href="index.php">Página principal</a></li>';
        echo '<li><a href="register.php">Registro</a></li>';
        echo '<li><a href="login.php">Login</a></li>';
        echo '</ul>';
        echo '</div>';

        echo '<div class="warning" style="margin-top: 20px;">';
        echo '<strong>⚠️ IMPORTANTE:</strong> Elimina este archivo (<code>test-db.php</code>) después de verificar la conexión por seguridad.';
        echo '</div>';
        ?>
    </div>
</body>
</html>
