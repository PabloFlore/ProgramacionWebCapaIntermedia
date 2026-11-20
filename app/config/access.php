<?php

declare(strict_types=1);

return [
    'POST /logout' => null,
    'GET /dashboard' => null,
    'GET /perfil' => null,
    'POST /perfil' => null,
    'POST /perfil/contrasena' => null,

    'GET /admin/usuarios' => ['admin'],
    'POST /admin/usuarios/{id}/bloquear' => ['admin'],
    'POST /admin/usuarios/{id}/reactivar' => ['admin'],

    'POST /api/v1/cursos' => ['instructor', 'admin'],
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
