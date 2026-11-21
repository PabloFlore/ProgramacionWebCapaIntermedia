-- Fase 1: cursos navegables, pagos simulados y certificados.

CREATE TABLE IF NOT EXISTS metodos_pago (
    metodo_pago_id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL,
    nombre VARCHAR(80) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_metodo_pago_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO metodos_pago (codigo, nombre, activo)
SELECT 'tarjeta', 'Tarjeta de débito o crédito', TRUE
WHERE NOT EXISTS (SELECT 1 FROM metodos_pago WHERE codigo = 'tarjeta');

INSERT INTO metodos_pago (codigo, nombre, activo)
SELECT 'transferencia', 'Transferencia bancaria', TRUE
WHERE NOT EXISTS (SELECT 1 FROM metodos_pago WHERE codigo = 'transferencia');

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE inscripciones ADD COLUMN metodo_pago_id INT NULL AFTER referencia_pago',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inscripciones'
      AND COLUMN_NAME = 'metodo_pago_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE inscripciones ADD COLUMN orden_id INT NULL AFTER metodo_pago_id',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inscripciones'
      AND COLUMN_NAME = 'orden_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE inscripciones ADD CONSTRAINT fk_inscripcion_metodo_pago FOREIGN KEY (metodo_pago_id) REFERENCES metodos_pago(metodo_pago_id) ON DELETE SET NULL ON UPDATE CASCADE',
        'SELECT 1')
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inscripciones'
      AND CONSTRAINT_NAME = 'fk_inscripcion_metodo_pago'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS ordenes (
    orden_id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    curso_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estatus ENUM('pendiente', 'pagada', 'cancelada') NOT NULL DEFAULT 'pendiente',
    referencia VARCHAR(100) NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_pago DATETIME DEFAULT NULL,
    KEY idx_orden_estatus (estatus),
    KEY fk_orden_estudiante (estudiante_id),
    KEY fk_orden_curso (curso_id),
    CONSTRAINT fk_orden_estudiante
        FOREIGN KEY (estudiante_id) REFERENCES usuarios(usuario_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_orden_curso
        FOREIGN KEY (curso_id) REFERENCES cursos(curso_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_orden_monto CHECK (monto >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orden_detalles (
    detalle_id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT NOT NULL,
    nivel_id INT DEFAULT NULL,
    concepto VARCHAR(180) NOT NULL,
    monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    cantidad INT NOT NULL DEFAULT 1,
    KEY fk_detalle_orden (orden_id),
    KEY fk_detalle_nivel (nivel_id),
    CONSTRAINT fk_detalle_orden
        FOREIGN KEY (orden_id) REFERENCES ordenes(orden_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detalle_nivel
        FOREIGN KEY (nivel_id) REFERENCES niveles(nivel_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pagos (
    pago_id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT NOT NULL,
    metodo_pago_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    referencia VARCHAR(100) NOT NULL,
    estatus ENUM('pendiente', 'aprobado', 'rechazado') NOT NULL DEFAULT 'pendiente',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_confirmacion DATETIME DEFAULT NULL,
    KEY fk_pago_orden (orden_id),
    KEY fk_pago_metodo (metodo_pago_id),
    CONSTRAINT fk_pago_orden
        FOREIGN KEY (orden_id) REFERENCES ordenes(orden_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pago_metodo
        FOREIGN KEY (metodo_pago_id) REFERENCES metodos_pago(metodo_pago_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_pago_monto CHECK (monto >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE inscripciones ADD CONSTRAINT fk_inscripcion_orden FOREIGN KEY (orden_id) REFERENCES ordenes(orden_id) ON DELETE SET NULL ON UPDATE CASCADE',
        'SELECT 1')
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inscripciones'
      AND CONSTRAINT_NAME = 'fk_inscripcion_orden'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
