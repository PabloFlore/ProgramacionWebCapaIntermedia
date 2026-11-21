# Estado de avance del proyecto

Este documento acompaña a `README.md` para que cualquier persona del equipo entienda rápido **qué está implementado, qué se está haciendo y por qué se tomaron ciertas decisiones**. Se actualiza al terminar cada fase.

## Contexto

SOSCUR Portal es un MVC PHP 8.2 sin framework, con MySQL y un front controller en `public/index.php`. La API propia vive bajo `/api/v1` y es consumida por el navegador mediante `fetch` (`public/js/api.js`). La autenticación usa sesiones, CSRF en formularios web y en peticiones API del navegador, `password_hash()` y control de accesos centralizado con política **denegar por defecto** en `app/config/access.php`.

La base de datos ya modela cursos, niveles, inscripciones, avance, comentarios, diplomas y mensajería (`schema.sql`), y `seed.sql` contiene datos de demostración. Toda esa funcionalidad tiene código ejecutable; las maquetas de `docs/prototipos/` se conservan como referencia visual, pero **no son servidas por el router**.

## Rúbricas trabajadas

| Rúbrica | Puntaje | Situación actual |
| --- | --- | --- |
| Portal en operación | 16 | Completo: catálogo, detalle, niveles, inscripción, avance, baja lógica, reportes, categorías, comentarios, mensajería y logout vía `fetch`. |
| API propia integrada | 12 | Implementada: flujos reales con `fetch` y colección Postman (carpetas 15 a 19). |
| Reportes con filtros | 10 | Implementados: reporte general admin y de ventas por instructor, con filtros y formatos. |
| Inyección de dependencias | 10 | Completa: contenedor, servicios inyectados, modelos con PDO y `Controller::model()` eliminado. |
| Módulo de administrador | 8 | Implementado: bloqueo/reactivación, gestión de cursos, categorías y moderación de comentarios. |
| Interacción y búsqueda | 8 | Implementada: búsqueda global por título/descripción/categoría, comentarios con promedio y mensajería interna. |
| Pagos y certificado | 8 | Implementados: compra simulada, confirmación, avance por nivel, diploma automático y verificación por código. |
| Seguridad | 8 | Completa: política de acceso denegar por defecto, CORS, CSRF condicional en API y obligatorio en formularios web, validación de entradas, URL seguras y logout vía `fetch` con fallback web. |
| Mensajes y avisos | 6 | Completos: flash de éxito, avisos con `SoscurApi` y confirmación antes de logout, bajas, eliminación de niveles y moderación. |

## Decisiones técnicas acordadas

- **Inyección de dependencias:** contenedor propio mínimo (`App\Core\Container`) con autowire y bindings en `app/config/bindings.php`.
- **Pagos:** simulación local con dos métodos (`tarjeta` y `transferencia`); no se usa pasarela externa ni se guardan credenciales en el repo.
- **Certificado:** vista HTML imprimible con código de verificación; no se añade Composer.
- **Baja lógica:** se usa `cursos.estatus` (`activo`/`inactivo`); no se borran filas porque las FK usan `ON DELETE CASCADE`.
- **Formatos:** fechas `dd/mm/yyyy` y moneda MXN con `$` y dos decimales. PHP no tiene `intl`, así que se usa `number_format()` en servidor; el navegador conserva `Intl.NumberFormat`.
- **CSRF:** en API se exige token solo cuando la petición parece del navegador (`Sec-Fetch-Site`/`Origin`); en formularios web se valida siempre el token. Postman y curl siguen funcionando sin token en la API.
- **Acceso:** `app/config/access.php` es denegar por defecto; una ruta nueva debe agregarse explícitamente con roles o con la marca `'public'`.
- **Commits:** uno por fase; Fase 5 se entrega en un único commit con todos los ajustes de auditoría.

## Plan por fases

| Fase | Contenido | Estado |
| --- | --- | --- |
| 0 | Contenedor DI, interfaces, PDO inyectado, `Request`, CSRF API, confirmaciones y este documento | Completada |
| 1 | Cursos/niveles completos, inscripción, pagos simulados y diploma automático | Completada |
| 2 | Reportes con filtros y formatos | Completada |
| 3 | CRUD de categorías, moderación de comentarios, comentarios/promedio, mensajería y búsqueda ampliada | Completada |
| 4 | Canje de token, tabla admin y logout vía `fetch` | Completada |
| 5 | Auditoría final: DI estricta, denegar por defecto, seguridad, Kardex, edición de curso/nivel, pestañas de reportes y avisos | Completada |

## Registro de avances

### Fase 5 — Auditoría final y cierre de brechas

- Inyección de dependencias estricta: contratos para repositorios, servicios y middleware; `bindings.php` registra `PDO`, repositorios, servicios y fábricas. Modelos y servicios ya no crean conexiones propias y `Auth`/`CourseService` fallan explícitamente si falta una dependencia.
- `Router` resuelve middleware y controladores desde el contenedor. `access.php` pasó a **denegar por defecto**: cada ruta declara roles o la marca `'public'`, y una política ausente se registra en `error_log` antes de denegar.
- Seguridad: CORS incluye `DELETE` y `X-CSRF-Token`; `CsrfWebMiddleware` protege formularios web; se validan URL de video/material, longitud de `imagen` y código de diploma, límite de 1 MiB en JSON, roles en vistas sensibles, courses activos en mensajería y vista `errors/404.php` para cursos inactivos.
- Portal: `public/js/course-gestion.js` permite editar curso y nivel con `GET /cursos/gestion/{id}` y `PATCH`; el detalle público muestra precios en MXN, enlace de material y leyenda de comentarios moderados.
- Kardex nuevo: rutas `/kardex` y `/api/v1/kardex`, `KardexService`, vista con resumen, progreso y columna `Completado` en `dd/mm/yyyy`.
- Reportes: la vista admin usa pestañas «Ventas por curso» y «Desglose por alumno» en la misma ruta; `tab` se conserva al aplicar filtros y al paginar.
- Avisos: confirmación antes de cerrar sesión y en acciones destructivas; flash de éxito para categorías, comentarios, mensajes y logout; `showAlert()` tras publicar un comentario.
- Colección Postman ampliada con la carpeta 19 (5 peticiones): kardex, detalle de gestión, reportes con `tab`, preflight CORS y CSRF de API con `Origin`.
- Verificación: 121 archivos PHP con `php -l`, 17 scripts con `node --check`, carga de clases `OK`, sustitución DI `OK`, JSON de Postman válido (19 carpetas; 5 peticiones en la Fase 5), `git diff --check` sin errores, migraciones 002/003/004 en BD temporal y smoke HTTP 31/31: API base, login admin, filtro/orden/paginación, metadatos, sin coincidencias, parámetros inválidos, vista admin, login sin verificar, dashboard de canje, canje exitoso, aviso oculto, token ya usado, login de estudiante demo, Kardex API y web, detalle de gestión, reportes con pestañas, leyenda de moderación, detalle público con MXN y leyenda, CSRF web sin token, logout con `Origin` sin CSRF, sesión activa tras `403`, token CSRF web, logout web fallback, logout API, sesión cerrada, logout sin sesión y log PHP limpio.

### Fase 4 — Logout fetch, canje de token y tabla admin

- Se eliminó el método muerto `Controller::model()`; no existían llamadas y los controladores ya reciben servicios por constructor desde el contenedor.
- Logout vía `fetch`: `public/js/logout.js` intercepta el formulario del header y consume `POST /api/v1/logout` con `X-CSRF-Token`, redirige a `/login` y conserva `POST /logout` como fallback sin JavaScript. La prueba e2e ahora comprueba que no se envía el formulario web.
- Canje de token: el dashboard muestra un formulario para pegar el token de verificación de sandbox y consume `POST /api/v1/verificar-correo`; al tener éxito oculta el aviso. El botón de reenvío muestra el token de sandbox y el enlace `GET /verificar-correo?token=...` sigue funcionando como alternativa.
- Tabla admin: `GET /api/v1/admin/usuarios` acepta `q`, `orden`, `direccion`, `pagina` y `limite`, valida los valores con `400` y responde `data`, `total`, `pagina`, `limite` y `paginas`. El orden se restringe a columnas permitidas para evitar inyección SQL.
- La vista `admin/usuarios` conserva el HTML inicial como fallback y usa `public/js/admin-usuarios.js` para buscar con debounce, ordenar, paginar, bloquear y reactivar mediante event delegation sin recargar. Las fechas se muestran en `dd/mm/yyyy`.
- Colección Postman ampliada con la carpeta 18 (7 peticiones) y declaración de la variable `verifyToken`; README actualizado con los nuevos parámetros y la respuesta paginada.
- Verificación: 85 archivos PHP con `php -l`, 18 scripts con `node --check`, JSON de Postman válido (18 carpetas; 7 peticiones en la Fase 4), `git diff --check` sin errores (solo warnings LF→CRLF), migraciones 002/003/004 aplicadas en BD temporal y smoke HTTP 23/23: API base, login admin, filtro/orden/paginación, metadatos, sin coincidencias, parámetros inválidos (`orden`, `limite`, `pagina`), vista web, login sin verificar, formulario de canje, canje exitoso, aviso oculto, token ya usado, logout con `Origin` sin CSRF (`403`), sesión activa tras `403`, token CSRF web, logout web fallback, logout API, sesión cerrada, logout sin sesión y log PHP limpio.

### Fase 3 — Categorías, comentarios, mensajería y búsqueda

- CRUD de categorías para admin: `GET /admin/categorias`, `GET /api/v1/categorias/gestion`, `POST /api/v1/categorias`, `PATCH /api/v1/categorias/{id}` y `DELETE /api/v1/categorias/{id}`, con nombre único, `creado_por` y bloqueo de baja cuando existen cursos asociados.
- Comentarios completos: `GET/POST /api/v1/cursos/{id}/comentarios`, calificación entera de 1 a 10, un comentario por curso y solo para estudiantes con inscripción completada; `Curso` devuelve `calificacion_promedio` y `total_comentarios` en catálogo y detalle.
- Moderación: `GET /api/v1/admin/comentarios`, `POST /api/v1/admin/comentarios/{id}/eliminar` y vista `admin/comentarios` con filtros, causa obligatoria y leyenda «Eliminado por administración» cuando `eliminado_por_admin = TRUE`.
- Mensajería interna: `migrations/004_mensajeria.sql`, modelo `Mensaje`, `MensajeService`, endpoints `/api/v1/mensajes` y vista `mensajes/index` con bandejas recibidos/enviados, destinatarios según rol y marcar como leído.
- Búsqueda ampliada: `GET /api/v1/cursos?categoria_id=todas&q=...` busca por título, descripción y categoría; el catálogo incluye «Todas las categorías» y búsqueda `q` con debounce.
- Nuevos scripts `admin-categorias.js`, `comentarios.js`, `admin-comentarios.js` y `mensajes.js`, todos usando `SoscurApi.request()` y confirmaciones antes de acciones destructivas.
- Colección Postman ampliada con la carpeta 17 (34 peticiones) y variables `comentarioId`, `mensajeId` y `categoriaGestionId`.
- `README.md` y este documento actualizados con rutas web/API, JS, permisos y migraciones de Fase 3.
- Verificación: 85 archivos PHP con `php -l`, 16 scripts con `node --check`, JSON de Postman válido (17 carpetas; 34 peticiones en la Fase 3), `git diff --check` sin errores, migraciones 002/003/004 aplicadas en BD temporal y smoke HTTP 43/43: `BUSQUEDA_GLOBAL`, `BUSQUEDA_TODAS_CATEGORIAS`, `CATEGORIA_INVALIDA`, `COMENTARIOS_CURSO_1`, `PROMEDIO_SEED`, `LOGIN_ESTUDIANTE`, `COMENTARIO_CALIFICACION_INVALIDA`, `COMENTARIO_SIN_COMPLETAR`, `COMENTARIO_DUPLICADO`, `MODERACION_ESTUDIANTE_403`, `LOGIN_INSTRUCTOR`, `MODERACION_INSTRUCTOR_403`, `LOGIN_ADMIN`, `MODERACION_ADMIN`, `MODERACION_LEYENDA`, `ELIMINAR_COMENTARIO`, `PROMEDIO_RECALCULADO`, `ADMIN_COMMENTS_WEB`, `WEB_LEYENDA_MODERACION`, CRUD de categorías con `409` por duplicado/cursos asociados, `MENSAJES_SIN_SESION`, `DESTINATARIOS_ROLES`, `CREAR_MENSAJE`, `MENSAJE_A_ESTUDIANTE_403`, bandejas API/web, `MARCAR_LEIDO` y las vistas web en `200`. Log PHP sin errores.

### Fase 2 — Reportes con filtros y formatos

- Nueva ruta web `GET /reportes` para instructor/admin y endpoint `GET /api/v1/reportes` con el mismo alcance por rol; ambos registrados en `public/index.php` y `app/config/access.php`.
- Nuevos `Reporte` y `ReporteService`: consultas preparadas sobre `inscripciones`, `cursos`, `categorias`, `usuarios` y `metodos_pago`, sin migraciones nuevas ni dependencias externas.
- Filtros soportados: `desde`, `hasta`, `curso_id`, `categoria_id`, `estado_pago`, `rol`, `bloqueado`, `pagina` y `limite` (alias `limit` en la API), con validación y `400` cuando no son válidos.
- El admin obtiene el reporte general de todo el portal; el instructor ve únicamente sus cursos, tanto en resúmenes como en el desglose.
- Vista `app/views/reporte/index.php` con KPIs, usuarios por rol, ventas por curso y desglose por alumno. Fechas `dd/mm/yyyy` e importes MXN con `$` y dos decimales; `public/js/reportes.js` valida el rango de fechas y ofrece limpiar filtros.
- Colección Postman ampliada con la carpeta 16 (10 peticiones): sin sesión, rol insuficiente, instructor filtrado, filtro inválido, administrador general y filtrado.
- Verificación: `php -l`, `node --check`, JSON de Postman válido, `git diff --check` sin errores y smoke HTTP sobre BD temporal con 21/21 verificaciones: `LOGOUT_INICIAL=401`, `REPORTE_SIN_SESION=401`, `LOGIN_ESTUDIANTE=200`, `REPORTE_ESTUDIANTE_403=403`, `LOGIN_INSTRUCTOR=200`, `REPORTE_INSTRUCTOR=200`, `INSTRUCTOR_ESTRUCTURA=True`, `INSTRUCTOR_FILTROS=pagado|3`, `INSTRUCTOR_ALCANCE_CURSOS=True`, `REPORTE_FILTRO_INVALIDO=400`, `LOGIN_ADMIN=200`, `REPORTE_GENERAL_ADMIN=200`, `ADMIN_POR_ROL=True`, `REPORTE_ADMIN_FILTRADO=200`, `ADMIN_FILTROS_NORMALIZADOS=estudiante|0|25`, `REPORTE_WEB=200`, formatos de fecha y moneda presentes y log PHP limpio.

### Fase 1 — Cursos navegables, pagos y certificados

- `migrations/003_cursos_pagos_diplomas.sql` agrega métodos de pago, órdenes, detalle de orden, pagos y columnas de pago en inscripciones.
- Nuevos modelos `Nivel`, `Inscripcion`, `ProgresoNivel`, `Diploma`, `MetodoPago`, `Orden` y `Pago`; `Curso` ahora soporta detalle, listado de gestión, actualización y baja lógica.
- Nuevos servicios `CourseService`, `PagoService`, `DiplomaService` e `InscripcionService`, inyectados por constructor con PDO compartido y transacciones para compra, confirmación y avance.
- API nueva: detalle/gestión/edición/baja de cursos, CRUD de niveles, métodos de pago, compra, confirmación de pago, completar nivel, diploma por inscripción y verificación por código.
- Vistas nuevas: detalle de curso con checkout, gestión de cursos/niveles, mis cursos, certificado imprimible y verificación pública. El catálogo ahora enlaza al detalle.
- Diálogos de confirmación reutilizados para baja/reactivación de cursos y eliminación de niveles con progreso.
- Colección Postman ampliada con la carpeta 15 (18 peticiones) y variables `cursoId`, `nivelId`, `metodoPagoId`, `inscripcionId`, `pagoId` y `diplomaCodigo`.
- Verificación: 67 archivos PHP con `php -l`, 11 scripts con `node --check`, JSON de Postman válido, `git diff --check` sin errores, migración 003 idempotente y smoke HTTP sobre BD temporal: `LOGIN_INSTRUCTOR=200`, `CREAR_CURSO=201`, `CREAR_NIVEL=201`, `GESTION_CURSOS=200`, `DETALLE_CURSO=200`, `LOGIN_ESTUDIANTE=200`, `METODOS_PAGO=200`, `COMPRA=201`, `COMPRA_REPETIDA=200`, `CONFIRMAR_PAGO=200`, `COMPLETAR_NIVEL=200`, `DIPLOMA_INSCRIPCION=200`, `VERIFICAR_DIPLOMA=200`, `VERIFICAR_INVALIDO=404`, `BAJA_CURSO=200`, `REACTIVAR_CURSO=200`, `ELIMINAR_NIVEL_CON_PROGRESO=409` y las seis vistas web en `200`. Log PHP limpio.

### Fase 0 — Cimientos

- Nuevo `App\Core\Container` con bindings en `app/config/bindings.php` y autowire en el router.
- Interfaces `MailerInterface` y `UsuarioRepositoryInterface`; `SendGridMailer` y `Usuario` son las implementaciones actuales.
- `PDO` se inyecta en los modelos y servicios; los controladores reciben sus servicios por constructor.
- `App\Core\Request` centraliza lectura de `string`, `raw`, `int`, `bool`, headers y JSON.
- CSRF condicional en API mutante: se exige token solo si la petición trae `Origin` o `Sec-Fetch-Site`; `api.js` envía `X-CSRF-Token` desde la meta del layout.
- Modal `SoscurConfirm` reutilizable en el footer, cableado al bloquear/reactivar usuarios.
- Verificación: `php -l` y `node --check` sin errores, JSON de Postman válido, `git diff --check` sin errores y smoke HTTP sobre BD temporal: `LOGIN=200`, `ADMIN_API=200`, `ADMIN_WEB=200`, `CSRF_SIN_TOKEN=403`, `CSRF_CON_TOKEN=200`, `REACTIVAR=200`, `TOKEN_SIN_ORIGEN=400`.

## Cómo ejecutar

```bash
php -S 127.0.0.1:8000 -t public public/index.php
```

Base de datos: importar `schema.sql`, `seed.sql`, `migrations/002_verificacion_correo.sql`, `migrations/003_cursos_pagos_diplomas.sql` y `migrations/004_mensajeria.sql`. Si la BD ya existía de antes de la Fase 0, aplicar primero `migrations/001_align_schema_ui.sql` (no en BD frescas: duplica `genero`).

Variables de entorno principales (plantilla en `.env.example`): `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_URL`, `SENDGRID_API_KEY`, `SENDGRID_FROM_EMAIL`, `SENDGRID_SANDBOX`. La app usa `getenv()`: no carga `.env` automáticamente.

## Convenciones para el equipo

- Los cambios de cada fase se verifican con `php -l`, `node --check`, parseo del JSON de Postman y smoke HTTP.
- Los nuevos endpoints se registran en `public/index.php` **y** en `app/config/access.php`.
- Toda acción destructiva debe pedir confirmación y mostrar aviso de éxito/error con `SoscurApi.request()` / `showAlert()`.
- `docs/prototipos/` se conserva como referencia visual; no se edita salvo que se corrija un link roto.
