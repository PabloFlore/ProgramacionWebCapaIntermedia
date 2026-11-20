# Estado de avance del proyecto

Este documento acompaña a `README.md` para que cualquier persona del equipo entienda rápido **qué está implementado, qué se está haciendo y por qué se tomaron ciertas decisiones**. Se actualiza al terminar cada fase.

## Contexto

SOSCUR Portal es un MVC PHP 8.2 sin framework, con MySQL y un front controller en `public/index.php`. La API propia vive bajo `/api/v1` y es consumida por el navegador mediante `fetch` (`public/js/api.js`). La autenticación usa sesiones, CSRF en formularios web, `password_hash()` y control de accesos centralizado en `app/config/access.php`.

La base de datos ya modela cursos, niveles, inscripciones, avance, comentarios y diplomas (`schema.sql`), y `seed.sql` contiene datos de demostración. Parte de esa funcionalidad todavía no tiene código ejecutable; las maquetas de `docs/prototipos/` muestran la intención pero **no son servidas por el router**.

## Rúbricas trabajadas

| Rúbrica | Puntaje | Situación inicial |
| --- | --- | --- |
| Portal en operación | 16 | Parcial: catálogo y alta de curso por API; faltan niveles, inscripción, avance y baja accionable. |
| API propia integrada | 12 | Implementada: flujos reales con `fetch` y colección Postman. |
| Reportes con filtros | 10 | Muy parcial: solo listado de usuarios con filtros. |
| Inyección de dependencias | 10 | Parcial: middleware inyectable, pero `new` interno en controladores/servicios/modelos. |
| Módulo de administrador | 8 | Parcial: bloqueo/reactivación reales; categorías y moderación pendientes. |
| Interacción y búsqueda | 8 | Parcial: filtro por categoría y búsqueda local; faltan comentarios, promedio y mensajería. |
| Pagos y certificado | 8 | Ausente en código; la base de datos ya tiene tablas preparadas. |
| Seguridad | 8 | Base sólida; falta reforzar CSRF en API mutante y tipado estricto de entrada. |
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
| 1 | Cursos/niveles completos, inscripción, pagos simulados y diploma automático | Pendiente |
| 2 | Reportes con filtros y formatos | Pendiente |
| 3 | CRUD de categorías, moderación de comentarios, comentarios/promedio, mensajería y búsqueda ampliada | Pendiente |
| 4 | Canje de token, tabla admin y logout vía `fetch` | Pendiente |

## Registro de avances

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

Base de datos: importar `schema.sql` y `seed.sql`. Si la BD ya existía, aplicar `migrations/001_align_schema_ui.sql`, `migrations/002_verificacion_correo.sql` y las que se añadan en fases posteriores.

Variables de entorno principales (plantilla en `.env.example`): `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_URL`, `SENDGRID_API_KEY`, `SENDGRID_FROM_EMAIL`, `SENDGRID_SANDBOX`. La app usa `getenv()`: no carga `.env` automáticamente.

## Convenciones para el equipo

- Los cambios de cada fase se verifican con `php -l`, `node --check`, parseo del JSON de Postman y smoke HTTP.
- Los nuevos endpoints se registran en `public/index.php` **y** en `app/config/access.php`.
- Toda acción destructiva debe pedir confirmación y mostrar aviso de éxito/error con `SoscurApi.request()` / `showAlert()`.
- `docs/prototipos/` se conserva como referencia visual; no se edita salvo que se corrija un link roto.
