<?php

use App\Helpers\Url;

$title = 'Dashboard';
require __DIR__ . '/../templates/header.php';

$rol = (string) $usuario['rol'];
$descripcionRol = [
    'estudiante' => 'Explora el catálogo, inscríbete a cursos y revisa tu progreso.',
    'instructor' => 'Gestiona tus cursos y revisa a tus estudiantes.',
    'admin' => 'Administra usuarios, cursos y la operación general del portal.',
][$rol] ?? 'Bienvenido al portal.';
?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?>">
    <?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<div class="card border shadow-sm mb-4">
  <div class="card-body p-4">
    <h4 class="fw-bold mb-1">Hola, <?= htmlspecialchars((string) $usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></h4>
    <p class="text-muted mb-2"><?= htmlspecialchars($descripcionRol, ENT_QUOTES, 'UTF-8') ?></p>
    <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($rol, ENT_QUOTES, 'UTF-8') ?></span>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-6 col-lg-4">
    <a class="card border shadow-sm h-100 text-decoration-none text-reset" href="<?= htmlspecialchars(Url::to('perfil'), ENT_QUOTES, 'UTF-8') ?>">
      <div class="card-body">
        <i class="bi bi-person-gear fs-3 text-primary"></i>
        <h6 class="fw-bold mt-3 mb-1">Mi perfil</h6>
        <p class="text-muted small mb-0">Consulta y actualiza tus datos y tu foto.</p>
      </div>
    </a>
  </div>

  <?php if ($rol === 'admin'): ?>
    <div class="col-md-6 col-lg-4">
      <a class="card border shadow-sm h-100 text-decoration-none text-reset" href="<?= htmlspecialchars(Url::to('admin/usuarios'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="card-body">
          <i class="bi bi-people fs-3 text-primary"></i>
          <h6 class="fw-bold mt-3 mb-1">Usuarios</h6>
          <p class="text-muted small mb-0">Lista, bloquea y reactiva cuentas.</p>
        </div>
      </a>
    </div>
  <?php endif; ?>

  <div class="col-md-6 col-lg-4">
    <a class="card border shadow-sm h-100 text-decoration-none text-reset" href="<?= htmlspecialchars(Url::to(''), ENT_QUOTES, 'UTF-8') ?>">
      <div class="card-body">
        <i class="bi bi-journal-bookmark fs-3 text-primary"></i>
        <h6 class="fw-bold mt-3 mb-1">Catálogo</h6>
        <p class="text-muted small mb-0">Explora los cursos disponibles en el portal.</p>
      </div>
    </a>
  </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
