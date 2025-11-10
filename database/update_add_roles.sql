-- =====================================================
-- SCRIPT PARA ACTUALIZAR LA BASE DE DATOS EXISTENTE
-- Agregar campo 'role' a usuarios existentes
-- =====================================================

-- IMPORTANTE: Ejecuta este script en phpMyAdmin si ya tienes
-- la base de datos creada y quieres agregar el campo 'role'

USE u404968876_security;

-- Agregar columna 'role' si no existe
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER password;

-- Agregar índice para role
ALTER TABLE users 
ADD INDEX IF NOT EXISTS idx_role (role);

-- Actualizar el usuario admin existente
UPDATE users 
SET role = 'admin', 
    password = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92IKe1B5BLLRlIJ/oVq7i'
WHERE email = 'admin@example.com';

-- Verificar cambios
SELECT id, username, email, role, is_active FROM users;

-- =====================================================
-- RESULTADO ESPERADO:
-- El usuario admin ahora tiene:
-- - Role: admin
-- - Password: 12345
-- - Todos los demás usuarios tienen role: user
-- =====================================================
