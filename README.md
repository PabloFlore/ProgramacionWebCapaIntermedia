# Portal de Cursos en Línea — PWCI (PIA)

Proyecto Integrador de Aprendizaje (PIA) para Programación Web Capa Intermedia (Grupo 051) de la Licenciatura en Multimedia y Animación Digital, UANL.

## Integrantes

- **Juan Pablo Flores Garibay #1905170** — Programador de Capa Intermedia. Controladores y modelos PHP (POO + MVC), API REST, autenticación, conexión y gestión de MySQL.
- **Ismael Cruz Vazquez** — Programador de Cliente e Integraciones. Vistas HTML5/CSS3/JS, consumo de API con `fetch`, integraciones y validaciones de interfaz.

El avance por fases y las decisiones técnicas se documentan en [`docs/ESTADO_AVANCE.md`](docs/ESTADO_AVANCE.md).

## Tecnologías

- PHP 8.2 puro, sin frameworks, con arquitectura MVC.
- MySQL/MariaDB, InnoDB, llaves foráneas e integridad referencial.
- HTML5, CSS3, JavaScript ES6+, Bootstrap 5 y Fetch API.
- Postman para pruebas manuales de la API.

## Requisitos

- PHP 8.2 o superior con extensiones `pdo_mysql`, `fileinfo` y `mbstring`.
- MySQL 8 o MariaDB 10.4 o superior.
- Apache o el servidor embebido de PHP.

## Puesta en marcha

1. Crear la base de datos e importar el esquema:

   ```sql
   SOURCE schema.sql;
   SOURCE seed.sql;
   SOURCE migrations/002_verificacion_correo.sql;
   SOURCE migrations/003_cursos_pagos_diplomas.sql;
   SOURCE migrations/004_mensajeria.sql;
   ```

   `seed.sql` carga usuarios, categorías, cursos, niveles, inscripciones, comentarios y un diploma de demostración. La migración 004 crea la tabla `mensajes`.

2. Configurar la conexión con variables de entorno o con los valores por defecto de `app/config/config.php`:

   ```text
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=soscur_db
   DB_USER=root
   DB_PASS=
   ```

3. Iniciar el servidor. Con XAMPP, colocar el proyecto dentro de `htdocs` y abrir:

   ```text
   http://localhost/PWCI/public/
   ```

   También se puede usar el servidor embebido desde la raíz del proyecto:

   ```bash
   php -S 127.0.0.1:8000 -t public public/index.php
   ```

4. Crear el directorio de avatares si no existe:

   ```text
   public/uploads/avatars/
   ```

5. Configurar la integración con SendGrid (API de terceros). La app lee estas variables con `getenv()`: **no carga un archivo `.env` automáticamente**. Usa `SetEnv` de Apache, variables del sistema o `export` antes de iniciar el servidor embebido. `.env.example` es la plantilla.

   ```text
   APP_URL=http://localhost/PWCI/public
   SENDGRID_API_KEY=SG.xxxxxxxxxxxxxxxx
   SENDGRID_FROM_EMAIL=no-reply@tudominio.com
   SENDGRID_SANDBOX=true
   ```

   Sin API key el registro sigue funcionando y solo escribe un aviso en `error_log`. Con `SENDGRID_SANDBOX=true`, SendGrid valida el envío (HTTP 202) sin entregar el correo.

## Rutas web

| Método | Ruta | Descripción |
| --- | --- | --- |
| GET | `/` | Catálogo público |
| GET/POST | `/login` | Inicio de sesión |
| GET/POST | `/registro` | Registro de estudiante o instructor |
| GET | `/verificar-correo?token=...` | Confirma el correo desde el enlace y redirige a `/login` |
| POST | `/logout` | Cerrar sesión |
| GET | `/dashboard` | Panel según el rol |
| GET/POST | `/perfil` | Consultar y actualizar perfil |
| POST | `/perfil/contrasena` | Cambiar contraseña |
| GET | `/admin/usuarios` | Listado de usuarios (solo admin) |
| POST | `/admin/usuarios/{id}/bloquear` | Bloquear usuario (solo admin) |
| POST | `/admin/usuarios/{id}/reactivar` | Reactivar usuario (solo admin) |
| GET | `/reportes` | Reportes con filtros (instructor/admin) |
| GET/POST | `/admin/categorias` | Alta, edición y baja lógica de categorías (admin) |
| GET | `/admin/comentarios` | Moderación de comentarios con causa de eliminación (admin) |
| GET | `/mensajes` | Bandeja de entrada/salida y envío de mensajes (sesión) |

Los formularios web usan token CSRF y las contraseñas se almacenan con `password_hash()`. Las peticiones API mutantes que llegan desde el navegador también exigen `X-CSRF-Token`; las peticiones de Postman/curl sin `Origin` o `Sec-Fetch-Site` siguen funcionando igual.

## API REST

Base: `/api/v1`

| Método | Endpoint | Auth | Descripción |
| --- | --- | --- | --- |
| GET | `/categorias` | No | Categorías disponibles para el catálogo |
| GET | `/cursos?categoria_id=1` | No | Cursos activos por categoría, con paginación |
| GET | `/cursos?categoria_id=todas&q=modelado` | No | Búsqueda ampliada por título, descripción o categoría; `categoria_id=0|todas` incluye todas |
| GET | `/categorias/gestion` | Admin | Categorías para administración |
| POST | `/categorias` | Admin | Crear categoría (nombre único) |
| PATCH | `/categorias/{id}` | Admin | Actualizar categoría |
| DELETE | `/categorias/{id}` | Admin | Eliminar categoría sin cursos asociados |
| GET | `/cursos/{id}/comentarios` | No | Comentarios visibles y resumen con promedio 1–10 |
| POST | `/cursos/{id}/comentarios` | Estudiante | Comentar un curso completado, con calificación 1–10 |
| POST | `/cursos` | Instructor/admin | Registrar curso |
| POST | `/registro` | No | Registrar estudiante o instructor y enviar correo de verificación |
| POST | `/login` | No | Iniciar sesión y crear cookie de sesión |
| POST | `/verificar-correo` | No | Verificar correo con `{ "token": "..." }` |
| POST | `/verificar-correo/reenviar` | Sesión | Reenviar el correo de verificación (máx. 1 por hora; sin límite en sandbox) |
| POST | `/logout` | Sesión | Cerrar sesión |
| GET | `/perfil` | Sesión | Consultar perfil propio |
| PATCH | `/perfil` | Sesión | Actualizar nombre, género y fecha de nacimiento |
| POST | `/perfil/contrasena` | Sesión | Cambiar contraseña |
| POST | `/perfil/foto` | Sesión | Subir avatar (`multipart/form-data`) |
| GET | `/admin/usuarios?q=ana&orden=nombre&direccion=asc&pagina=1&limite=25` | Admin | Listar usuarios con búsqueda, orden, paginación y filtros `rol`/`bloqueado`; `orden` permite `usuario_id`, `nombre`, `correo`, `rol`, `bloqueado` o `fecha_registro`, `direccion` es `asc`/`desc` y `limite` va de 1 a 100 |
| GET | `/reportes?desde=2026-01-01&hasta=2026-12-31&estado_pago=pagado` | Instructor/admin | Reportes con resumen, por rol, por curso y desglose; filtros `desde`, `hasta`, `curso_id`, `categoria_id`, `estado_pago`, `rol`, `bloqueado`, `pagina` y `limit` |
| GET | `/admin/comentarios?curso_id=1&incluir_eliminados=1` | Admin | Moderación de comentarios; muestra causa y moderador cuando aplica |
| POST | `/admin/comentarios/{id}/eliminar` | Admin | Eliminación lógica de un comentario con `causa_eliminacion` |
| GET | `/mensajes?buzon=recibidos` | Sesión | Bandeja de mensajes (`recibidos` o `enviados`) |
| GET | `/mensajes/destinatarios` | Sesión | Destinatarios permitidos según el rol |
| POST | `/mensajes` | Sesión | Enviar mensaje interno |
| PATCH | `/mensajes/{id}/leido` | Sesión | Marcar como leído un mensaje recibido |
| POST | `/admin/usuarios/{id}/bloquear` | Admin | Bloquear usuario |
| POST | `/admin/usuarios/{id}/reactivar` | Admin | Reactivar usuario |

La respuesta de `GET /api/v1/admin/usuarios` incluye `data`, `total`, `pagina`, `limite` y `paginas`. Los parámetros `orden`, `direccion`, `pagina` y `limite` se validan en servidor: un valor no permitido responde `400` con un mensaje explicativo. La tabla web usa esos mismos parámetros y recarga solo el cuerpo de la tabla mediante `fetch`.

### Consumo desde el cliente con `fetch`

El cliente consume la API propia con `async/await` y `fetch`, sin recargar la página y mostrando avisos visibles en cada vista.

| Vista | Archivo JS | Interacción |
| --- | --- | --- |
| Inicio / catálogo | `public/js/catalog.js` | Categorías, cursos, paginación y búsqueda |
| Login | `public/js/auth.js` | `POST /api/v1/login` |
| Registro | `public/js/auth.js` | `POST /api/v1/registro` con `multipart/form-data` y foto opcional |
| Perfil | `public/js/profile.js` | `PATCH /api/v1/perfil`, `POST /api/v1/perfil/foto` y cambio de contraseña |
| Administración | `public/js/admin-usuarios.js` | Bloquear y reactivar usuarios |
| Reportes | `public/js/reportes.js` | Validar rango de fechas y limpiar filtros |
| Dashboard instructor/admin | `public/js/instructor.js` | Crear cursos con `POST /api/v1/cursos` |
| Administración de categorías | `public/js/admin-categorias.js` | Alta, edición y baja con confirmación |
| Detalle de curso | `public/js/comentarios.js` | Publicar comentarios, recargar promedio y listar opiniones |
| Moderación | `public/js/admin-comentarios.js` | Eliminar comentarios indicando causa y confirmación |
| Mensajería | `public/js/mensajes.js` | Enviar mensajes y marcarlos como leídos |
| Header global | `public/js/logout.js` | Cerrar sesión con `POST /api/v1/logout` sin recargar, conservando el formulario web como fallback |
| Dashboard | `public/js/verificacion.js` | Canjear el token de verificación y reenviar el correo |
| Administración de usuarios | `public/js/admin-usuarios.js` | Buscar, ordenar, paginar, bloquear y reactivar sin recargar |

La capa común está en `public/js/api.js`:

- Lee la URL base de `<meta name="api-base">`, generada con `Url::to('api/v1')`.
- Envía `credentials: 'same-origin'` para conservar la cookie de sesión.
- Convierte respuestas no exitosas en un error con `status`, mensaje y `detalles`.
- Expone `showAlert`, `showError`, `clearAlert`, `setLoading` y `redirect` para mantener un comportamiento uniforme.

Los formularios conservan `action` y `method`, por lo que siguen funcionando sin JavaScript. Los módulos JS interceptan el evento `submit`, validan en cliente, bloquean el botón mientras se procesa la petición y muestran los errores del servidor en un `alert` de Bootstrap. El registro acepta JSON (Postman) y `multipart/form-data` (navegador) en el mismo endpoint.

### Control de accesos

Las rutas sensibles se protegen con `App\Middleware\AccessMiddleware` antes de ejecutar el controlador. Las reglas se declaran centralizadas en `app/config/access.php` y el `Router` las aplica automáticamente al registrar cada ruta.

| Situación | Web | API |
| --- | --- | --- |
| Sin sesión | Redirección a `/login` | `401` JSON |
| Sesión sin rol permitido | `403` con vista de acceso denegado | `403` JSON |
| Sesión con rol permitido | Ejecuta el controlador | Ejecuta el controlador |

Rutas públicas: `/`, `/home`, `/login`, `/registro`, `GET /api/v1/cursos`, `GET /api/v1/cursos/{id}/comentarios`, `POST /api/v1/registro` y `POST /api/v1/login`. El resto de las rutas requieren sesión; las rutas de administración requieren rol `admin` y la creación de cursos requiere `instructor` o `admin`.

Para proteger una ruta nueva, agrégala a `app/config/access.php` con la clave `MÉTODO /ruta`: usa `null` para exigir solo sesión o un arreglo de roles permitidos.

### CORS

Las rutas `/api/*` pasan por `App\Middleware\CorsMiddleware` antes del enrutamiento y del control de accesos, por lo que las cabeceras CORS también acompañan respuestas `401`, `403` y `404`.

Orígenes locales permitidos por defecto:

- `http://localhost:3000`
- `http://localhost:5500`
- `http://localhost:8000`
- `http://localhost:8080`
- Los equivalentes con `http://127.0.0.1`

Para cambiarlos, definir `CORS_ALLOWED_ORIGINS` con una lista separada por comas. No se admite `*`: se devuelve el origen exacto y `Access-Control-Allow-Credentials: true`.

| Caso | Respuesta |
| --- | --- |
| Origen permitido | Continúa y agrega `Access-Control-Allow-Origin` |
| Preflight `OPTIONS` permitido | `204` con métodos, cabeceras y `Access-Control-Max-Age: 600` |
| Origen no autorizado | `403` JSON `{"error":"Origen no permitido"}` sin cabecera CORS |
| Sin `Origin` | Continúa sin cabeceras CORS (mismo origen, Postman o curl) |

Si el cliente corre en otro origen, la cookie de sesión también debe cumplir las reglas `SameSite`/`Secure` del navegador.

### Bloqueo por intentos fallidos

Después de **3 intentos fallidos**, la cuenta se bloquea automáticamente. El login de una cuenta bloqueada responde `401` y solo un administrador puede reactivarla. El conteo se actualiza dentro de una transacción con `SELECT ... FOR UPDATE` para evitar condiciones de carrera.

### Verificación de correo (API de terceros)

Se integra **SendGrid desde el servidor** (`AuthService` → `SendGridHelper`), no desde la vista, para que la API key nunca se exponga en el navegador. No se usa Composer: la petición HTTP se hace con cURL nativo a `https://api.sendgrid.com/v3/mail/send`.

- Al registrar, se genera un token de 64 hexadecimales (`random_bytes`), se guarda **su hash SHA-256** en `usuarios.token_verificacion` y se programa su caducidad en `token_verificacion_expira` (24 h, configurable con `verification_hours`).
- `SendGridHelper` envía el enlace `GET /verificar-correo?token=...`. El enlace invalida el token y marca `correo_verificado = 1`.
- En modo sandbox la respuesta de registro y de reenvío incluye `token_verificacion` para poder probar el flujo sin leer el correo. En modo normal ese campo es `null`.
- La cuenta **no se bloquea** mientras está sin verificar: el dashboard muestra un aviso con un botón que consume `POST /api/v1/verificar-correo/reenviar` vía `fetch` y un formulario para pegar el token y canjearlo con `POST /api/v1/verificar-correo`.
- El canje por `fetch` oculta el aviso al tener éxito; el enlace `GET /verificar-correo?token=...` del correo sigue funcionando como alternativa sin JavaScript.
- Errores de SendGrid se registran en `error_log` con el código HTTP y un fragmento de la respuesta; la key nunca se registra.

### Usuarios de demostración

Contraseña para todos: `Password1!`

| Correo | Rol |
| --- | --- |
| `admin@soscur.test` | Administrador |
| `ana@mail.com` | Instructor |
| `juan@mail.com` | Instructor |
| `ismael@mail.com` | Estudiante |
| `maria@mail.com` | Estudiante |
| `pedro@mail.com` | Estudiante bloqueado |

## Estructura

```text
app/
├── config/              Configuración, conexión PDO, política de acceso y CORS
├── controllers/         Controladores web y API
├── core/                Router, Container, Request, Controller y JsonController
├── helpers/             Auth, Csrf, Session, Upload, Url y SendGrid
├── middleware/          Middleware de accesos, CORS y CSRF de API
├── models/              Acceso a datos y contratos de repositorio
├── services/            Lógica de autenticación, usuarios, correo y contratos
└── views/               Vistas PHP en ejecución
public/
├── css/
├── js/
├── uploads/avatars/     Avatares subidos
└── index.php            Front controller y rutas
docs/
├── ESTADO_AVANCE.md     Seguimiento de fases y decisiones técnicas
├── postman/             Colección de pruebas de la API
├── prototipos/          Maquetas HTML estáticas (no servidas por el router)
└── presentacion/        Material de la asignatura
migrations/              Migraciones para bases existentes
tests/
├── cors-demo/           Prueba manual de CORS
├── e2e/                 Prueba Playwright
└── healthcheck.php      Comprobación rápida de PHP
schema.sql               Esquema completo
seed.sql                 Datos de demostración
```

## Base de datos existente

Si `soscur_db` ya existía con el esquema anterior, aplicar `migrations/001_align_schema_ui.sql`, `migrations/002_verificacion_correo.sql`, `migrations/003_cursos_pagos_diplomas.sql` y `migrations/004_mensajeria.sql` antes de usar esta versión. Para instalaciones nuevas, usar `schema.sql`, `seed.sql` y aplicar las migraciones 002, 003 y 004 (no la 001, porque duplica `genero`).

## Pruebas

La colección `docs/postman/soscur-api.postman_collection.json` contiene las pruebas de cursos, autenticación, perfil, administración, control de accesos, CORS, verificación de correo, pagos/certificados (carpeta 15), reportes (carpeta 16), Fase 3 (carpeta 17: búsqueda, comentarios, promedio, moderación, mensajería y categorías) y Fase 4 (carpeta 18: logout con CSRF condicional, canje de token y tabla admin con búsqueda, orden y paginación). Ajustar `baseUrl` según la URL local del proyecto y, si hace falta, `corsAllowedOrigin`/`corsDeniedOrigin`. Las pruebas de rol insuficiente y origen no autorizado esperan `403`; las de sesión ausente esperan `401`. Los scripts de `tests/` usan `http://127.0.0.1:8000`, el mismo puerto del servidor embebido documentado arriba.

Con `SENDGRID_SANDBOX=true`, la petición de registro devuelve `token_verificacion`; ese valor se usa en la carpeta «14. Verificación de correo» para comprobar `200` y, repetida, `400` por token ya usado. El reenvío sin sesión espera `401`.

También se puede validar la sintaxis de todo el código PHP con PowerShell:

```powershell
Get-ChildItem -Recurse -Filter *.php app,public | ForEach-Object { php -l $_.FullName }
```
