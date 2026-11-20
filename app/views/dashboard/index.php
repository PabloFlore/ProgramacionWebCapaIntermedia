<?php

use App\Helpers\Url;

$title = 'Dashboard';
require __DIR__ . '/../templates/header.php';

$rol = (string) $usuario['rol'];
$descripcionRol = [
    'estudiante' => 'Explora el catálogo, inscríbete a cursos y revisa tu progreso.',
    'instructor' => 'Gestiona tus cursos y revisa a tus estudiantes.',
    'admin' => 'Administra usuarios, cursos y la operación general del portal.',
][$rol] ?? 'Bienvenido al portal.';
?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?>">
    <?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<?php if ((int) ($usuario['correo_verificado'] ?? 1) === 0): ?>
  <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2" role="status">
    <span>
      <i class="bi bi-envelope-exclamation" aria-hidden="true"></i>
      Tu correo todavía no está verificado. Revisa tu bandeja o solicita un nuevo enlace.
    </span>
    <button id="btnReenviarVerificacion" class="btn btn-outline-warning btn-sm" type="button">
      Reenviar correo
    </button>
  </div>
  <div id="verificacion-alert" aria-live="polite"></div>
<?php endif; ?>

<div class="card border shadow-sm mb-4">
  <div class="card-body p-4">
    <h4 class="fw-bold mb-1">Hola, <?= htmlspecialchars((string) $usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></h4>
    <p class="text-muted mb-2"><?= htmlspecialchars($descripcionRol, ENT_QUOTES, 'UTF-8') ?></p>
    <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($rol, ENT_QUOTES, 'UTF-8') ?></span>
  </div>
</div>

<div class="row g-3">
  <?php if (in_array($rol, ['instructor', 'admin'], true)): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card border shadow-sm h-100">
        <div class="card-body">
          <i class="bi bi-journal-plus fs-3 text-primary"></i>
          <h6 class="fw-bold mt-3 mb-1">Nuevo curso</h6>
          <p class="text-muted small mb-3">Registra un curso desde el panel, sin recargar la página.</p>
          <button class="btn btn-primary-soscur btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#modalCurso">
            Crear curso
          </button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="col-md-6 col-lg-4">
    <a class="card border shadow-sm h-100 text-decoration-none text-reset" href="<?= htmlspecialchars(Url::to('perfil'), ENT_QUOTES, 'UTF-8') ?>">
      <div class="card-body">
        <i class="bi bi-person-gear fs-3 text-primary"></i>
        <h6 class="fw-bold mt-3 mb-1">Mi perfil</h6>
        <p class="text-muted small mb-0">Consulta y actualiza tus datos y tu foto.</p>
      </div>
    </a>
  </div>

  <?php if ($rol === 'admin'): ?>
    <div class="col-md-6 col-lg-4">
      <a class="card border shadow-sm h-100 text-decoration-none text-reset" href="<?= htmlspecialchars(Url::to('admin/usuarios'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="card-body">
          <i class="bi bi-people fs-3 text-primary"></i>
          <h6 class="fw-bold mt-3 mb-1">Usuarios</h6>
          <p class="text-muted small mb-0">Lista, bloquea y reactiva cuentas.</p>
        </div>
      </a>
    </div>
  <?php endif; ?>

  <div class="col-md-6 col-lg-4">
    <a class="card border shadow-sm h-100 text-decoration-none text-reset" href="<?= htmlspecialchars(Url::to(''), ENT_QUOTES, 'UTF-8') ?>">
      <div class="card-body">
        <i class="bi bi-journal-bookmark fs-3 text-primary"></i>
        <h6 class="fw-bold mt-3 mb-1">Catálogo</h6>
        <p class="text-muted small mb-0">Explora los cursos disponibles en el portal.</p>
      </div>
    </a>
  </div>
</div>

<?php if (in_array($rol, ['instructor', 'admin'], true)): ?>
  <div class="card border shadow-sm mt-4">
    <div class="card-body p-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
          <h5 class="fw-bold mb-1">Gestión de cursos</h5>
          <p class="text-muted small mb-0">Los cursos creados aquí se guardan mediante la API y aparecen sin recargar.</p>
        </div>
        <button class="btn btn-primary-soscur" type="button" data-bs-toggle="modal" data-bs-target="#modalCurso">
          <i class="bi bi-plus-lg"></i> Nuevo curso
        </button>
      </div>

      <div id="course-alert" aria-live="polite"></div>

      <h6 class="fw-bold mt-3 mb-2">Creados en esta sesión</h6>
      <ul id="cursos-creados" class="list-group list-group-flush">
        <li class="list-group-item text-muted small px-0 js-sin-cursos">Aún no has creado cursos desde este panel.</li>
      </ul>
    </div>
  </div>

  <div class="modal fade" id="modalCurso" tabindex="-1" aria-labelledby="modalCursoTitulo" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalCursoTitulo">Nuevo curso</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <form id="formCurso">
          <div class="modal-body">
            <div id="course-modal-alert" aria-live="polite"></div>

            <div class="mb-3">
              <label class="form-label" for="curso_titulo">Título</label>
              <input id="curso_titulo" name="titulo" class="form-control" maxlength="150" required>
            </div>

            <div class="mb-3">
              <label class="form-label" for="curso_descripcion">Descripción</label>
              <textarea id="curso_descripcion" name="descripcion" class="form-control" rows="3"></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label" for="curso_categoria_id">Categoría</label>
              <select id="curso_categoria_id" name="categoria_id" class="form-select" required>
                <option value="">Cargando categorías...</option>
              </select>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="curso_precio">Precio (MXN)</label>
                <input id="curso_precio" name="precio" type="number" class="form-control" min="0" step="0.01" value="0.00">
              </div>

              <div class="col-md-6">
                <label class="form-label" for="curso_tipo_precio">Tipo de precio</label>
                <select id="curso_tipo_precio" name="tipo_precio" class="form-select">
                  <option value="curso" selected>Precio del curso completo</option>
                  <option value="nivel">Precio por nivel</option>
                </select>
              </div>
            </div>

            <div class="mt-3">
              <label class="form-label" for="curso_estatus">Estatus</label>
              <select id="curso_estatus" name="estatus" class="form-select">
                <option value="activo" selected>Activo</option>
                <option value="inactivo">Inactivo</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button id="btnGuardarCurso" type="submit" class="btn btn-primary-soscur">Guardar curso</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php
if (in_array($rol, ['instructor', 'admin'], true)) {
    $pageScript = 'js/instructor.js';
}

require __DIR__ . '/../templates/footer.php';
?>
