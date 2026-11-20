<?php

declare(strict_types=1);

return [
    'POST /logout' => null,
    'GET /dashboard' => null,
    'GET /admin/cursos' => ['instructor', 'admin'],
    'GET /mis-cursos' => null,
    'GET /diploma/{id}' => null,
    'GET /perfil' => null,
    'POST /perfil' => null,
    'POST /perfil/contrasena' => null,

    'GET /admin/usuarios' => ['admin'],
    'POST /admin/usuarios/{id}/bloquear' => ['admin'],
    'POST /admin/usuarios/{id}/reactivar' => ['admin'],

    'GET /api/v1/cursos/gestion' => ['instructor', 'admin'],
    'POST /api/v1/cursos' => ['instructor', 'admin'],
    'PATCH /api/v1/cursos/{id}' => ['instructor', 'admin'],
    'POST /api/v1/cursos/{id}/baja' => ['instructor', 'admin'],
    'POST /api/v1/cursos/{id}/reactivar' => ['instructor', 'admin'],
    'POST /api/v1/cursos/{id}/niveles' => ['instructor', 'admin'],
    'PATCH /api/v1/niveles/{id}' => ['instructor', 'admin'],
    'DELETE /api/v1/niveles/{id}' => ['instructor', 'admin'],
    'GET /api/v1/mis-cursos' => null,
    'POST /api/v1/inscripciones' => null,
    'POST /api/v1/pagos/{id}/confirmar' => null,
    'POST /api/v1/inscripciones/{id}/niveles/{nivelId}/completar' => null,
    'GET /api/v1/inscripciones/{id}/diploma' => null,
    'POST /api/v1/verificar-correo/reenviar' => null,
    'POST /api/v1/logout' => null,
    'GET /api/v1/perfil' => null,
    'PATCH /api/v1/perfil' => null,
    'POST /api/v1/perfil/contrasena' => null,
    'POST /api/v1/perfil/foto' => null,

    'GET /api/v1/admin/usuarios' => ['admin'],
    'POST /api/v1/admin/usuarios/{id}/bloquear' => ['admin'],
    'POST /api/v1/admin/usuarios/{id}/reactivar' => ['admin'],
];
