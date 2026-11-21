<?php

use App\Helpers\Url;
use App\Helpers\Fecha;

$title = 'Kardex';
require __DIR__ . '/../templates/header.php';

$fechaLegible = static fn (mixed $valor): string => Fecha::formato($valor);
?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?>">
    <?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Kardex</h1>
    <p class="text-muted small mb-0">Historial académico de tus inscripciones y avances.</p>
  </div>
  <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(Url::to('mis-cursos'), ENT_QUOTES, 'UTF-8') ?>">Mis cursos</a>
</div>

<form method="get" action="<?= htmlspecialchars(Url::to('kardex'), ENT_QUOTES, 'UTF-8') ?>" class="card border shadow-sm mb-4">
  <div class="card-body p-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-2">
        <label class="form-label small" for="desde">Desde</label>
        <input id="desde" name="desde" type="date" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['desde'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small" for="hasta">Hasta</label>
        <input id="hasta" name="hasta" type="date" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['hasta'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small" for="categoria_id">Categoría</label>
        <select id="categoria_id" name="categoria_id" class="form-select form-select-sm">
          <option value="">Todas</option>
          <?php foreach ($categorias as $cat): ?>
            <option value="<?= (int) $cat['categoria_id'] ?>" <?= ($filtros['categoria_id'] ?? '') === (string) $cat['categoria_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars((string) $cat['nombre'], ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small" for="terminados">Avance</label>
        <select id="terminados" name="terminados" class="form-select form-select-sm">
          <option value="">Todos</option>
          <option value="1" <?= ($filtros['terminados'] ?? '') === '1' ? 'selected' : '' ?>>Solo terminados</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small" for="activos">Estatus curso</label>
        <select id="activos" name="activos" class="form-select form-select-sm">
          <option value="">Todos</option>
          <option value="1" <?= ($filtros['activos'] ?? '') === '1' ? 'selected' : '' ?>>Solo activos</option>
        </select>
      </div>
      <div class="col-md-1">
        <button class="btn btn-primary-soscur btn-sm w-100" type="submit">Filtrar</button>
      </div>
    </div>
  </div>
</form>

<div class="row g-3 mb-4">
  <?php foreach ([
      ['Inscripciones', $kardex['resumen']['total']],
      ['Completados', $kardex['resumen']['completados']],
      ['En curso', $kardex['resumen']['en_curso']],
      ['Pagadas', $kardex['resumen']['pagadas']],
  ] as [$etiqueta, $valor]): ?>
    <div class="col-6 col-lg-3">
      <div class="card border shadow-sm h-100">
        <div class="card-body p-3">
          <p class="text-muted small mb-1"><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></p>
          <p class="h4 fw-bold mb-0"><?= (int) $valor ?></p>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if (count($kardex['inscripciones']) === 0): ?>
  <div class="alert alert-info">
    Todavía no tienes movimientos en tu kardex. <a href="<?= htmlspecialchars(Url::to(''), ENT_QUOTES, 'UTF-8') ?>">Explora el catálogo</a>.
  </div>
<?php else: ?>
  <div class="card border shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Curso</th>
            <th>Inscripción</th>
            <th>Completado</th>
            <th>Pago</th>
            <th>Niveles</th>
            <th>Progreso</th>
            <th>Estatus</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($kardex['inscripciones'] as $inscripcion): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars((string) $inscripcion['curso_titulo'], ENT_QUOTES, 'UTF-8') ?></strong>
                <div class="small text-muted"><?= htmlspecialchars((string) ($inscripcion['categoria_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
              </td>
              <td class="small"><?= htmlspecialchars($fechaLegible($inscripcion['fecha_inscripcion'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
              <td class="small"><?= htmlspecialchars($fechaLegible($inscripcion['fecha_completado'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
              <td class="small">
                <span class="badge <?= $inscripcion['estado_pago'] === 'pagado' ? 'bg-success' : 'bg-warning text-dark' ?>">
                  <?= htmlspecialchars((string) $inscripcion['estado_pago'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <div class="text-muted">$<?= number_format((float) $inscripcion['monto_pagado'], 2, '.', ',') ?> MXN</div>
              </td>
              <td class="small">
                <?= (int) $inscripcion['niveles_completados'] ?> / <?= (int) $inscripcion['total_niveles'] ?>
              </td>
              <td style="min-width: 130px;">
                <div class="d-flex justify-content-between small">
                  <span><?= (int) $inscripcion['progreso'] ?>%</span>
                  <span><?= (bool) $inscripcion['completado'] ? 'Completado' : 'Activo' ?></span>
                </div>
                <div class="progress" style="height: 7px;">
                  <div class="progress-bar" role="progressbar" style="width: <?= (int) $inscripcion['progreso'] ?>%" aria-valuenow="<?= (int) $inscripcion['progreso'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </td>
              <td class="small">
                <?= htmlspecialchars((string) $inscripcion['curso_estatus'], ENT_QUOTES, 'UTF-8') ?>
              </td>
              <td class="text-end">
                <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars(Url::to('curso/' . (int) $inscripcion['curso_id']), ENT_QUOTES, 'UTF-8') ?>">Ver</a>
                <?php if ((bool) $inscripcion['completado']): ?>
                  <a class="btn btn-outline-dark btn-sm" href="<?= htmlspecialchars(Url::to('diploma/' . (int) $inscripcion['inscripcion_id']), ENT_QUOTES, 'UTF-8') ?>">Certificado</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
<?php
require __DIR__ . '/../templates/footer.php';
