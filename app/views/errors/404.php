<?php

use App\Helpers\Url;

$title = 'No encontrado';
require __DIR__ . '/../templates/header.php';
?>
<div class="text-center py-5">
  <i class="bi bi-exclamation-triangle fs-1 text-warning"></i>
  <h4 class="fw-bold mt-3">404 — No encontrado</h4>
  <p class="text-muted">El curso solicitado no existe o ya no está disponible.</p>
  <a class="btn btn-primary-soscur" href="<?= htmlspecialchars(Url::to(''), ENT_QUOTES, 'UTF-8') ?>">Volver al catálogo</a>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
