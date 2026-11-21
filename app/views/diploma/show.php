<?php

use App\Helpers\Fecha;
use App\Helpers\Url;

$title = 'Certificado';
require __DIR__ . '/../templates/header.php';

$fechaEmision = Fecha::formato($diploma['fecha_emision'] ?? date('Y-m-d'));
?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?>">
    <?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Certificado de finalización</h1>
    <p class="text-muted small mb-0">Imprime o guarda tu comprobante con código de verificación.</p>
  </div>
  <div class="d-flex gap-2 no-print">
    <button class="btn btn-primary-soscur btn-sm" type="button" onclick="window.print()">
      <i class="bi bi-printer"></i> Imprimir
    </button>
    <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(Url::to('mis-cursos'), ENT_QUOTES, 'UTF-8') ?>">Volver</a>
  </div>
</div>

<div class="card border shadow-sm mx-auto" style="max-width: 900px;">
  <div class="card-body p-5 text-center certificado">
    <p class="text-uppercase text-muted letter-spacing mb-2">SOSCUR Portal · Formación verificable</p>
    <h2 class="display-6 fw-bold mb-4">Certificado de finalización</h2>
    <p class="lead mb-1">Se otorga el presente reconocimiento a</p>
    <p class="display-6 fw-bold text-primary my-3"><?= htmlspecialchars($diploma['estudiante_nombre'], ENT_QUOTES, 'UTF-8') ?></p>
    <p class="lead mb-1">por completar satisfactoriamente el curso</p>
    <p class="h4 fw-bold mb-5"><?= htmlspecialchars($diploma['curso_titulo'], ENT_QUOTES, 'UTF-8') ?></p>

    <div class="row border-top pt-4">
      <div class="col-md-6 text-md-start">
        <p class="small text-muted mb-1">Fecha de emisión</p>
        <p class="fw-bold mb-0"><?= htmlspecialchars($fechaEmision, ENT_QUOTES, 'UTF-8') ?></p>
      </div>
      <div class="col-md-6 text-md-end">
        <p class="small text-muted mb-1">Código de verificación</p>
        <p class="fw-bold mb-0"><?= htmlspecialchars($diploma['codigo_verificacion'], ENT_QUOTES, 'UTF-8') ?></p>
      </div>
    </div>
    <p class="small text-muted mt-4 mb-0">
      Verifica este documento en <?= htmlspecialchars(Url::absolute('verificar-diploma'), ENT_QUOTES, 'UTF-8') ?>
    </p>
  </div>
</div>
<?php
require __DIR__ . '/../templates/footer.php';
