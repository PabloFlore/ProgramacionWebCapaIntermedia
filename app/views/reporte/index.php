<?php

use App\Helpers\Fecha;
use App\Helpers\Url;

$title = 'Reportes';
$pageScripts = ['js/reportes.js'];
require __DIR__ . '/../templates/header.php';

$fechaLegible = static fn (mixed $valor): string => Fecha::formato($valor);

$dinero = static function (mixed $valor): string {
    return '$' . number_format((float) $valor, 2, '.', ',');
};

$queryCon = static function (array $cambios) use ($filtros): string {
    $consulta = array_merge($filtros, $cambios);
    $consulta = array_filter($consulta, static fn ($valor) => $valor !== '');

    return http_build_query($consulta);
};

$estadosPago = ['pendiente', 'pagado', 'reembolsado'];
$roles = ['estudiante', 'instructor', 'admin'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Reportes</h1>
    <p class="text-muted small mb-0">
      <?= $usuario['rol'] === 'admin'
          ? 'Reporte general del portal con filtros y formatos MXN.'
          : 'Reportes de ventas de tus cursos con filtros y formatos MXN.' ?>
    </p>
  </div>
  <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(Url::to('dashboard'), ENT_QUOTES, 'UTF-8') ?>">Volver al dashboard</a>
</div>

<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?>">
    <?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<?php if ($errores !== []): ?>
  <div class="alert alert-danger" id="reportes-errores">
    <ul class="mb-0">
      <?php foreach ($errores as $error): ?>
        <li><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form id="form-reportes" method="get" action="<?= htmlspecialchars(Url::to('reportes'), ENT_QUOTES, 'UTF-8') ?>" class="card border shadow-sm mb-4">
  <input type="hidden" name="tab" id="tab-reportes" value="<?= htmlspecialchars($filtros['tab'] ?? 'ventas', ENT_QUOTES, 'UTF-8') ?>">
  <div class="card-body p-4">
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label small" for="desde">Desde</label>
        <input id="desde" name="desde" type="date" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['desde'], ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small" for="hasta">Hasta</label>
        <input id="hasta" name="hasta" type="date" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['hasta'], ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small" for="curso_id">Curso</label>
        <select id="curso_id" name="curso_id" class="form-select form-select-sm">
          <option value="">Todos</option>
          <?php foreach ($cursos as $curso): ?>
            <option value="<?= (int) $curso['curso_id'] ?>" <?= $filtros['curso_id'] === (string) $curso['curso_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars((string) $curso['titulo'], ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small" for="categoria_id">Categoría</label>
        <select id="categoria_id" name="categoria_id" class="form-select form-select-sm">
          <option value="">Todas</option>
          <?php foreach ($categorias as $categoria): ?>
            <option value="<?= (int) $categoria['categoria_id'] ?>" <?= $filtros['categoria_id'] === (string) $categoria['categoria_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars((string) $categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small" for="estado_pago">Estado de pago</label>
        <select id="estado_pago" name="estado_pago" class="form-select form-select-sm">
          <option value="">Todos</option>
          <?php foreach ($estadosPago as $estado): ?>
            <option value="<?= $estado ?>" <?= $filtros['estado_pago'] === $estado ? 'selected' : '' ?>>
              <?= htmlspecialchars(ucfirst($estado), ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($usuario['rol'] === 'admin'): ?>
        <div class="col-md-3">
          <label class="form-label small" for="rol">Rol (Reporte usuarios)</label>
          <select id="rol" name="rol" class="form-select form-select-sm">
            <option value="">Estudiante (por defecto)</option>
            <?php foreach ($roles as $rol): ?>
              <option value="<?= $rol ?>" <?= $filtros['rol'] === $rol ? 'selected' : '' ?>>
                <?= htmlspecialchars(ucfirst($rol), ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small" for="bloqueado">Estado de cuenta</label>
          <select id="bloqueado" name="bloqueado" class="form-select form-select-sm">
            <option value="">Todas</option>
            <option value="0" <?= $filtros['bloqueado'] === '0' ? 'selected' : '' ?>>Activas</option>
            <option value="1" <?= $filtros['bloqueado'] === '1' ? 'selected' : '' ?>>Bloqueadas</option>
          </select>
        </div>
      <?php endif; ?>
      <div class="col-md-3">
        <label class="form-label small" for="limite">Filas por página</label>
        <select id="limite" name="limite" class="form-select form-select-sm">
          <?php foreach ([10, 25, 50] as $limite): ?>
            <option value="<?= $limite ?>" <?= $filtros['limite'] === (string) $limite ? 'selected' : '' ?>><?= $limite ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary-soscur" type="submit">Aplicar filtros</button>
        <button class="btn btn-outline-secondary" type="button" id="limpiar-reportes">Limpiar</button>
      </div>
    </div>
  </div>
</form>

<?php if ($reporte !== null): ?>
  <?php $resumen = $reporte['resumen']; ?>
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card border shadow-sm h-100"><div class="card-body">
        <div class="text-muted small">Usuarios totales</div>
        <div class="h4 fw-bold mb-0"><?= (int) $resumen['usuarios_totales'] ?></div>
        <div class="small text-muted"><?= (int) $resumen['usuarios_activos'] ?> activos / <?= (int) $resumen['usuarios_bloqueados'] ?> bloqueados</div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card border shadow-sm h-100"><div class="card-body">
        <div class="text-muted small">Cursos</div>
        <div class="h4 fw-bold mb-0"><?= (int) $resumen['cursos'] ?></div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card border shadow-sm h-100"><div class="card-body">
        <div class="text-muted small">Inscripciones</div>
        <div class="h4 fw-bold mb-0"><?= (int) $resumen['inscripciones'] ?></div>
        <div class="small text-muted"><?= (int) $resumen['ventas_pagadas'] ?> pagadas</div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card border shadow-sm h-100"><div class="card-body">
        <div class="text-muted small">Ingresos</div>
        <div class="h4 fw-bold mb-0"><?= htmlspecialchars($dinero($resumen['ingresos']), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="small text-muted">MXN</div>
      </div></div>
    </div>
  </div>

  <?php $tabActiva = ($filtros['tab'] ?? 'ventas') === 'alumnos' ? 'alumnos' : 'ventas'; ?>
  <ul class="nav nav-tabs mb-4" id="tabs-reportes" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link <?= $tabActiva === 'ventas' ? 'active' : '' ?>" id="tab-ventas-btn" data-bs-toggle="tab" data-bs-target="#tab-ventas" type="button" role="tab" aria-controls="tab-ventas" aria-selected="<?= $tabActiva === 'ventas' ? 'true' : 'false' ?>">Ventas por curso</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?= $tabActiva === 'alumnos' ? 'active' : '' ?>" id="tab-alumnos-btn" data-bs-toggle="tab" data-bs-target="#tab-alumnos" type="button" role="tab" aria-controls="tab-alumnos" aria-selected="<?= $tabActiva === 'alumnos' ? 'true' : 'false' ?>">Desglose por alumno</button>
    </li>
  </ul>
  <div class="tab-content">
    <div class="tab-pane fade <?= $tabActiva === 'ventas' ? 'show active' : '' ?>" id="tab-ventas" role="tabpanel" aria-labelledby="tab-ventas-btn">
      <div class="row g-4 mb-4">
        <div class="col-lg-4">
          <div class="card border shadow-sm h-100">
            <div class="card-body p-4">
              <h2 class="h6 fw-bold">Usuarios por rol</h2>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead class="table-light"><tr><th>Rol</th><th>Activos</th><th>Bloqueados</th><th>Total</th></tr></thead>
                  <tbody>
                    <?php if ($reporte['por_rol'] === []): ?>
                      <tr><td colspan="4" class="text-center text-muted py-3">Sin datos.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($reporte['por_rol'] as $fila): ?>
                      <tr>
                        <td class="text-uppercase small"><?= htmlspecialchars((string) $fila['rol'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $fila['activos'] ?></td>
                        <td><?= (int) $fila['bloqueados'] ?></td>
                        <td class="fw-semibold"><?= (int) $fila['total'] ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="card border shadow-sm h-100">
            <div class="card-body p-4">
              <h2 class="h6 fw-bold">Ventas por curso</h2>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Curso</th>
                      <th>Categoría</th>
                      <th>Inscritos</th>
                      <th>Nivel prom.</th>
                      <th class="text-end">Ingresos</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($reporte['por_curso'] === []): ?>
                      <tr><td colspan="5" class="text-center text-muted py-3">Sin datos.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($reporte['por_curso'] as $fila): ?>
                      <tr>
                        <td><?= htmlspecialchars((string) $fila['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small text-muted"><?= htmlspecialchars((string) $fila['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $fila['inscripciones'] ?></td>
                        <td><?= number_format((float) ($fila['nivel_promedio'] ?? 0), 1) ?></td>
                        <td class="text-end fw-semibold"><?= htmlspecialchars($dinero($fila['monto']), ENT_QUOTES, 'UTF-8') ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <h3 class="h6 fw-bold mt-4">Desglose de ingresos por forma de pago</h3>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Forma de pago</th>
                      <th>Operaciones</th>
                      <th class="text-end">Total recaudado</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($reporte['por_metodo_pago'])): ?>
                      <tr><td colspan="3" class="text-center text-muted py-2">Sin ingresos registrados.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($reporte['por_metodo_pago'] as $mp): ?>
                      <tr>
                        <td><?= htmlspecialchars((string) $mp['metodo_pago'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $mp['operaciones'] ?></td>
                        <td class="text-end fw-semibold"><?= htmlspecialchars($dinero($mp['total']), ENT_QUOTES, 'UTF-8') ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade <?= $tabActiva === 'alumnos' ? 'show active' : '' ?>" id="tab-alumnos" role="tabpanel" aria-labelledby="tab-alumnos-btn">
      <div class="card border shadow-sm">
        <div class="card-body p-4">
          <h2 class="h6 fw-bold">Desglose por alumno</h2>
          <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
              <thead class="table-light">
                <tr><th>Alumno</th><th>Curso</th><th>Fecha</th><th class="text-end">Monto</th><th>Pago</th></tr>
              </thead>
              <tbody>
                <?php if ($reporte['detalle'] === []): ?>
                  <tr><td colspan="5" class="text-center text-muted py-4">No hay inscripciones para este filtro.</td></tr>
                <?php endif; ?>
                <?php foreach ($reporte['detalle'] as $fila): ?>
                  <?php
                  $clasePago = match ($fila['estado_pago']) {
                      'pagado' => 'bg-success',
                      'pendiente' => 'bg-warning text-dark',
                      default => 'bg-secondary',
                  };
                  ?>
                  <tr>
                    <td>
                      <div class="fw-semibold small"><?= htmlspecialchars((string) $fila['estudiante'], ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="text-muted small"><?= htmlspecialchars((string) $fila['estudiante_correo'], ENT_QUOTES, 'UTF-8') ?></div>
                    </td>
                    <td class="small"><?= htmlspecialchars((string) $fila['curso'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small text-muted"><?= htmlspecialchars($fechaLegible($fila['fecha']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end small fw-semibold"><?= htmlspecialchars($dinero($fila['monto_pagado']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <span class="badge <?= $clasePago ?>"><?= htmlspecialchars(ucfirst((string) $fila['estado_pago']), ENT_QUOTES, 'UTF-8') ?></span>
                      <div class="text-muted small"><?= htmlspecialchars((string) $fila['metodo_pago'], ENT_QUOTES, 'UTF-8') ?></div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <?php $paginacion = $reporte['paginacion']; ?>
          <?php if ($paginacion['total_paginas'] > 1): ?>
            <nav class="mt-3" aria-label="Paginación de reportes">
              <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $paginacion['pagina'] <= 1 ? 'disabled' : '' ?>">
                  <a class="page-link" href="<?= htmlspecialchars(Url::to('reportes') . '?' . $queryCon(['pagina' => max(1, $paginacion['pagina'] - 1), 'tab' => 'alumnos']), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
                </li>
                <li class="page-item disabled"><span class="page-link"><?= (int) $paginacion['pagina'] ?> / <?= (int) $paginacion['total_paginas'] ?></span></li>
                <li class="page-item <?= $paginacion['pagina'] >= $paginacion['total_paginas'] ? 'disabled' : '' ?>">
                  <a class="page-link" href="<?= htmlspecialchars(Url::to('reportes') . '?' . $queryCon(['pagina' => min($paginacion['total_paginas'], $paginacion['pagina'] + 1), 'tab' => 'alumnos']), ENT_QUOTES, 'UTF-8') ?>">Siguiente</a>
                </li>
              </ul>
            </nav>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <?php if ($usuario['rol'] === 'admin'): ?>
    <?php $tipoUsuario = $filtros['rol'] === 'instructor' ? 'instructor' : 'estudiante'; ?>
    <div class="card border shadow-sm mt-4">
      <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
          <h2 class="h6 fw-bold mb-0">Reporte de usuarios activos (<?= $tipoUsuario === 'instructor' ? 'Instructores' : 'Estudiantes' ?>)</h2>
          <span class="small text-muted">Usa el filtro "Rol" arriba para alternar entre Instructor y Estudiante</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
              <?php if ($tipoUsuario === 'instructor'): ?>
                <tr>
                  <th>Usuario</th>
                  <th>Nombre</th>
                  <th>Fecha ingreso</th>
                  <th>Cursos ofrecidos</th>
                  <th class="text-end">Total de ganancias</th>
                </tr>
              <?php else: ?>
                <tr>
                  <th>Usuario</th>
                  <th>Nombre</th>
                  <th>Fecha ingreso</th>
                  <th>Cursos inscritos</th>
                  <th class="text-end">% Cursos terminados</th>
                </tr>
              <?php endif; ?>
            </thead>
            <tbody>
              <?php if (empty($reporte['usuarios_detallado'])): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No hay usuarios activos para este rol.</td></tr>
              <?php endif; ?>
              <?php foreach ($reporte['usuarios_detallado'] as $u): ?>
                <tr>
                  <td class="small"><?= htmlspecialchars((string) $u['usuario'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="fw-semibold small"><?= htmlspecialchars((string) $u['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="small text-muted"><?= htmlspecialchars($fechaLegible($u['fecha_registro']), ENT_QUOTES, 'UTF-8') ?></td>
                  <?php if ($tipoUsuario === 'instructor'): ?>
                    <td><?= (int) $u['cursos_ofrecidos'] ?></td>
                    <td class="text-end fw-semibold"><?= htmlspecialchars($dinero($u['total_ganancias']), ENT_QUOTES, 'UTF-8') ?></td>
                  <?php else: ?>
                    <td><?= (int) $u['cursos_inscritos'] ?></td>
                    <td class="text-end fw-semibold"><?= number_format((float) $u['porcentaje_terminados'], 1) ?>%</td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
<?php else: ?>
  <div class="alert alert-info">Corrige los filtros para ver el reporte.</div>
<?php endif; ?>

<?php require __DIR__ . '/../templates/footer.php'; ?>