# Estado de avance del proyecto

Este documento acompaña a `README.md` para que cualquier persona del equipo entienda rápido **qué está implementado, qué se está haciendo y por qué se tomaron ciertas decisiones**. Se actualiza al terminar cada fase.

## Contexto

SOSCUR Portal es un MVC PHP 8.2 sin framework, con MySQL y un front controller en `public/index.php`. La API propia vive bajo `/api/v1` y es consumida por el navegador mediante `fetch` (`public/js/api.js`). La autenticación usa sesiones, CSRF en formularios web, `password_hash()` y control de accesos centralizado en `app/config/access.php`.

La base de datos ya modela cursos, niveles, inscripciones, avance, comentarios y diplomas (`schema.sql`), y `seed.sql` contiene datos de demostración. Parte de esa funcionalidad todavía no tiene código ejecutable; las maquetas de `docs/prototipos/` muestran la intención pero **no son servidas por el router**.

## Rúbricas trabajadas

| Rúbrica | Puntaje | Situación inicial |
| --- | --- | --- |
| Portal en operación | 16 | Casi completo: catálogo, detalle, niveles, inscripción, avance y baja lógica accionables; faltan reportes y moderación. |
| API propia integrada | 12 | Implementada: flujos reales con `fetch` y colección Postman (carpeta 15 de Fase 1). |
| Reportes con filtros | 10 | Muy parcial: solo listado de usuarios con filtros. |
| Inyección de dependencias | 10 | Ampliada: contenedor, servicios inyectados y modelos con PDO; queda limpiar `Controller::model()` en Fase 4. |
| Módulo de administrador | 8 | Parcial: bloqueo/reactivación y gestión de cursos; categorías y moderación pendientes. |
| Interacción y búsqueda | 8 | Parcial: filtro por categoría, búsqueda local y detalle de curso; faltan comentarios, promedio y mensajería. |
| Pagos y certificado | 8 | Implementados: compra simulada, confirmación, avance por nivel, diploma automático y verificación por código. |
| Seguridad | 8 | Base sólida: CSRF condicional en API, control de acceso y validación de entrada; pendiente logout vía `fetch`. |
| Mensajes y avisos | 6 | Flash/éxito/error completos; falta confirmación antes de eliminar. |

## Decisiones técnicas acordadas

- **Inyección de dependencias:** contenedor propio mínimo (`App\Core\Container`) con autowire y bindings en `app/config/bindings.php`.
- **Pagos:** simulación local con dos métodos (`tarjeta` y `transferencia`); no se usa pasarela externa ni se guardan credenciales en el repo.
- **Certificado:** vista HTML imprimible con código de verificación; no se añade Composer.
- **Baja lógica:** se usa `cursos.estatus` (`activo`/`inactivo`); no se borran filas porque las FK usan `ON DELETE CASCADE`.
- **Formatos:** fechas `dd/mm/yyyy` y moneda MXN con `$` y dos decimales. PHP no tiene `intl`, así que se usa `number_format()` en servidor; el navegador conserva `Intl.NumberFormat`.
- **CSRF en API:** se exige token solo cuando la petición parece del navegador (`Sec-Fetch-Site`/`Origin`). Postman y curl siguen funcionando sin token.
- **Commits:** uno por fase, con mensaje descriptivo.

## Plan por fases

| Fase | Contenido | Estado |
| --- | --- | --- |
| 0 | Contenedor DI, interfaces, PDO inyectado, `Request`, CSRF API, confirmaciones y este documento | Completada |
| 1 | Cursos/niveles completos, inscripción, pagos simulados y diploma automático | Completada |
| 2 | Reportes con filtros y formatos | Pendiente |
| 3 | CRUD de categorías, moderación de comentarios, comentarios/promedio, mensajería y búsqueda ampliada | Pendiente |
| 4 | Canje de token, tabla admin y logout vía `fetch` | Pendiente |

## Registro de avances

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

Base de datos: importar `schema.sql` y `seed.sql`. Para Fase 1 aplicar además `migrations/002_verificacion_correo.sql` y `migrations/003_cursos_pagos_diplomas.sql`. Si la BD ya existía de antes de la Fase 0, aplicar primero `migrations/001_align_schema_ui.sql` (no en BD frescas: duplica `genero`).

Variables de entorno principales (plantilla en `.env.example`): `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_URL`, `SENDGRID_API_KEY`, `SENDGRID_FROM_EMAIL`, `SENDGRID_SANDBOX`. La app usa `getenv()`: no carga `.env` automáticamente.

## Convenciones para el equipo

- Los cambios de cada fase se verifican con `php -l`, `node --check`, parseo del JSON de Postman y smoke HTTP.
- Los nuevos endpoints se registran en `public/index.php` **y** en `app/config/access.php`.
- Toda acción destructiva debe pedir confirmación y mostrar aviso de éxito/error con `SoscurApi.request()` / `showAlert()`.
- `docs/prototipos/` se conserva como referencia visual; no se edita salvo que se corrija un link roto.
