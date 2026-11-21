<?php

use App\Helpers\Auth;
use App\Helpers\Url;
use App\Helpers\Fecha;

$title = (string) $curso['titulo'];
require __DIR__ . '/../templates/header.php';

$usuarioActual = Auth::user();
$autenticado = $usuarioActual !== null;
$esEstudiante = $autenticado && $usuarioActual['rol'] === 'estudiante';
$cursoJson = json_encode(
    $curso,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>
<div id="curso-detalle" data-curso-id="<?= (int) $curso['curso_id'] ?>" data-autenticado="<?= $autenticado ? '1' : '0' ?>">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= htmlspecialchars(Url::to(''), ENT_QUOTES, 'UTF-8') ?>">Catálogo</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($curso['titulo'], ENT_QUOTES, 'UTF-8') ?></li>
    </ol>
  </nav>

  <div id="detalle-alerta" aria-live="polite"></div>

  <div class="row g-4">
    <section class="col-lg-8">
      <div class="card border shadow-sm">
        <div class="course-cover cover-1" style="min-height: 170px;">
          <?= htmlspecialchars(strtoupper(mb_substr((string) $curso['titulo'], 0, 3)), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="card-body p-4">
          <span class="badge bg-light text-dark mb-2"><?= htmlspecialchars($curso['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
          <h1 class="h3 fw-bold"><?= htmlspecialchars($curso['titulo'], ENT_QUOTES, 'UTF-8') ?></h1>
          <p class="text-muted"><?= nl2br(htmlspecialchars((string) ($curso['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
          <p class="small mb-1"><i class="bi bi-person-badge"></i> Instructor: <?= htmlspecialchars($curso['instructor_nombre'], ENT_QUOTES, 'UTF-8') ?></p>
          <p class="small mb-0"><i class="bi bi-calendar3"></i> Alta: <?= htmlspecialchars(Fecha::formato($curso['fecha_creacion']), ENT_QUOTES, 'UTF-8') ?></p>        
        </div>
      </div>

      <div class="card border shadow-sm mt-4">
        <div class="card-body p-4">
          <h2 class="h5 fw-bold mb-3">Contenido del curso</h2>
          <?php if (count($curso['niveles']) === 0): ?>
            <p class="text-muted mb-0">Este curso todavía no tiene niveles registrados.</p>
          <?php else: ?>
            <div class="list-group list-group-flush">
              <?php foreach ($curso['niveles'] as $nivel): ?>
                <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2 px-0">
                  <div>
                    <span class="badge bg-secondary me-2"><?= (int) $nivel['orden'] ?></span>
                    <strong><?= htmlspecialchars($nivel['titulo'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php if ($nivel['precio'] !== null): ?>
                      <span class="text-muted small ms-2">$<?= number_format((float) $nivel['precio'], 2, '.', ',') ?> MXN</span>
                    <?php endif; ?>
                  </div>
                  <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($nivel['video_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                      <i class="bi bi-play-btn"></i> Ver
                    </a>
                    <?php if (!empty($nivel['material_url'])): ?>
                      <a class="btn btn-outline-info btn-sm" href="<?= htmlspecialchars($nivel['material_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                        <i class="bi bi-paperclip"></i> Material
                      </a>
                    <?php endif; ?>
                    <?php if ($autenticado): ?>
                      <button type="button" class="btn btn-outline-success btn-sm btn-completar-nivel" data-nivel-id="<?= (int) $nivel['nivel_id'] ?>" disabled>
                        Marcar completado
                      </button>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card border shadow-sm mt-4" id="comentarios-curso">
        <div class="card-body p-4">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 class="h5 fw-bold mb-0">Comentarios y calificación</h2>
            <span class="badge bg-warning text-dark" id="comentarios-promedio">
              <?= $curso['calificacion_promedio'] === null
                  ? 'Sin calificaciones'
                  : number_format((float) $curso['calificacion_promedio'], 1, '.', ',') . '/10' ?>
            </span>
          </div>
          <p class="small text-muted mt-2 mb-3" id="comentarios-total">
            <?= (int) $curso['total_comentarios'] ?> comentario(s) publicado(s).
          </p>
          <div id="comentarios-alerta" aria-live="polite"></div>
          <div id="comentarios-lista" class="list-group list-group-flush">
            <p class="text-muted small mb-0">Cargando comentarios...</p>
          </div>

          <?php if ($esEstudiante): ?>
            <form id="form-comentario" class="mt-4 border-top pt-3">
              <div id="comentario-alerta" aria-live="polite"></div>
              <label class="form-label small" for="comentario-calificacion">Calificación (1 a 10)</label>
              <select id="comentario-calificacion" class="form-select form-select-sm mb-3" required>
                <option value="">Selecciona</option>
                <?php for ($nota = 1; $nota <= 10; $nota++): ?>
                  <option value="<?= $nota ?>"><?= $nota ?></option>
                <?php endfor; ?>
              </select>
              <label class="form-label small" for="comentario-texto">Comentario</label>
              <textarea id="comentario-texto" class="form-control form-control-sm" rows="3" maxlength="1000"></textarea>
              <button id="btn-publicar-comentario" type="submit" class="btn btn-outline-primary btn-sm mt-3">Publicar comentario</button>
              <p class="text-muted small mt-2 mb-0">Solo puedes opinar si completaste este curso y una sola vez por curso.</p>
            </form>
          <?php elseif (!$autenticado): ?>
            <p class="small text-muted mt-3 mb-0">
              <a href="<?= htmlspecialchars(Url::to('login'), ENT_QUOTES, 'UTF-8') ?>">Inicia sesión</a> para dejar tu comentario.
            </p>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <aside class="col-lg-4">
      <div class="card border shadow-sm sticky-top" style="top: 80px;">
        <div class="card-body p-4">
          <p class="text-muted small mb-1">Precio de referencia</p>
          <p class="display-6 fw-bold mb-3">$<?= number_format((float) $curso['precio'], 2, '.', ',') ?> <span class="fs-6 text-muted">MXN</span></p>
          <span class="badge st-curso text-uppercase mb-4"><?= $curso['tipo_precio'] === 'nivel' ? 'Precio por nivel' : 'Curso completo' ?></span>

          <div id="compra-alerta" aria-live="polite"></div>

          <?php if (!$autenticado): ?>
            <a class="btn btn-primary-soscur w-100" href="<?= htmlspecialchars(Url::to('login'), ENT_QUOTES, 'UTF-8') ?>">
              Inicia sesión para inscribirte
            </a>
          <?php else: ?>
            <div id="compra-formulario">
              <?php if ($curso['tipo_precio'] === 'nivel' && count($curso['niveles']) > 0): ?>
                <label class="form-label small" for="nivel-compra">Comprar</label>
                <select id="nivel-compra" class="form-select form-select-sm mb-3">
                  <option value="">Curso completo</option>
                  <?php foreach ($curso['niveles'] as $nivel): ?>
                    <option value="<?= (int) $nivel['nivel_id'] ?>">
                      Nivel <?= (int) $nivel['orden'] ?> — <?= htmlspecialchars($nivel['titulo'], ENT_QUOTES, 'UTF-8') ?>
                      <?= $nivel['precio'] !== null ? '($' . number_format((float) $nivel['precio'], 2, '.', ',') . ' MXN)' : '' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>

              <label class="form-label small" for="metodo-pago">Método de pago</label>
              <select id="metodo-pago" class="form-select form-select-sm mb-3">
                <option value="">Cargando...</option>
              </select>

              <button id="btn-comprar" type="button" class="btn btn-primary-soscur w-100">
                <i class="bi bi-cart-plus"></i> Iniciar compra
              </button>
            </div>

            <div id="pago-pendiente" class="d-none">
              <div class="alert alert-warning small">
                <strong>Pago pendiente.</strong> Usa esta referencia para simular tu pago:
                <span id="referencia-pago" class="fw-bold d-block mt-1"></span>
                <span class="d-none" id="pago-id"></span>
              </div>
              <button id="btn-confirmar-pago" type="button" class="btn btn-success w-100">
                Confirmar pago
              </button>
            </div>

            <a id="diploma-enlace" class="btn btn-outline-dark w-100 mt-2 d-none" href="#">
              <i class="bi bi-award"></i> Ver certificado
            </a>
          <?php endif; ?>
        </div>
      </div>
    </aside>
  </div>
</div>
<script>
  window.SOSCUR_CURSO = <?= $cursoJson ?>;
</script>
<?php
$pageScripts = ['js/course.js', 'js/comentarios.js'];
require __DIR__ . '/../templates/footer.php';
