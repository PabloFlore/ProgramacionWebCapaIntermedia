CREATE DATABASE IF NOT EXISTS soscur_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE soscur_db;

-- 1. usuarios (Diapositiva 10)
CREATE TABLE IF NOT EXISTS usuarios (
    usuario_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    contrasena_hash VARCHAR(255) NOT NULL,
    rol ENUM('estudiante', 'instructor', 'admin') NOT NULL DEFAULT 'estudiante',
    intentos_fallidos TINYINT NOT NULL DEFAULT 0,
    bloqueado BOOLEAN NOT NULL DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. categorias (Diapositiva 14)
CREATE TABLE IF NOT EXISTS categorias (
    categoria_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    creado_por INT,
    FOREIGN KEY (creado_por) REFERENCES usuarios(usuario_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. cursos (Diapositiva 11)
CREATE TABLE IF NOT EXISTS cursos (
    curso_id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    instructor_id INT NOT NULL,
    categoria_id INT NOT NULL,
    estatus ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    FOREIGN KEY (instructor_id) REFERENCES usuarios(usuario_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(categoria_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. niveles (Diapositiva 12 - Módulos del curso)
CREATE TABLE IF NOT EXISTS niveles (
    nivel_id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    video_url VARCHAR(255) NOT NULL,
    FOREIGN KEY (curso_id) REFERENCES cursos(curso_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. inscripciones (Diapositiva 13)
CREATE TABLE IF NOT EXISTS inscripciones (
    inscripcion_id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    curso_id INT NOT NULL,
    monto_pagado DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    completado BOOLEAN NOT NULL DEFAULT FALSE,
    UNIQUE KEY uq_estudiante_curso (estudiante_id, curso_id),
    FOREIGN KEY (estudiante_id) REFERENCES usuarios(usuario_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(curso_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. comentarios (Diapositiva 15)
CREATE TABLE IF NOT EXISTS comentarios (
    comentario_id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    estudiante_id INT NOT NULL,
    calificacion INT NOT NULL CHECK (calificacion BETWEEN 1 AND 10),
    comentario TEXT,
    eliminado_por_admin BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_comentario DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(curso_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (estudiante_id) REFERENCES usuarios(usuario_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. diplomas (Diapositiva 16)
CREATE TABLE IF NOT EXISTS diplomas (
    diploma_id INT AUTO_INCREMENT PRIMARY KEY,
    inscripcion_id INT NOT NULL UNIQUE,
    codigo_verificacion VARCHAR(100) NOT NULL UNIQUE,
    fecha_emision DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inscripcion_id) REFERENCES inscripciones(inscripcion_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;