# Portal de Cursos en Línea — PWCI (PIA)

Proyecto Integrador de Aprendizaje (PIA) para Programación Web Capa Intermedia (Grupo 051) de la Licenciatura en Multimedia y Animación Digital, UANL.

## Integrantes

- **Juan Pablo Flores Garibay #1905170** — Programador de Capa Intermedia. Controladores y modelos PHP (POO + MVC), API REST, autenticación, conexión y gestión de MySQL.
- **Ismael Cruz Vazquez** — Programador de Cliente e Integraciones. Vistas HTML5/CSS3/JS, consumo de API con `fetch`, integraciones y validaciones de interfaz.

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
   ```

   `seed.sql` carga usuarios, categorías, cursos, niveles, inscripciones, comentarios y un diploma de demostración.

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

## Rutas web

| Método | Ruta | Descripción |
| --- | --- | --- |
| GET | `/` | Catálogo público |
| GET/POST | `/login` | Inicio de sesión |
| GET/POST | `/registro` | Registro de estudiante o instructor |
| POST | `/logout` | Cerrar sesión |
| GET | `/dashboard` | Panel según el rol |
| GET/POST | `/perfil` | Consultar y actualizar perfil |
| POST | `/perfil/contrasena` | Cambiar contraseña |
| GET | `/admin/usuarios` | Listado de usuarios (solo admin) |
| POST | `/admin/usuarios/{id}/bloquear` | Bloquear usuario (solo admin) |
| POST | `/admin/usuarios/{id}/reactivar` | Reactivar usuario (solo admin) |

Los formularios web usan token CSRF y las contraseñas se almacenan con `password_hash()`.

## API REST

Base: `/api/v1`

| Método | Endpoint | Auth | Descripción |
| --- | --- | --- | --- |
| GET | `/cursos?categoria_id=1` | No | Cursos activos por categoría, con paginación |
| POST | `/cursos` | Instructor/admin | Registrar curso |
| POST | `/registro` | No | Registrar estudiante o instructor |
| POST | `/login` | No | Iniciar sesión y crear cookie de sesión |
| POST | `/logout` | Sesión | Cerrar sesión |
| GET | `/perfil` | Sesión | Consultar perfil propio |
| PATCH | `/perfil` | Sesión | Actualizar nombre, género y fecha de nacimiento |
| POST | `/perfil/contrasena` | Sesión | Cambiar contraseña |
| POST | `/perfil/foto` | Sesión | Subir avatar (`multipart/form-data`) |
| GET | `/admin/usuarios` | Admin | Listar usuarios con filtros `rol` y `bloqueado` |
| POST | `/admin/usuarios/{id}/bloquear` | Admin | Bloquear usuario |
| POST | `/admin/usuarios/{id}/reactivar` | Admin | Reactivar usuario |

### Bloqueo por intentos fallidos

Después de **3 intentos fallidos**, la cuenta se bloquea automáticamente. El login de una cuenta bloqueada responde `401` y solo un administrador puede reactivarla. El conteo se actualiza dentro de una transacción con `SELECT ... FOR UPDATE` para evitar condiciones de carrera.

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
├── config/              Configuración y conexión PDO
├── controllers/         Controladores web y API
├── core/                Router, Controller y JsonController
├── helpers/             Auth, Csrf, Session, Upload y Url
├── models/              Acceso a datos
├── services/            Lógica de autenticación y usuarios
└── views/               Vistas PHP
public/
├── css/
├── js/
├── uploads/avatars/     Avatares subidos
└── index.php            Punto de entrada y rutas
docs/postman/            Colección de pruebas de la API
schema.sql               Esquema completo
seed.sql                 Datos de demostración
```

## Pruebas

La colección `docs/postman/soscur-api.postman_collection.json` contiene las pruebas de cursos, autenticación, perfil y administración. Ajustar la variable `baseUrl` según la URL local del proyecto.

También se puede validar la sintaxis de todo el código PHP con PowerShell:

```powershell
Get-ChildItem -Recurse -Filter *.php app,public | ForEach-Object { php -l $_.FullName }
```
