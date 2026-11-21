-- Migracion para bases de datos soscur_db creadas con el esquema anterior.
-- Ejecutar una sola vez sobre una base existente:
--   mysql -u root -p soscur_db < migrations/001_align_schema_ui.sql
--
-- Si la base se puede recrear desde cero, basta con ejecutar schema.sql.

USE soscur_db;

-- ---------------------------------------------------------------------------
-- usuarios: perfil, verificacion, recordar sesion y bloqueo con motivo
-- ---------------------------------------------------------------------------
ALTER TABLE usuarios
    ADD COLUMN genero ENUM('femenino', 'masculino', 'prefiero_no_decir') NULL AFTER rol,
    ADD COLUMN fecha_nacimiento DATE NULL AFTER genero,
    ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER fecha_nacimiento,
    ADD COLUMN correo_verificado BOOLEAN NOT NULL DEFAULT FALSE AFTER foto_perfil,
    ADD COLUMN token_verificacion VARCHAR(100) NULL AFTER correo_verificado,
    ADD COLUMN motivo_bloqueo VARCHAR(255) NULL AFTER bloqueado,
    ADD COLUMN fecha_bloqueo DATETIME NULL AFTER motivo_bloqueo,
    ADD COLUMN bloqueado_por INT NULL AFTER fecha_bloqueo,
    ADD COLUMN remember_token VARCHAR(100) NULL AFTER bloqueado_por,
    ADD COLUMN remember_token_expira DATETIME NULL AFTER remember_token,
    ADD COLUMN fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER remember_token_expira;

ALTER TABLE usuarios
    ADD UNIQUE KEY uq_usuario_token_verificacion (token_verificacion),
    ADD KEY idx_usuario_remember_token (remember_token),
    ADD KEY idx_usuario_rol_bloqueado (rol, bloqueado),
    ADD CONSTRAINT fk_usuario_bloqueado_por
        FOREIGN KEY (bloqueado_por) REFERENCES usuarios(usuario_id)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- ---------------------------------------------------------------------------
-- cursos: descripcion, imagen, modalidad de precio y fecha de creacion
-- ---------------------------------------------------------------------------
ALTER TABLE cursos
    ADD COLUMN descripcion TEXT NULL AFTER titulo,
    ADD COLUMN imagen VARCHAR(255) NULL AFTER descripcion,
    ADD COLUMN tipo_precio ENUM('curso', 'nivel') NOT NULL DEFAULT 'curso' AFTER precio,
    ADD COLUMN fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER estatus;

ALTER TABLE cursos
    ADD KEY idx_curso_estatus_fecha (estatus, fecha_creacion),
    ADD CONSTRAINT chk_curso_precio CHECK (precio >= 0);

-- ---------------------------------------------------------------------------
-- niveles: orden, precio por nivel y material complementario
-- ---------------------------------------------------------------------------
ALTER TABLE niveles
    ADD COLUMN orden INT NOT NULL DEFAULT 1 AFTER video_url,
    ADD COLUMN precio DECIMAL(10,2) NULL AFTER orden,
    ADD COLUMN material_url VARCHAR(255) NULL AFTER precio,
    ADD COLUMN material_tipo ENUM('pdf', 'enlace') NULL AFTER material_url;

-- Rellenar el orden de los niveles existentes antes de crear la clave unica.
UPDATE niveles n
JOIN (
    SELECT nivel_id, ROW_NUMBER() OVER (PARTITION BY curso_id ORDER BY nivel_id) AS rn
    FROM niveles
) x ON x.nivel_id = n.nivel_id
SET n.orden = x.rn;

ALTER TABLE niveles
    ADD UNIQUE KEY uq_nivel_curso_orden (curso_id, orden),
    ADD CONSTRAINT chk_nivel_precio CHECK (precio IS NULL OR precio >= 0),
    ADD CONSTRAINT chk_nivel_material CHECK (
        (material_url IS NULL AND material_tipo IS NULL)
        OR (material_url IS NOT NULL AND material_tipo IS NOT NULL)
    );

-- ---------------------------------------------------------------------------
-- inscripciones: fecha, progreso y estado de pago
-- ---------------------------------------------------------------------------
ALTER TABLE inscripciones
    ADD COLUMN estado_pago ENUM('pendiente', 'pagado', 'reembolsado') NOT NULL DEFAULT 'pendiente' AFTER monto_pagado,
    ADD COLUMN fecha_pago DATETIME NULL AFTER estado_pago,
    ADD COLUMN referencia_pago VARCHAR(100) NULL AFTER fecha_pago,
    ADD COLUMN progreso TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER referencia_pago,
    ADD COLUMN fecha_inscripcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER completado,
    ADD COLUMN fecha_completado DATETIME NULL AFTER fecha_inscripcion;

UPDATE inscripciones
SET estado_pago = CASE
        WHEN monto_pagado > 0 THEN 'pagado'
        ELSE estado_pago
    END,
    progreso = CASE
        WHEN completado = TRUE THEN 100
        ELSE progreso
    END,
    fecha_completado = CASE
        WHEN completado = TRUE AND fecha_completado IS NULL THEN NOW()
        ELSE fecha_completado
    END
WHERE completado = TRUE OR monto_pagado > 0;

ALTER TABLE inscripciones
    ADD KEY idx_inscripcion_curso_pago (curso_id, estado_pago),
    ADD CONSTRAINT chk_inscripcion_progreso CHECK (progreso BETWEEN 0 AND 100),
    ADD CONSTRAINT chk_inscripcion_monto CHECK (monto_pagado >= 0);

-- ---------------------------------------------------------------------------
-- progreso_niveles: detalle del avance por nivel
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS progreso_niveles (
    progreso_id INT AUTO_INCREMENT PRIMARY KEY,
    inscripcion_id INT NOT NULL,
    nivel_id INT NOT NULL,
    completado BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_completado DATETIME NULL,
    UNIQUE KEY uq_progreso_inscripcion_nivel (inscripcion_id, nivel_id),
    KEY idx_progreso_nivel (nivel_id),
    CONSTRAINT fk_progreso_inscripcion
        FOREIGN KEY (inscripcion_id) REFERENCES inscripciones(inscripcion_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_progreso_nivel
        FOREIGN KEY (nivel_id) REFERENCES niveles(nivel_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Para inscripciones ya completadas, marcar todos sus niveles como completados.
INSERT INTO progreso_niveles (inscripcion_id, nivel_id, completado, fecha_completado)
SELECT i.inscripcion_id,
       n.nivel_id,
       TRUE,
       COALESCE(i.fecha_completado, NOW())
FROM inscripciones i
JOIN niveles n ON n.curso_id = i.curso_id
WHERE i.completado = TRUE
ON DUPLICATE KEY UPDATE
    completado = VALUES(completado),
    fecha_completado = VALUES(fecha_completado);

-- ---------------------------------------------------------------------------
-- comentarios: causa de moderacion y moderador
-- ---------------------------------------------------------------------------
ALTER TABLE comentarios
    ADD COLUMN causa_eliminacion TEXT NULL AFTER eliminado_por_admin,
    ADD COLUMN moderado_por INT NULL AFTER causa_eliminacion,
    ADD COLUMN fecha_moderacion DATETIME NULL AFTER moderado_por;

UPDATE comentarios
SET causa_eliminacion = 'Causa no registrada (migracion)',
    fecha_moderacion = COALESCE(fecha_moderacion, NOW())
WHERE eliminado_por_admin = TRUE
  AND (causa_eliminacion IS NULL OR TRIM(causa_eliminacion) = '');

ALTER TABLE comentarios
    ADD KEY idx_comentario_curso_fecha (curso_id, fecha_comentario),
    ADD CONSTRAINT fk_comentario_moderador
        FOREIGN KEY (moderado_por) REFERENCES usuarios(usuario_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT chk_comentario_causa CHECK (
        eliminado_por_admin = FALSE OR causa_eliminacion IS NOT NULL
    );

-- ---------------------------------------------------------------------------
-- recuperacion_contrasena
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS recuperacion_contrasena (
    recuperacion_id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token VARCHAR(100) NOT NULL UNIQUE,
    expira_en DATETIME NOT NULL,
    usado BOOLEAN NOT NULL DEFAULT FALSE,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_recuperacion_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(usuario_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
