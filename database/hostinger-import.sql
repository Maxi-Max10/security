-- =====================================================
-- IMPORTAR ESTE ARCHIVO EN PHPMYADMIN DE HOSTINGER
-- =====================================================
-- Base de datos: u404968876_security
-- Usuario: u404968876_security
-- 
-- PASOS PARA IMPORTAR:
-- 1. Ve a phpMyAdmin en hPanel de Hostinger
-- 2. En el menú izquierdo, selecciona: u404968876_security
-- 3. Haz clic en la pestaña "Importar" (arriba)
-- 4. Clic en "Seleccionar archivo" 
-- 5. Sube este archivo (hostinger-import.sql)
-- 6. Desplázate abajo y haz clic en "Continuar"
-- 7. ¡Listo! Las tablas se crearán automáticamente
-- =====================================================

-- Configuración de caracteres
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- TABLA: users
-- Almacena información de usuarios registrados
-- =====================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`),
    KEY `idx_email` (`email`),
    KEY `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: login_attempts
-- Registra intentos de login para seguridad
-- =====================================================
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(100) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `attempt_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `success` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_email_time` (`email`, `attempt_time`),
    KEY `idx_ip_time` (`ip_address`, `attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATOS DE PRUEBA
-- Usuario administrador de prueba
-- Email: admin@example.com
-- Password: Admin123
-- =====================================================
INSERT INTO `users` (`username`, `email`, `password`, `is_active`) VALUES 
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- =====================================================
-- Restaurar configuración
-- =====================================================
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- ¡IMPORTACIÓN COMPLETADA!
-- =====================================================
-- Verifica que las tablas se crearon:
-- - users (debe tener 1 registro: admin)
-- - login_attempts (debe estar vacía)
--
-- Ahora configura el archivo de conexión:
-- 1. Ve a config/database.hostinger.php
-- 2. Cópialo como config/database.php
-- 3. Edita las credenciales:
--    DB_HOST = 'localhost'
--    DB_USER = 'u404968876_security'
--    DB_PASS = 'tu_contraseña'
--    DB_NAME = 'u404968876_security'
-- =====================================================
