-- =====================================================
-- SCRIPT DE IMPORTACIÓN PARA HOSTINGER
-- Base de datos: u404968876_security
-- =====================================================
-- INSTRUCCIONES:
-- 1. Accede a phpMyAdmin en Hostinger
-- 2. Selecciona la base de datos: u404968876_security
-- 3. Ve a la pestaña "Importar"
-- 4. Selecciona este archivo (schema.sql)
-- 5. Haz clic en "Continuar"
-- =====================================================

-- NO crear base de datos (ya existe en Hostinger)
-- La base de datos ya fue creada: u404968876_security

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de intentos de login (seguridad)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) DEFAULT 0,
    INDEX idx_email_time (email, attempt_time),
    INDEX idx_ip_time (ip_address, attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuario administrador (username: admin, password: 12345)
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92IKe1B5BLLRlIJ/oVq7i', 'admin');
