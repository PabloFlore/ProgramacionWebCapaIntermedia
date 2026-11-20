<?php

use App\Helpers\Url;

$title = 'Mis cursos';
require __DIR__ . '/../templates/header.php';
?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?>">
    <?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Mis cursos</h1>
    <p class="text-muted small mb-0">Consulta tu progreso y descarga tus certificados.</p>
  </div>
  <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(Url::to('verificar-diploma'), ENT_QUOTES, 'UTF-8') ?>">
    Verificar certificado
  </a>
</div>

<?php if (count($inscripciones) === 0): ?>
  <div class="alert alert-info">
    Todavía no tienes inscripciones. <a href="<?= htmlspecialchars(Url::to(''), ENT_QUOTES, 'UTF-8') ?>">Explora el catálogo</a> para comenzar.
  </div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($inscripciones as $inscripcion): ?>
      <div class="col-md-6 col-xl-4">
        <div class="card border shadow-sm h-100">
          <div class="card-body d-flex flex-column">
            <span class="badge bg-light text-dark align-self-start mb-2"><?= htmlspecialchars($inscripcion['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
            <h5 class="fw-bold"><?= htmlspecialchars($inscripcion['titulo'], ENT_QUOTES, 'UTF-8') ?></h5>
            <p class="text-muted small"><?= htmlspecialchars(mb_substr((string) $inscripcion['descripcion'], 0, 110), ENT_QUOTES, 'UTF-8') ?>...</p>

            <div class="d-flex justify-content-between small mb-1">
              <span>Progreso</span>
              <strong><?= (int) $inscripcion['progreso'] ?>%</strong>
            </div>
            <div class="progress mb-3" style="height: 8px;">
              <div class="progress-bar" role="progressbar" style="width: <?= (int) $inscripcion['progreso'] ?>%" aria-valuenow="<?= (int) $inscripcion['progreso'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <p class="small mb-2">
              <span class="badge <?= $inscripcion['estado_pago'] === 'pagado' ? 'bg-success' : 'bg-warning text-dark' ?>">
                <?= htmlspecialchars($inscripcion['estado_pago'], ENT_QUOTES, 'UTF-8') ?>
              </span>
              <span class="text-muted ms-2">$<?= number_format((float) $inscripcion['monto_pagado'], 2, '.', ',') ?> MXN</span>
            </p>

            <div class="mt-auto d-flex flex-wrap gap-2">
              <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars(Url::to('curso/' . (int) $inscripcion['curso_id']), ENT_QUOTES, 'UTF-8') ?>">
                Continuar curso
              </a>
              <?php if ((bool) $inscripcion['completado']): ?>
                <a class="btn btn-outline-dark btn-sm" href="<?= htmlspecialchars(Url::to('diploma/' . (int) $inscripcion['inscripcion_id']), ENT_QUOTES, 'UTF-8') ?>">
                  <i class="bi bi-award"></i> Ver certificado
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php
require __DIR__ . '/../templates/footer.php';
