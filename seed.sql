-- Datos de demostracion para soscur_db.
-- Todos los usuarios usan la contrasena: Password1!
-- Recomendado ejecutar sobre una base limpia, despues de schema.sql.

USE soscur_db;

-- ---------------------------------------------------------------------------
-- usuarios
-- ---------------------------------------------------------------------------
INSERT INTO usuarios
    (usuario_id, nombre, correo, contrasena_hash, rol, genero, fecha_nacimiento,
     foto_perfil, correo_verificado, token_verificacion, intentos_fallidos,
     bloqueado, motivo_bloqueo, fecha_bloqueo, bloqueado_por, remember_token,
     remember_token_expira, fecha_registro)
VALUES
    (1, 'Admin SOSCUR', 'admin@soscur.test',
     '$2y$10$cXommjOC7E94ACDkK/Ja5Oj7Piys7XWgojPT4kE59XdSy6YzSGEn6',
     'admin', NULL, NULL, NULL, TRUE, NULL, 0, FALSE, NULL, NULL, NULL, NULL, NULL,
     '2026-08-01 09:00:00'),
    (2, 'Dra. Ana', 'ana@mail.com',
     '$2y$10$cXommjOC7E94ACDkK/Ja5Oj7Piys7XWgojPT4kE59XdSy6YzSGEn6',
     'instructor', 'femenino', '1988-03-14', NULL, TRUE, NULL, 0, FALSE, NULL,
     NULL, NULL, NULL, NULL, '2026-08-01 09:05:00'),
    (3, 'Ismael Cruz V.', 'ismael@mail.com',
     '$2y$10$cXommjOC7E94ACDkK/Ja5Oj7Piys7XWgojPT4kE59XdSy6YzSGEn6',
     'estudiante', 'masculino', '2001-07-22', NULL, TRUE, NULL, 0, FALSE, NULL,
     NULL, NULL, NULL, NULL, '2026-08-10 10:00:00'),
    (4, 'Maria Lopez', 'maria@mail.com',
     '$2y$10$cXommjOC7E94ACDkK/Ja5Oj7Piys7XWgojPT4kE59XdSy6YzSGEn6',
     'estudiante', 'femenino', '1999-11-05', NULL, TRUE, NULL, 0, FALSE, NULL,
     NULL, NULL, NULL, NULL, '2026-08-10 10:05:00'),
    (5, 'Pedro Ramirez', 'pedro@mail.com',
     '$2y$10$cXommjOC7E94ACDkK/Ja5Oj7Piys7XWgojPT4kE59XdSy6YzSGEn6',
     'estudiante', 'masculino', '2000-02-18', NULL, TRUE, NULL, 5, TRUE,
     'Comentario ofensivo', '2026-09-22 12:00:00', NULL, NULL, NULL,
     '2026-08-12 11:00:00'),
    (6, 'Juan Pablo Flores', 'juan@mail.com',
     '$2y$10$cXommjOC7E94ACDkK/Ja5Oj7Piys7XWgojPT4kE59XdSy6YzSGEn6',
     'instructor', 'masculino', '1997-05-30', NULL, TRUE, NULL, 0, FALSE, NULL,
     NULL, NULL, NULL, NULL, '2026-08-01 09:10:00')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    correo = VALUES(correo),
    contrasena_hash = VALUES(contrasena_hash),
    rol = VALUES(rol),
    genero = VALUES(genero),
    fecha_nacimiento = VALUES(fecha_nacimiento),
    foto_perfil = VALUES(foto_perfil),
    correo_verificado = VALUES(correo_verificado),
    token_verificacion = VALUES(token_verificacion),
    intentos_fallidos = VALUES(intentos_fallidos),
    bloqueado = VALUES(bloqueado),
    motivo_bloqueo = VALUES(motivo_bloqueo),
    fecha_bloqueo = VALUES(fecha_bloqueo),
    bloqueado_por = VALUES(bloqueado_por),
    remember_token = VALUES(remember_token),
    remember_token_expira = VALUES(remember_token_expira),
    fecha_registro = VALUES(fecha_registro);

UPDATE usuarios
SET bloqueado_por = 1
WHERE usuario_id = 5 AND bloqueado = TRUE;

-- ---------------------------------------------------------------------------
-- categorias
-- ---------------------------------------------------------------------------
INSERT INTO categorias (categoria_id, nombre, descripcion, creado_por)
VALUES
    (1, 'Animacion 2D', 'Cursos de animacion tradicional y digital.', 1),
    (2, 'Modelado 3D', 'Modelado, escultura y texturizado 3D.', 1),
    (3, 'Edicion de Video', 'Montaje y postproduccion audiovisual.', 1),
    (4, 'Produccion de Audio', 'Grabacion, mezcla y masterizacion.', 1),
    (5, 'Diseno Grafico', 'Composicion, color y tipografia.', 1),
    (6, 'Multimedia Web', 'Desarrollo web y experiencias interactivas.', 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    creado_por = VALUES(creado_por);

-- ---------------------------------------------------------------------------
-- cursos
-- ---------------------------------------------------------------------------
INSERT INTO cursos
    (curso_id, titulo, descripcion, imagen, precio, tipo_precio, instructor_id,
     categoria_id, estatus, fecha_creacion)
VALUES
    (1, 'Fundamentos de Animacion',
     'Aprende los principios basicos de la animacion y desarrolla tu primer proyecto.',
     NULL, 450.00, 'curso', 2, 1, 'activo', '2026-08-02 09:00:00'),
    (2, 'Modelado Low Poly',
     'Crea modelos 3D con tecnicas low poly para videojuegos y animacion.',
     NULL, 600.00, 'curso', 2, 2, 'activo', '2026-08-03 09:00:00'),
    (3, 'Edicion con Premiere',
     'Domina el flujo de trabajo de edicion y postproduccion con Adobe Premiere.',
     NULL, 350.00, 'curso', 2, 3, 'activo', '2026-08-04 09:00:00'),
    (4, 'Rigging de Personajes',
     'Prepara esqueletos y controles para animar personajes de forma profesional.',
     NULL, 700.00, 'curso', 2, 1, 'activo', '2026-08-05 09:00:00'),
    (5, 'Composicion Visual',
     'Aplica teoria del color, jerarquia y composicion en proyectos graficos.',
     NULL, 280.00, 'curso', 2, 5, 'inactivo', '2026-08-06 09:00:00'),
    (6, 'Mezcla y Sonido',
     'Aprende grabacion, mezcla y masterizacion para producciones multimedia.',
     NULL, 320.00, 'curso', 6, 4, 'activo', '2026-08-07 09:00:00'),
    (7, 'HTML y CSS desde cero',
     'Construye sitios web modernos con HTML semantico, CSS y diseno responsive.',
     NULL, 250.00, 'curso', 6, 6, 'activo', '2026-08-08 09:00:00'),
    (8, 'Motion Graphics',
     'Curso por niveles para crear graficos en movimiento y piezas audiovisuales.',
     NULL, 540.00, 'nivel', 6, 1, 'activo', '2026-08-09 09:00:00')
ON DUPLICATE KEY UPDATE
    titulo = VALUES(titulo),
    descripcion = VALUES(descripcion),
    imagen = VALUES(imagen),
    precio = VALUES(precio),
    tipo_precio = VALUES(tipo_precio),
    instructor_id = VALUES(instructor_id),
    categoria_id = VALUES(categoria_id),
    estatus = VALUES(estatus),
    fecha_creacion = VALUES(fecha_creacion);

-- ---------------------------------------------------------------------------
-- niveles
-- ---------------------------------------------------------------------------
INSERT INTO niveles
    (nivel_id, curso_id, titulo, video_url, orden, precio, material_url, material_tipo)
VALUES
    (1, 1, 'Introduccion a la animacion',
     'uploads/videos/fundamentos-animacion/nivel-1.mp4', 1, NULL,
     'uploads/materiales/fundamentos-animacion/nivel-1.pdf', 'pdf'),
    (2, 1, 'Principios del movimiento',
     'uploads/videos/fundamentos-animacion/nivel-2.mp4', 2, NULL, NULL, NULL),
    (3, 1, 'Timing y spacing',
     'uploads/videos/fundamentos-animacion/nivel-3.mp4', 3, NULL,
     'https://example.com/lecturas/timing-spacing', 'enlace'),
    (4, 1, 'Proyecto final',
     'uploads/videos/fundamentos-animacion/nivel-4.mp4', 4, NULL, NULL, NULL),

    (5, 2, 'Introduccion al modelado',
     'uploads/videos/modelado-low-poly/nivel-1.mp4', 1, NULL,
     'uploads/materiales/modelado-low-poly/nivel-1.pdf', 'pdf'),
    (6, 2, 'Herramientas basicas',
     'uploads/videos/modelado-low-poly/nivel-2.mp4', 2, NULL, NULL, NULL),
    (7, 2, 'Modelado de objetos',
     'uploads/videos/modelado-low-poly/nivel-3.mp4', 3, NULL,
     'https://example.com/lecturas/modelado-objetos', 'enlace'),
    (8, 2, 'Proyecto final',
     'uploads/videos/modelado-low-poly/nivel-4.mp4', 4, NULL, NULL, NULL),

    (9, 3, 'Interfaz y flujo de trabajo',
     'uploads/videos/edicion-premiere/nivel-1.mp4', 1, NULL, NULL, NULL),
    (10, 3, 'Corte y montaje',
     'uploads/videos/edicion-premiere/nivel-2.mp4', 2, NULL,
     'uploads/materiales/edicion-premiere/nivel-2.pdf', 'pdf'),
    (11, 3, 'Efectos y exportacion',
     'uploads/videos/edicion-premiere/nivel-3.mp4', 3, NULL, NULL, NULL),

    (12, 4, 'Introduccion al rigging',
     'uploads/videos/rigging-personajes/nivel-1.mp4', 1, NULL, NULL, NULL),
    (13, 4, 'Estructura osea',
     'uploads/videos/rigging-personajes/nivel-2.mp4', 2, NULL,
     'uploads/materiales/rigging-personajes/nivel-2.pdf', 'pdf'),
    (14, 4, 'Controles',
     'uploads/videos/rigging-personajes/nivel-3.mp4', 3, NULL, NULL, NULL),
    (15, 4, 'Pesos y deformacion',
     'uploads/videos/rigging-personajes/nivel-4.mp4', 4, NULL,
     'https://example.com/lecturas/pesos-deformacion', 'enlace'),
    (16, 4, 'Animacion de prueba',
     'uploads/videos/rigging-personajes/nivel-5.mp4', 5, NULL, NULL, NULL),
    (17, 4, 'Proyecto final',
     'uploads/videos/rigging-personajes/nivel-6.mp4', 6, NULL, NULL, NULL),

    (18, 5, 'Teoria del color',
     'uploads/videos/composicion-visual/nivel-1.mp4', 1, NULL, NULL, NULL),
    (19, 5, 'Jerarquia visual',
     'uploads/videos/composicion-visual/nivel-2.mp4', 2, NULL,
     'uploads/materiales/composicion-visual/nivel-2.pdf', 'pdf'),
    (20, 5, 'Proyecto de composicion',
     'uploads/videos/composicion-visual/nivel-3.mp4', 3, NULL, NULL, NULL),

    (21, 6, 'Fundamentos de audio',
     'uploads/videos/mezcla-sonido/nivel-1.mp4', 1, NULL, NULL, NULL),
    (22, 6, 'Mezcla',
     'uploads/videos/mezcla-sonido/nivel-2.mp4', 2, NULL,
     'https://example.com/lecturas/mezcla-audio', 'enlace'),
    (23, 6, 'Masterizacion',
     'uploads/videos/mezcla-sonido/nivel-3.mp4', 3, NULL, NULL, NULL),

    (24, 7, 'HTML semantico',
     'uploads/videos/html-css/nivel-1.mp4', 1, NULL, NULL, NULL),
    (25, 7, 'CSS basico',
     'uploads/videos/html-css/nivel-2.mp4', 2, NULL,
     'uploads/materiales/html-css/nivel-2.pdf', 'pdf'),
    (26, 7, 'Layouts',
     'uploads/videos/html-css/nivel-3.mp4', 3, NULL, NULL, NULL),
    (27, 7, 'Proyecto web',
     'uploads/videos/html-css/nivel-4.mp4', 4, NULL, NULL, NULL),

    (28, 8, 'Introduccion a Motion Graphics',
     'uploads/videos/motion-graphics/nivel-1.mp4', 1, 180.00, NULL, NULL),
    (29, 8, 'Animacion de graficos',
     'uploads/videos/motion-graphics/nivel-2.mp4', 2, 180.00,
     'https://example.com/lecturas/motion-graphics', 'enlace'),
    (30, 8, 'Proyecto final',
     'uploads/videos/motion-graphics/nivel-3.mp4', 3, 180.00, NULL, NULL)
ON DUPLICATE KEY UPDATE
    curso_id = VALUES(curso_id),
    titulo = VALUES(titulo),
    video_url = VALUES(video_url),
    orden = VALUES(orden),
    precio = VALUES(precio),
    material_url = VALUES(material_url),
    material_tipo = VALUES(material_tipo);

-- ---------------------------------------------------------------------------
-- inscripciones
-- ---------------------------------------------------------------------------
INSERT INTO inscripciones
    (inscripcion_id, estudiante_id, curso_id, monto_pagado, estado_pago,
     fecha_pago, referencia_pago, progreso, completado, fecha_inscripcion,
     fecha_completado)
VALUES
    (1, 3, 2, 600.00, 'pagado', '2026-09-02 10:00:00', 'PAY-2026-0001', 50,
     FALSE, '2026-09-02 10:00:00', NULL),
    (2, 3, 3, 350.00, 'pagado', '2026-08-20 10:00:00', 'PAY-2026-0002', 100,
     TRUE, '2026-08-20 10:00:00', '2026-09-10 18:00:00'),
    (3, 3, 7, 250.00, 'pagado', '2026-09-10 10:00:00', 'PAY-2026-0003', 25,
     FALSE, '2026-09-10 10:00:00', NULL),
    (4, 4, 1, 450.00, 'pagado', '2026-08-28 10:00:00', 'PAY-2026-0004', 100,
     TRUE, '2026-08-28 10:00:00', '2026-09-15 18:00:00'),
    (5, 5, 4, 0.00, 'pendiente', NULL, NULL, 0, FALSE,
     '2026-09-15 10:00:00', NULL)
ON DUPLICATE KEY UPDATE
    estudiante_id = VALUES(estudiante_id),
    curso_id = VALUES(curso_id),
    monto_pagado = VALUES(monto_pagado),
    estado_pago = VALUES(estado_pago),
    fecha_pago = VALUES(fecha_pago),
    referencia_pago = VALUES(referencia_pago),
    progreso = VALUES(progreso),
    completado = VALUES(completado),
    fecha_inscripcion = VALUES(fecha_inscripcion),
    fecha_completado = VALUES(fecha_completado);

-- ---------------------------------------------------------------------------
-- progreso_niveles
-- ---------------------------------------------------------------------------
INSERT INTO progreso_niveles
    (progreso_id, inscripcion_id, nivel_id, completado, fecha_completado)
VALUES
    (1, 1, 5, TRUE, '2026-09-05 10:00:00'),
    (2, 1, 6, TRUE, '2026-09-12 10:00:00'),
    (3, 2, 9, TRUE, '2026-08-25 10:00:00'),
    (4, 2, 10, TRUE, '2026-09-01 10:00:00'),
    (5, 2, 11, TRUE, '2026-09-10 18:00:00'),
    (6, 3, 24, TRUE, '2026-09-12 10:00:00'),
    (7, 4, 1, TRUE, '2026-09-01 10:00:00'),
    (8, 4, 2, TRUE, '2026-09-05 10:00:00'),
    (9, 4, 3, TRUE, '2026-09-10 10:00:00'),
    (10, 4, 4, TRUE, '2026-09-15 18:00:00')
ON DUPLICATE KEY UPDATE
    inscripcion_id = VALUES(inscripcion_id),
    nivel_id = VALUES(nivel_id),
    completado = VALUES(completado),
    fecha_completado = VALUES(fecha_completado);

-- ---------------------------------------------------------------------------
-- comentarios
-- ---------------------------------------------------------------------------
INSERT INTO comentarios
    (comentario_id, curso_id, estudiante_id, calificacion, comentario,
     eliminado_por_admin, causa_eliminacion, moderado_por, fecha_moderacion,
     fecha_comentario)
VALUES
    (1, 1, 4, 9, 'Muy buen curso, explica todo paso a paso.', FALSE, NULL, NULL,
     NULL, '2026-09-16 12:00:00'),
    (2, 4, 5, 2, 'No se entiende nada, pesimo.', FALSE, NULL, NULL, NULL,
     '2026-09-17 12:00:00'),
    (3, 2, 3, 8, 'Buen curso de modelado.', FALSE, NULL, NULL, NULL,
     '2026-09-19 12:00:00'),
    (4, 3, 3, 10, 'Muy completo y practico.', FALSE, NULL, NULL, NULL,
     '2026-09-20 12:00:00'),
    (5, 4, 5, 1, 'Comentario eliminado por lenguaje ofensivo.', TRUE,
     'Lenguaje ofensivo', 1, '2026-09-22 12:00:00', '2026-09-18 12:00:00')
ON DUPLICATE KEY UPDATE
    curso_id = VALUES(curso_id),
    estudiante_id = VALUES(estudiante_id),
    calificacion = VALUES(calificacion),
    comentario = VALUES(comentario),
    eliminado_por_admin = VALUES(eliminado_por_admin),
    causa_eliminacion = VALUES(causa_eliminacion),
    moderado_por = VALUES(moderado_por),
    fecha_moderacion = VALUES(fecha_moderacion),
    fecha_comentario = VALUES(fecha_comentario);

-- ---------------------------------------------------------------------------
-- diplomas
-- ---------------------------------------------------------------------------
INSERT INTO diplomas
    (diploma_id, inscripcion_id, codigo_verificacion, fecha_emision)
VALUES
    (1, 2, 'SOSCUR-2026-0001', '2026-09-10 18:05:00'),
    (2, 4, 'SOSCUR-2026-0002', '2026-09-15 18:05:00')
ON DUPLICATE KEY UPDATE
    inscripcion_id = VALUES(inscripcion_id),
    codigo_verificacion = VALUES(codigo_verificacion),
    fecha_emision = VALUES(fecha_emision);
