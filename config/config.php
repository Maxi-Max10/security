<?php
// Configuración general del sistema
define('SITE_URL', 'https://lime-fish-310503.hostingersite.com');
define('SITE_NAME', 'Sistema de Login');

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 cuando configures HTTPS

// Configuración de errores para producción
error_reporting(E_ALL);
ini_set('display_errors', 1); // Temporal para ver errores, cambiar a 0 después
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Zona horaria
date_default_timezone_set('America/Mexico_City');
?>
