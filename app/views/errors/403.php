<?php

use App\Helpers\Url;

$title = 'Acceso denegado';
require __DIR__ . '/../templates/header.php';
?>
<div class="text-center py-5">
  <i class="bi bi-shield-lock fs-1 text-danger"></i>
  <h4 class="fw-bold mt-3">Acceso denegado</h4>
  <p class="text-muted">No tienes permisos para acceder a esta sección.</p>
  <a class="btn btn-primary-soscur" href="<?= htmlspecialchars(Url::to('dashboard'), ENT_QUOTES, 'UTF-8') ?>">Volver al dashboard</a>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
