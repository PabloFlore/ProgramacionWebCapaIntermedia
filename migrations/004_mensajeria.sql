-- Fase 3: mensajería interna entre estudiantes, instructores y administración.

CREATE TABLE IF NOT EXISTS mensajes (
    mensaje_id INT AUTO_INCREMENT PRIMARY KEY,
    remitente_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    curso_id INT NULL,
    asunto VARCHAR(150) NOT NULL,
    cuerpo TEXT NOT NULL,
    leido BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_leido DATETIME NULL,
    KEY idx_mensaje_destinatario_leido (destinatario_id, leido),
    KEY idx_mensaje_remitente (remitente_id),
    KEY fk_mensaje_curso (curso_id),
    CONSTRAINT fk_mensaje_remitente
        FOREIGN KEY (remitente_id) REFERENCES usuarios(usuario_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_mensaje_destinatario
        FOREIGN KEY (destinatario_id) REFERENCES usuarios(usuario_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_mensaje_curso
        FOREIGN KEY (curso_id) REFERENCES cursos(curso_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
