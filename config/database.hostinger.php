<?php
/**
 * Configuración de Base de Datos para Hostinger
 * 
 * INSTRUCCIONES:
 * 1. En hPanel de Hostinger, ve a "Bases de datos MySQL"
 * 2. Crea una nueva base de datos o usa una existente
 * 3. Anota: nombre de BD, usuario, contraseña y host
 * 4. Reemplaza los valores abajo con tus credenciales reales
 * 5. En phpMyAdmin, importa el archivo database/schema.sql
 */

// Configuración para Hostinger
// El host generalmente es 'localhost' pero puede variar
define('DB_HOST', 'localhost');

// Usuario de la base de datos (formato común: u123456789_nombrebd)
define('DB_USER', 'u123456789_user');

// Contraseña de la base de datos
define('DB_PASS', 'TuContraseñaSegura123');

// Nombre de la base de datos (formato común: u123456789_login)
define('DB_NAME', 'u123456789_login');

/**
 * NOTA IMPORTANTE:
 * En algunos planes de Hostinger, el host puede ser diferente.
 * Si 'localhost' no funciona, prueba con:
 * - El nombre de tu dominio
 * - mysql.hostinger.com
 * - Una IP específica que te proporcione Hostinger
 * 
 * Consulta los detalles exactos en:
 * hPanel → Bases de datos → Tu base de datos → Detalles de conexión
 */

// Crear conexión con manejo de errores mejorado
function getDBConnection() {
    try {
        // Intentar conexión
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Verificar error de conexión
        if ($conn->connect_error) {
            // Log del error (solo para debugging, comentar en producción)
            error_log("Error de conexión MySQL: " . $conn->connect_error);
            throw new Exception("No se pudo conectar a la base de datos.");
        }
        
        // Establecer charset para evitar problemas con caracteres especiales
        if (!$conn->set_charset("utf8mb4")) {
            error_log("Error cargando charset utf8mb4: " . $conn->error);
        }
        
        return $conn;
        
    } catch (Exception $e) {
        // Registrar el error
        error_log("Error DB: " . $e->getMessage());
        
        // En producción, mostrar mensaje genérico
        die("Error al conectar con la base de datos. Por favor, contacte al administrador.");
        
        // Para debugging (descomentar solo en desarrollo):
        // die("Error: " . $e->getMessage());
    }
}

// Función auxiliar para verificar la conexión
function testDBConnection() {
    try {
        $conn = getDBConnection();
        $result = $conn->query("SELECT 1");
        $conn->close();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
