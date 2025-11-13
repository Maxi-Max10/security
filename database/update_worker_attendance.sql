-- Tabla de registros de asistencia de trabajadores
CREATE TABLE IF NOT EXISTS worker_attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    worker_id INT UNSIGNED NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    recorded_at DATETIME NOT NULL,
    attachment_path VARCHAR(255) NULL,
    attachment_original VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_worker_recorded (worker_id, recorded_at),
    CONSTRAINT fk_worker_attendance_worker FOREIGN KEY (worker_id)
        REFERENCES workers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
