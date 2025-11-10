-- =====================================================
-- SCRIPT PARA AGREGAR TABLA workers A UNA BD EXISTENTE
-- =====================================================
USE u404968876_security;

CREATE TABLE IF NOT EXISTS workers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    dni VARCHAR(15) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    cvu_alias VARCHAR(50) NULL,
    age TINYINT UNSIGNED NULL,
    work_place VARCHAR(150) NOT NULL,
    address_text VARCHAR(255) NULL,
    address_url VARCHAR(500) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    INDEX idx_dni (dni),
    INDEX idx_email (email),
    INDEX idx_work_place (work_place),
    CONSTRAINT fk_workers_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_workers_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificación rápida
SELECT COUNT(*) AS total_workers FROM workers;