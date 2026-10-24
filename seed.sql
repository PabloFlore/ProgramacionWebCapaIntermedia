USE soscur_db;

-- Contraseña demo para todas las cuentas: Password1!
-- Hash generado con password_hash('Password1!', PASSWORD_DEFAULT)
INSERT INTO usuarios (
    usuario_id, nombre, correo, contrasena_hash, rol, genero, fecha_nacimiento,
    correo_verificado, intentos_fallidos, bloqueado, motivo_bloqueo, fecha_bloqueo,
    bloqueado_por, fecha_registro
) VALUES
    (1, 'Admin SOSCUR', 'admin@soscur.test', '$2y$10$J3pTCyHTPga4tNyekmHiB.PLgJJ8sR5kk8vR7UYlbVCWRqPcuueEK', 'admin', NULL, NULL, 1, 0, 0, NULL, NULL, NULL, '2026-08-01 09:00:00'),
    (2, 'Dra. Ana', 'ana@mail.com', '$2y$10$J3pTCyHTPga4tNyekmHiB.PLgJJ8sR5kk8vR7UYlbVCWRqPcuueEK', 'instructor', 'femenino', '1988-03-14', 1, 0, 0, NULL, NULL, NULL, '2026-08-01 09:05:00'),
    (3, 'Ismael Cruz V.', 'ismael@mail.com', '$2y$10$J3pTCyHTPga4tNyekmHiB.PLgJJ8sR5kk8vR7UYlbVCWRqPcuueEK', 'estudiante', 'masculino', '2001-07-22', 1, 0, 0, NULL, NULL, NULL, '2026-08-10 10:00:00'),
    (4, 'Maria Lopez', 'maria@mail.com', '$2y$10$J3pTCyHTPga4tNyekmHiB.PLgJJ8sR5kk8vR7UYlbVCWRqPcuueEK', 'estudiante', 'femenino', '1999-11-05', 1, 0, 0, NULL, NULL, NULL, '2026-08-10 10:05:00'),
    (5, 'Pedro Ramirez', 'pedro@mail.com', '$2y$10$J3pTCyHTPga4tNyekmHiB.PLgJJ8sR5kk8vR7UYlbVCWRqPcuueEK', 'estudiante', 'masculino', '2000-02-18', 1, 5, 1, 'Comentario ofensivo', '2026-09-22 12:00:00', NULL, '2026-08-12 11:00:00'),
    (6, 'Juan Pablo Flores', 'juan@mail.com', '$2y$10$J3pTCyHTPga4tNyekmHiB.PLgJJ8sR5kk8vR7UYlbVCWRqPcuueEK', 'instructor', 'masculino', '1997-05-30', 1, 0, 0, NULL, NULL, NULL, '2026-08-01 09:10:00')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    contrasena_hash = VALUES(contrasena_hash),
    rol = VALUES(rol),
    genero = VALUES(genero),
    fecha_nacimiento = VALUES(fecha_nacimiento),
    correo_verificado = VALUES(correo_verificado),
    intentos_fallidos = VALUES(intentos_fallidos),
    bloqueado = VALUES(bloqueado),
    motivo_bloqueo = VALUES(motivo_bloqueo),
    fecha_bloqueo = VALUES(fecha_bloqueo);

UPDATE usuarios SET bloqueado_por = 1 WHERE usuario_id = 5;

INSERT INTO categorias (categoria_id, nombre, descripcion, creado_por) VALUES
    (1, 'Animacion 2D', 'Principios, animacion digital y produccion.', 2),
    (2, 'Modelado 3D', 'Modelado, texturizado y render.', 2),
    (3, 'Edicion de Video', 'Edicion, postproduccion y color.', 2),
    (4, 'Produccion de Audio', 'Grabacion, mezcla y diseno sonoro.', 6),
    (5, 'Diseno Grafico', 'Composicion visual y diseno digital.', 2),
    (6, 'Multimedia Web', 'Desarrollo web, HTML, CSS y JavaScript.', 6)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    creado_por = VALUES(creado_por);

INSERT INTO cursos (
    curso_id, titulo, descripcion, imagen, precio, tipo_precio, instructor_id,
    categoria_id, estatus, fecha_creacion
) VALUES
    (1, 'Fundamentos de Animacion', 'Aprende los principios de la animacion y su aplicacion.', NULL, 450.00, 'curso', 2, 1, 'activo', '2026-08-15 09:00:00'),
    (2, 'Modelado Low Poly', 'Crea personajes y escenarios con geometria low poly.', NULL, 600.00, 'curso', 2, 2, 'activo', '2026-08-16 09:00:00'),
    (3, 'Edicion con Premiere', 'Domina el flujo de trabajo de edicion y postproduccion.', NULL, 350.00, 'curso', 2, 3, 'activo', '2026-08-04 09:00:00'),
    (4, 'Rigging de Personajes', 'Prepara esqueletos y controles para animacion.', NULL, 700.00, 'curso', 2, 1, 'activo', '2026-08-18 09:00:00'),
    (5, 'Composicion Visual', 'Fundamentos de composicion y teoria del color.', NULL, 280.00, 'curso', 2, 5, 'inactivo', '2026-08-20 09:00:00'),
    (6, 'Mezcla y Sonido', 'Mezcla profesional y diseno sonoro para multimedia.', NULL, 500.00, 'curso', 6, 4, 'activo', '2026-08-21 09:00:00'),
    (7, 'HTML y CSS desde cero', 'Construye sitios web modernos con HTML y CSS.', NULL, 400.00, 'curso', 6, 6, 'activo', '2026-08-22 09:00:00')
ON DUPLICATE KEY UPDATE
    titulo = VALUES(titulo),
    descripcion = VALUES(descripcion),
    precio = VALUES(precio),
    tipo_precio = VALUES(tipo_precio),
    instructor_id = VALUES(instructor_id),
    categoria_id = VALUES(categoria_id),
    estatus = VALUES(estatus);

INSERT INTO niveles (nivel_id, curso_id, titulo, video_url, orden, precio, material_url, material_tipo) VALUES
    (1, 1, 'Introduccion a la animacion', 'https://example.com/videos/animacion-1.mp4', 1, 150.00, 'https://example.com/materiales/animacion-1.pdf', 'pdf'),
    (2, 1, 'Principios del movimiento', 'https://example.com/videos/animacion-2.mp4', 2, 150.00, NULL, NULL),
    (3, 2, 'Modelado base', 'https://example.com/videos/modelado-1.mp4', 1, 200.00, NULL, NULL),
    (4, 2, 'Detalles y texturizado', 'https://example.com/videos/modelado-2.mp4', 2, 200.00, 'https://example.com/materiales/modelado-2.pdf', 'pdf'),
    (5, 3, 'Interfaz y flujo de trabajo', 'https://example.com/videos/premiere-1.mp4', 1, 175.00, NULL, NULL),
    (6, 3, 'Corte y color', 'https://example.com/videos/premiere-2.mp4', 2, 175.00, NULL, NULL),
    (7, 4, 'Esqueletos y huesos', 'https://example.com/videos/rigging-1.mp4', 1, 350.00, NULL, NULL),
    (8, 4, 'Controles y pruebas', 'https://example.com/videos/rigging-2.mp4', 2, 350.00, NULL, NULL),
    (9, 5, 'Composicion y jerarquia', 'https://example.com/videos/composicion-1.mp4', 1, 140.00, NULL, NULL),
    (10, 6, 'Grabacion y edicion de audio', 'https://example.com/videos/audio-1.mp4', 1, 250.00, 'https://example.com/materiales/audio-1.pdf', 'pdf'),
    (11, 6, 'Mezcla final', 'https://example.com/videos/audio-2.mp4', 2, 250.00, NULL, NULL),
    (12, 7, 'Estructura HTML', 'https://example.com/videos/html-1.mp4', 1, 200.00, NULL, NULL),
    (13, 7, 'Estilos CSS', 'https://example.com/videos/css-2.mp4', 2, 200.00, 'https://example.com/materiales/css-2.pdf', 'pdf')
ON DUPLICATE KEY UPDATE
    titulo = VALUES(titulo),
    video_url = VALUES(video_url),
    precio = VALUES(precio),
    material_url = VALUES(material_url),
    material_tipo = VALUES(material_tipo);

INSERT INTO inscripciones (
    inscripcion_id, estudiante_id, curso_id, monto_pagado, estado_pago,
    fecha_pago, referencia_pago, progreso, completado, fecha_inscripcion, fecha_completado
) VALUES
    (1, 3, 2, 600.00, 'pagado', '2026-09-02 10:00:00', 'REF-001', 65, 0, '2026-09-02 10:00:00', NULL),
    (2, 4, 1, 450.00, 'pagado', '2026-08-28 10:00:00', 'REF-002', 100, 1, '2026-08-28 10:00:00', '2026-09-20 10:00:00'),
    (3, 5, 3, 350.00, 'pendiente', NULL, NULL, 15, 0, '2026-09-15 10:00:00', NULL)
ON DUPLICATE KEY UPDATE
    monto_pagado = VALUES(monto_pagado),
    estado_pago = VALUES(estado_pago),
    progreso = VALUES(progreso),
    completado = VALUES(completado);

INSERT INTO comentarios (
    comentario_id, curso_id, estudiante_id, calificacion, comentario,
    eliminado_por_admin, fecha_comentario
) VALUES
    (1, 1, 4, 9, 'Muy buen curso, explica todo paso a paso.', 0, '2026-09-21 10:00:00'),
    (2, 2, 3, 8, 'Buen contenido y ejercicios practicos.', 0, '2026-09-22 10:00:00')
ON DUPLICATE KEY UPDATE
    calificacion = VALUES(calificacion),
    comentario = VALUES(comentario);

INSERT INTO diplomas (diploma_id, inscripcion_id, codigo_verificacion, fecha_emision) VALUES
    (1, 2, 'SOSCUR-DEMO-0001', '2026-09-20 10:05:00')
ON DUPLICATE KEY UPDATE
    codigo_verificacion = VALUES(codigo_verificacion),
    fecha_emision = VALUES(fecha_emision);
