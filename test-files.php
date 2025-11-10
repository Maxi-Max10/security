<?php
// Script simple para verificar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de Archivos</h1>";

// Verificar que existen los archivos
$files = [
    'config/config.php',
    'config/database.php',
    'includes/functions.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file existe<br>";
    } else {
        echo "❌ $file NO existe<br>";
    }
}

echo "<br><h2>Test de Includes</h2>";

try {
    require_once 'config/config.php';
    echo "✅ config.php cargado<br>";
} catch (Exception $e) {
    echo "❌ Error en config.php: " . $e->getMessage() . "<br>";
}

try {
    require_once 'config/database.php';
    echo "✅ database.php cargado<br>";
} catch (Exception $e) {
    echo "❌ Error en database.php: " . $e->getMessage() . "<br>";
}

try {
    require_once 'includes/functions.php';
    echo "✅ functions.php cargado<br>";
} catch (Exception $e) {
    echo "❌ Error en functions.php: " . $e->getMessage() . "<br>";
}

echo "<br><h2>Test de Sesión</h2>";
session_start();
echo "✅ Sesión iniciada<br>";

echo "<br><h2>Test de Base de Datos</h2>";
try {
    $conn = getDBConnection();
    echo "✅ Conexión a BD exitosa<br>";
    $conn->close();
} catch (Exception $e) {
    echo "❌ Error de BD: " . $e->getMessage() . "<br>";
}

echo "<br><h2>Conclusión</h2>";
echo "Si todos los tests pasaron, <a href='register.php'>intenta registrarte aquí</a>";
?>
