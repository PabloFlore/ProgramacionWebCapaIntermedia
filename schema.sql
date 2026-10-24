CREATE DATABASE IF NOT EXISTS soscur_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE soscur_db;

-- usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    usuario_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    contrasena_hash VARCHAR(255) NOT NULL,
    rol ENUM('estudiante', 'instructor', 'admin') NOT NULL DEFAULT 'estudiante',
    genero ENUM('femenino', 'masculino', 'prefiero_no_decir') DEFAULT NULL,
    fecha_nacimiento DATE DEFAULT NULL,
    foto_perfil VARCHAR(255) DEFAULT NULL,
    correo_verificado BOOLEAN NOT NULL DEFAULT FALSE,
    token_verificacion VARCHAR(100) DEFAULT NULL,
    intentos_fallidos TINYINT NOT NULL DEFAULT 0,
    bloqueado BOOLEAN NOT NULL DEFAULT FALSE,
    motivo_bloqueo VARCHAR(255) DEFAULT NULL,
    fecha_bloqueo DATETIME DEFAULT NULL,
    bloqueado_por INT DEFAULT NULL,
    remember_token VARCHAR(100) DEFAULT NULL,
    remember_token_expira DATETIME DEFAULT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuario_correo (correo),
    UNIQUE KEY uq_usuario_token_verificacion (token_verificacion),
    KEY idx_usuario_rol_bloqueado (rol, bloqueado),
    KEY idx_usuario_remember_token (remember_token),
    KEY fk_usuario_bloqueado_por (bloqueado_por),
    CONSTRAINT fk_usuario_bloqueado_por
        FOREIGN KEY (bloqueado_por) REFERENCES usuarios(usuario_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- categorias
CREATE TABLE IF NOT EXISTS categorias (
    categoria_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    creado_por INT DEFAULT NULL,
    UNIQUE KEY uq_categoria_nombre (nombre),
    KEY fk_categoria_creador (creado_por),
    CONSTRAINT fk_categoria_creador
        FOREIGN KEY (creado_por) REFERENCES usuarios(usuario_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- cursos
CREATE TABLE IF NOT EXISTS cursos (
    curso_id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    imagen VARCHAR(255) DEFAULT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tipo_precio ENUM('curso', 'nivel') NOT NULL DEFAULT 'curso',
    instructor_id INT NOT NULL,
    categoria_id INT NOT NULL,
    estatus ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_curso_estatus_fecha (estatus, fecha_creacion),
    KEY fk_curso_instructor (instructor_id),
    KEY fk_curso_categoria (categoria_id),
    CONSTRAINT fk_curso_instructor
        FOREIGN KEY (instructor_id) REFERENCES usuarios(usuario_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_curso_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias(categoria_id)
        ON UPDATE CASCADE,
    CONSTRAINT chk_curso_precio CHECK (precio >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- niveles
CREATE TABLE IF NOT EXISTS niveles (
    nivel_id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    video_url VARCHAR(255) NOT NULL,
    orden INT NOT NULL DEFAULT 1,
    precio DECIMAL(10,2) DEFAULT NULL,
    material_url VARCHAR(255) DEFAULT NULL,
    material_tipo ENUM('pdf', 'enlace') DEFAULT NULL,
    UNIQUE KEY uq_nivel_curso_orden (curso_id, orden),
    CONSTRAINT fk_nivel_curso
        FOREIGN KEY (curso_id) REFERENCES cursos(curso_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_nivel_precio CHECK (precio IS NULL OR precio >= 0),
    CONSTRAINT chk_nivel_material CHECK (
        (material_url IS NULL AND material_tipo IS NULL)
        OR (material_url IS NOT NULL AND material_tipo IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- inscripciones
CREATE TABLE IF NOT EXISTS inscripciones (
    inscripcion_id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    curso_id INT NOT NULL,
    monto_pagado DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado_pago ENUM('pendiente', 'pagado', 'reembolsado') NOT NULL DEFAULT 'pendiente',
    fecha_pago DATETIME DEFAULT NULL,
    referencia_pago VARCHAR(100) DEFAULT NULL,
    progreso TINYINT UNSIGNED NOT NULL DEFAULT 0,
    completado BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_inscripcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_completado DATETIME DEFAULT NULL,
    UNIQUE KEY uq_estudiante_curso (estudiante_id, curso_id),
    KEY idx_inscripcion_curso_pago (curso_id, estado_pago),
    CONSTRAINT fk_inscripcion_estudiante
        FOREIGN KEY (estudiante_id) REFERENCES usuarios(usuario_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_inscripcion_curso
        FOREIGN KEY (curso_id) REFERENCES cursos(curso_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_inscripcion_progreso CHECK (progreso BETWEEN 0 AND 100),
    CONSTRAINT chk_inscripcion_monto CHECK (monto_pagado >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- progreso de niveles
CREATE TABLE IF NOT EXISTS progreso_niveles (
    progreso_id INT AUTO_INCREMENT PRIMARY KEY,
    inscripcion_id INT NOT NULL,
    nivel_id INT NOT NULL,
    completado BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_completado DATETIME DEFAULT NULL,
    UNIQUE KEY uq_progreso_inscripcion_nivel (inscripcion_id, nivel_id),
    KEY idx_progreso_nivel (nivel_id),
    CONSTRAINT fk_progreso_inscripcion
        FOREIGN KEY (inscripcion_id) REFERENCES inscripciones(inscripcion_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_progreso_nivel
        FOREIGN KEY (nivel_id) REFERENCES niveles(nivel_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- comentarios
CREATE TABLE IF NOT EXISTS comentarios (
    comentario_id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    estudiante_id INT NOT NULL,
    calificacion INT NOT NULL,
    comentario TEXT DEFAULT NULL,
    eliminado_por_admin BOOLEAN NOT NULL DEFAULT FALSE,
    causa_eliminacion TEXT DEFAULT NULL,
    moderado_por INT DEFAULT NULL,
    fecha_moderacion DATETIME DEFAULT NULL,
    fecha_comentario DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_comentario_curso_fecha (curso_id, fecha_comentario),
    KEY fk_comentario_estudiante (estudiante_id),
    KEY fk_comentario_moderador (moderado_por),
    CONSTRAINT fk_comentario_curso
        FOREIGN KEY (curso_id) REFERENCES cursos(curso_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_comentario_estudiante
        FOREIGN KEY (estudiante_id) REFERENCES usuarios(usuario_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_comentario_moderador
        FOREIGN KEY (moderado_por) REFERENCES usuarios(usuario_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_comentario_calificacion CHECK (calificacion BETWEEN 1 AND 10),
    CONSTRAINT chk_comentario_causa CHECK (
        eliminado_por_admin = 0 OR causa_eliminacion IS NOT NULL
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- diplomas
CREATE TABLE IF NOT EXISTS diplomas (
    diploma_id INT AUTO_INCREMENT PRIMARY KEY,
    inscripcion_id INT NOT NULL,
    codigo_verificacion VARCHAR(100) NOT NULL,
    fecha_emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_diploma_inscripcion (inscripcion_id),
    UNIQUE KEY uq_diploma_codigo (codigo_verificacion),
    CONSTRAINT fk_diploma_inscripcion
        FOREIGN KEY (inscripcion_id) REFERENCES inscripciones(inscripcion_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- recuperacion de contrasena
CREATE TABLE IF NOT EXISTS recuperacion_contrasena (
    recuperacion_id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token VARCHAR(100) NOT NULL,
    expira_en DATETIME NOT NULL,
    usado BOOLEAN NOT NULL DEFAULT FALSE,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_recuperacion_token (token),
    KEY fk_recuperacion_usuario (usuario_id),
    CONSTRAINT fk_recuperacion_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(usuario_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
