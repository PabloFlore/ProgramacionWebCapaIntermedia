<?php

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Url;

$titulo = $title ?? 'SOSCUR Portal';
$navUsuario = Auth::user();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?> — SOSCUR Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= htmlspecialchars(Url::asset('css/prototipo.css'), ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-soscur">
  <div class="container">
    <a class="navbar-brand" href="<?= htmlspecialchars(Url::to(''), ENT_QUOTES, 'UTF-8') ?>">
      <i class="bi bi-mortarboard-fill"></i> SOSCUR Portal
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPrincipal">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navPrincipal">
      <ul class="navbar-nav me-auto">
        <?php if ($navUsuario !== null): ?>
          <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(Url::to('dashboard'), ENT_QUOTES, 'UTF-8') ?>">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(Url::to('perfil'), ENT_QUOTES, 'UTF-8') ?>">Mi perfil</a></li>
          <?php if ($navUsuario['rol'] === 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(Url::to('admin/usuarios'), ENT_QUOTES, 'UTF-8') ?>">Usuarios</a></li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>
      <div class="d-flex align-items-center gap-2">
        <?php if ($navUsuario !== null): ?>
          <span class="navbar-text small">
            <?= htmlspecialchars($navUsuario['nombre'], ENT_QUOTES, 'UTF-8') ?>
            <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($navUsuario['rol'], ENT_QUOTES, 'UTF-8') ?></span>
          </span>
          <form method="post" action="<?= htmlspecialchars(Url::to('logout'), ENT_QUOTES, 'UTF-8') ?>">
            <?= Csrf::field() ?>
            <button class="btn btn-outline-danger btn-sm" type="submit">Cerrar sesión</button>
          </form>
        <?php else: ?>
          <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(Url::to('login'), ENT_QUOTES, 'UTF-8') ?>">Iniciar sesión</a>
          <a class="btn btn-primary-soscur btn-sm" href="<?= htmlspecialchars(Url::to('registro'), ENT_QUOTES, 'UTF-8') ?>">Registrarse</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<main class="container py-4">
