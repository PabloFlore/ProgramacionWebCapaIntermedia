<?php

$title = 'Verificar certificado';
require __DIR__ . '/../templates/header.php';
?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?>">
    <?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card border shadow-sm">
      <div class="card-body p-4">
        <h1 class="h4 fw-bold mb-1">Verificar certificado</h1>
        <p class="text-muted small mb-4">Ingresa el código impreso en el certificado para confirmar su autenticidad.</p>

        <div id="verificar-alerta" aria-live="polite"></div>

        <form id="form-verificar-diploma" class="row g-2 align-items-end">
          <div class="col-sm-9">
            <label class="form-label small" for="codigo-diploma">Código de verificación</label>
            <input id="codigo-diploma" class="form-control" placeholder="SOSCUR-2026-XXXXXXXX" autocomplete="off" required>
          </div>
          <div class="col-sm-3">
            <button id="btn-verificar-diploma" type="submit" class="btn btn-primary-soscur w-100">Verificar</button>
          </div>
        </form>

        <div id="resultado-diploma" class="mt-4 d-none">
          <div class="alert alert-success mb-0">
            <h2 class="h6 fw-bold mb-2"><i class="bi bi-check-circle"></i> Certificado válido</h2>
            <p class="mb-1"><strong>Estudiante:</strong> <span id="resultado-estudiante"></span></p>
            <p class="mb-1"><strong>Curso:</strong> <span id="resultado-curso"></span></p>
            <p class="mb-0"><strong>Fecha de emisión:</strong> <span id="resultado-fecha"></span></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
$pageScript = 'js/diploma-verificar.js';
require __DIR__ . '/../templates/footer.php';
