<?php
// Configuración general del sistema
define('SITE_URL', 'http://localhost/seguridad');
define('SITE_NAME', 'Sistema de Login');

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 si usas HTTPS

// Configuración de errores (cambiar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Zona horaria
date_default_timezone_set('America/Mexico_City');
?>
