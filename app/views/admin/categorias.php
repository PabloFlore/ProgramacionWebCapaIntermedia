<?php

use App\Helpers\Url;

$title = 'Categorías';
$pageScripts = ['js/admin-categorias.js'];
require __DIR__ . '/../templates/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Categorías</h1>
    <p class="text-muted small mb-0">Alta, edición y eliminación de categorías del catálogo.</p>
  </div>
  <button class="btn btn-primary-soscur" type="button" data-bs-toggle="modal" data-bs-target="#modalCategoria">
    <i class="bi bi-plus-lg"></i> Nueva categoría
  </button>
</div>

<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?>">
    <?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<div id="categorias-alerta" aria-live="polite"></div>

<div class="card border shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Nombre</th>
          <th>Descripción</th>
          <th>Cursos</th>
          <th>Creado por</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody id="categorias-filas">
        <?php foreach ($categorias as $categoria): ?>
          <tr data-categoria-id="<?= (int) $categoria['categoria_id'] ?>"
              data-nombre="<?= htmlspecialchars((string) $categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>"
              data-descripcion="<?= htmlspecialchars((string) ($categoria['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <td class="fw-semibold"><?= htmlspecialchars((string) $categoria['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="small text-muted"><?= htmlspecialchars((string) ($categoria['descripcion'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int) $categoria['total_cursos'] ?></td>
            <td class="small text-muted"><?= htmlspecialchars((string) ($categoria['creado_por_nombre'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary js-editar-categoria" type="button">Editar</button>
              <button class="btn btn-sm btn-outline-danger js-eliminar-categoria" type="button">Eliminar</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalCategoria" tabindex="-1" aria-labelledby="modalCategoriaTitulo" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCategoriaTitulo">Nueva categoría</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form id="formCategoria">
        <div class="modal-body">
          <div id="categoria-modal-alerta" aria-live="polite"></div>
          <input type="hidden" id="categoria_id" value="">
          <div class="mb-3">
            <label class="form-label" for="categoria_nombre">Nombre</label>
            <input id="categoria_nombre" class="form-control" maxlength="100" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="categoria_descripcion">Descripción</label>
            <textarea id="categoria_descripcion" class="form-control" rows="3" maxlength="255"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button id="btnGuardarCategoria" type="submit" class="btn btn-primary-soscur">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
