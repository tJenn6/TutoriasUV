CREATE TABLE IF NOT EXISTS asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    horario_id INT NOT NULL,
    estudiante_id INT NOT NULL,
    asistio TINYINT(1) NOT NULL,
    observaciones VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_asistencia_horario_estudiante (horario_id, estudiante_id),
    CONSTRAINT fk_asistencias_horario
        FOREIGN KEY (horario_id) REFERENCES horarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_asistencias_estudiante
        FOREIGN KEY (estudiante_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
