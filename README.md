Portal de Cursos en Línea — PWCI (PIA)

Este repositorio contiene el desarrollo del Proyecto Integrador de Aprendizaje (PIA) para la materia de Programación Web Capa Intermedia (Grupo 051) de la Licenciatura en Multimedia y Animación Digital en la Universidad Autónoma de Nuevo León (UANL).

Integrantes del Equipo

Programador de Capa Intermedia: Juan Pablo Flores Garibay #1905170
Responsabilidades: Controladores y modelos en PHP (POO + MVC), API REST propia, middlewares (Autenticación/CORS), conexión y gestión de base de datos MySQL.

Programador de Cliente e Integraciones: Ismael Cruz Vazquez
Responsabilidades: Vistas (HTML5, CSS3, JS ES6+), consumo de API con fetch, integración de API de terceros, validaciones de interfaz y estilos.

Tecnologías Utilizadas

Backend PHP puro (sin frameworks), Programación Orientada a Objetos (POO), Patrón MVC.

Base de Datos: MySQL (Engine InnoDB con llaves foráneas e integridad referencial).

Frontend: HTML5, CSS3, JavaScript ES6+ (Fetch API / Async-Await).



Estructura del Proyecto (MVC)

El proyecto sigue una arquitectura  en tres capas sin mezclar consultas a la BD en vistas ni controladores:

```
/
├── config/              -- Configuración global y conexión a BD
│   └── Database.php
├── controllers/         -- Controladores (procesan peticiones)
│   ├── AuthController.php
│   ├── CursoController.php
│   ├── UsuarioController.php
│   └── CategoriaController.php
├── models/              -- Modelos (interacción exclusiva con MySQL)
│   ├── Usuario.php
│   ├── Curso.php
│   └── Categoria.php
├── views/               -- Vistas (interfaces del sistema)
│   ├── admin/
│   ├── instructor/
│   ├── estudiante/
│   └── public/
├── middlewares/         -- Middlewares de control
│   ├── AuthMiddleware.php
│   └── CorsMiddleware.php
├── api/                 -- Endpoints de la API Propia
│   └── v1/
├── public/              -- Archivos públicos accesibles por el cliente
│   ├── css/
│   ├── js/
│   └── uploads/         -- Avatares, imágenes de cursos y adjuntos
└── database/            -- Script SQL de creación de la base de datos
    └── schema.sql
```

Base de Datos

El esquema incluye perfiles de usuario, verificación de correo, "recordarme", recuperación de contraseña, bloqueo con motivo, modalidad de precio por curso o por nivel, material complementario por nivel, progreso por nivel, estado de pago y moderación de comentarios con causa.

- `schema.sql`: esquema completo para una instalación nueva.
- `migrations/001_align_schema_ui.sql`: migración para una base `soscur_db` creada con el esquema anterior.
- `seed.sql`: datos de demostración alineados con el prototipo. Todos los usuarios demo usan la contraseña `Password1!`.

Instalación desde cero:

```bash
mysql -u root -p < schema.sql
mysql -u root -p < seed.sql
```

Si la base ya existe, ejecutar solo la migración:

```bash
mysql -u root -p soscur_db < migrations/001_align_schema_ui.sql
```

Las calificaciones se guardan en escala `1–10`. Las vistas públicas pueden mostrar el promedio como estrellas usando `AVG(calificacion) / 2`.