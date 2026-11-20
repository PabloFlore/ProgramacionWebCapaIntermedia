<?php

use App\Helpers\Url;

$title = 'Gestión de cursos';
require __DIR__ . '/../templates/header.php';
?>
<div id="gestion-cursos">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Gestión de cursos</h1>
      <p class="text-muted small mb-0">Alta, edición, baja lógica y niveles mediante la API.</p>
    </div>
    <button class="btn btn-primary-soscur" type="button" data-bs-toggle="modal" data-bs-target="#modalCursoGestion">
      <i class="bi bi-plus-lg"></i> Nuevo curso
    </button>
  </div>

  <div id="gestion-alerta" aria-live="polite"></div>

  <div class="card border shadow-sm">
    <div class="card-body p-4">
      <div id="gestion-cargando" class="text-muted small py-3">Cargando cursos...</div>
      <div class="table-responsive d-none" id="gestion-tabla">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Curso</th>
              <th>Categoría</th>
              <th>Precio</th>
              <th>Estatus</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody id="gestion-filas"></tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card border shadow-sm mt-4 d-none" id="panel-niveles">
    <div class="card-body p-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <h2 class="h5 fw-bold mb-1" id="niveles-titulo">Niveles</h2>
          <p class="text-muted small mb-0">El orden debe ser único dentro del curso.</p>
        </div>
        <button id="btn-cerrar-niveles" class="btn btn-outline-secondary btn-sm" type="button">Cerrar</button>
      </div>
      <div id="niveles-alerta" aria-live="polite"></div>
      <div class="row g-3 mt-1">
        <div class="col-md-4">
          <label class="form-label small" for="nivel_titulo">Título</label>
          <input id="nivel_titulo" class="form-control form-control-sm" maxlength="150" required>
        </div>
        <div class="col-md-4">
          <label class="form-label small" for="nivel_video_url">URL del video</label>
          <input id="nivel_video_url" class="form-control form-control-sm" type="url" required>
        </div>
        <div class="col-md-2">
          <label class="form-label small" for="nivel_orden">Orden</label>
          <input id="nivel_orden" class="form-control form-control-sm" type="number" min="1" value="1" required>
        </div>
        <div class="col-md-2">
          <label class="form-label small" for="nivel_precio">Precio</label>
          <input id="nivel_precio" class="form-control form-control-sm" type="number" min="0" step="0.01">
        </div>
        <div class="col-md-6">
          <label class="form-label small" for="nivel_material_url">URL de material (opcional)</label>
          <input id="nivel_material_url" class="form-control form-control-sm" type="url">
        </div>
        <div class="col-md-3">
          <label class="form-label small" for="nivel_material_tipo">Tipo de material</label>
          <select id="nivel_material_tipo" class="form-select form-select-sm">
            <option value="">Sin material</option>
            <option value="pdf">PDF</option>
            <option value="enlace">Enlace</option>
          </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button id="btn-guardar-nivel" class="btn btn-outline-primary btn-sm w-100" type="button">Agregar nivel</button>
        </div>
      </div>
      <div id="niveles-listado" class="list-group list-group-flush mt-3"></div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalCursoGestion" tabindex="-1" aria-labelledby="modalCursoGestionTitulo" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCursoGestionTitulo">Nuevo curso</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form id="form-curso-gestion">
        <div class="modal-body">
          <div id="modal-curso-alerta" aria-live="polite"></div>
          <div class="mb-3">
            <label class="form-label" for="curso_titulo_gestion">Título</label>
            <input id="curso_titulo_gestion" class="form-control" maxlength="150" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="curso_descripcion_gestion">Descripción</label>
            <textarea id="curso_descripcion_gestion" class="form-control" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" for="curso_categoria_gestion">Categoría</label>
            <select id="curso_categoria_gestion" class="form-select" required>
              <option value="">Cargando...</option>
            </select>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="curso_precio_gestion">Precio (MXN)</label>
              <input id="curso_precio_gestion" class="form-control" type="number" min="0" step="0.01" value="0.00">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="curso_tipo_gestion">Tipo de precio</label>
              <select id="curso_tipo_gestion" class="form-select">
                <option value="curso">Curso completo</option>
                <option value="nivel">Por nivel</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button id="btn-guardar-curso" type="submit" class="btn btn-primary-soscur">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
  window.SOSCUR_APP_BASE = <?= json_encode(Url::to(''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<?php
$pageScript = 'js/course-gestion.js';
require __DIR__ . '/../templates/footer.php';
