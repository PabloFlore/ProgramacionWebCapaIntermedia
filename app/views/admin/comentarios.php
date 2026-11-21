<?php

use App\Helpers\Url;

$title = 'Moderación de comentarios';
$pageScripts = ['js/admin-comentarios.js'];
require __DIR__ . '/../templates/header.php';

$fechaLegible = static function (mixed $valor): string {
    if (!is_string($valor) || $valor === '') {
        return '—';
    }

    $marca = strtotime($valor);

    return $marca === false ? '—' : date('d/m/Y', $marca);
};
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Moderación de comentarios</h1>
    <p class="text-muted small mb-0">Revisa calificaciones y retira comentarios indicando la causa.</p>
  </div>
</div>

<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?>">
    <?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<?php if ($errores !== []): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errores as $error): ?>
        <li><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div id="moderacion-alerta" aria-live="polite"></div>

<form method="get" action="<?= htmlspecialchars(Url::to('admin/comentarios'), ENT_QUOTES, 'UTF-8') ?>" class="row g-2 align-items-end mb-3">
  <div class="col-md-5">
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
  <div class="col-md-4">
    <label class="form-label small" for="incluir_eliminados">Mostrar</label>
    <select id="incluir_eliminados" name="incluir_eliminados" class="form-select form-select-sm">
      <option value="">Solo activos</option>
      <option value="1" <?= $filtros['incluir_eliminados'] === '1' ? 'selected' : '' ?>>Incluir moderados</option>
    </select>
  </div>
  <div class="col-md-3">
    <button class="btn btn-outline-secondary btn-sm w-100" type="submit">Filtrar</button>
  </div>
</form>

<p class="small text-muted mb-3">
  <i class="bi bi-info-circle"></i> Leyenda:
  <span class="badge bg-success">Visible</span> comentario publicado;
  <span class="badge bg-danger">Eliminado por administración</span> comentario retirado por moderación, con causa, moderador y fecha;
  <span class="text-muted">Moderado</span> acción aplicada.
</p>

<div class="card border shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Curso</th>
          <th>Estudiante</th>
          <th>Calificación</th>
          <th>Comentario</th>
          <th>Moderación</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($comentarios === []): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No hay comentarios para este filtro.</td></tr>
        <?php endif; ?>
        <?php foreach ($comentarios as $comentario): ?>
          <tr>
            <td class="small fw-semibold"><?= htmlspecialchars((string) $comentario['curso_titulo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="small"><?= htmlspecialchars((string) $comentario['estudiante_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge bg-warning text-dark"><?= (int) $comentario['calificacion'] ?>/10</span></td>
            <td class="small">
              <?= htmlspecialchars((string) ($comentario['comentario'] ?? 'Sin texto'), ENT_QUOTES, 'UTF-8') ?>
              <div class="text-muted small"><?= htmlspecialchars($fechaLegible($comentario['fecha_comentario']), ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td class="small">
              <?php if ($comentario['eliminado_por_admin']): ?>
                <span class="badge bg-danger">Eliminado por administración</span>
                <div class="text-muted mt-1"><?= htmlspecialchars((string) ($comentario['causa_eliminacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-muted"><?= htmlspecialchars((string) ($comentario['moderado_por_nombre'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($fechaLegible($comentario['fecha_moderacion']), ENT_QUOTES, 'UTF-8') ?></div>
              <?php else: ?>
                <span class="badge bg-success">Visible</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <?php if (!$comentario['eliminado_por_admin']): ?>
                <button type="button"
                        class="btn btn-sm btn-outline-danger js-moderar-comentario"
                        data-id="<?= (int) $comentario['comentario_id'] ?>"
                        data-curso="<?= htmlspecialchars((string) $comentario['curso_titulo'], ENT_QUOTES, 'UTF-8') ?>"
                        data-estudiante="<?= htmlspecialchars((string) $comentario['estudiante_nombre'], ENT_QUOTES, 'UTF-8') ?>">
                  Eliminar
                </button>
              <?php else: ?>
                <span class="text-muted small">Moderado</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalModeracion" tabindex="-1" aria-labelledby="modalModeracionTitulo" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalModeracionTitulo">Eliminar comentario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form id="formModeracion">
        <div class="modal-body">
          <div id="moderacion-modal-alerta" aria-live="polite"></div>
          <input type="hidden" id="moderacion_id" value="">
          <p class="small text-muted" id="moderacion_detalle"></p>
          <div class="mb-3">
            <label class="form-label" for="moderacion_causa">Causa de eliminación</label>
            <textarea id="moderacion_causa" class="form-control" rows="3" maxlength="500" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button id="btnModerar" type="submit" class="btn btn-danger">Eliminar comentario</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
