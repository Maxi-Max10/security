<?php
// Configuración de la base de datos para Hostinger
// IMPORTANTE: Actualiza estos valores con las credenciales de tu hosting

define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario_hostinger');
define('DB_PASS', 'tu_contraseña_hostinger');
define('DB_NAME', 'tu_base_datos_hostinger');

// Crear conexión
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Error de conexión: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
        
    } catch (Exception $e) {
        error_log($e->getMessage());
        die("Error al conectar con la base de datos. Por favor, intente más tarde.");
    }
}
?>
