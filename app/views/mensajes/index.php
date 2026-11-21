<?php

use App\Helpers\Url;
use App\Helpers\Fecha;

$title = 'Mensajería interna';
$pageScripts = ['js/mensajes.js'];
require __DIR__ . '/../templates/header.php';

$fechaLegible = static fn (mixed $valor): string => Fecha::formato($valor, true);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">Mensajería interna</h1>
    <p class="text-muted small mb-0">Comunícate con instructores, administración y estudiantes.</p>
  </div>
  <?php if ($no_leidos > 0): ?>
    <span class="badge bg-danger"><?= (int) $no_leidos ?> sin leer</span>
  <?php endif; ?>
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

<div id="mensajes-alerta" aria-live="polite"></div>

<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link <?= $filtros['buzon'] === 'recibidos' ? 'active' : '' ?>"
       href="<?= htmlspecialchars(Url::to('mensajes?buzon=recibidos'), ENT_QUOTES, 'UTF-8') ?>">Recibidos</a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $filtros['buzon'] === 'enviados' ? 'active' : '' ?>"
       href="<?= htmlspecialchars(Url::to('mensajes?buzon=enviados'), ENT_QUOTES, 'UTF-8') ?>">Enviados</a>
  </li>
</ul>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card border shadow-sm">
      <div class="card-body p-3">
        <?php if ($mensajes === []): ?>
          <p class="text-muted small mb-0">No hay mensajes en esta bandeja.</p>
        <?php endif; ?>
        <div class="list-group list-group-flush" id="lista-mensajes">
          <?php foreach ($mensajes as $mensaje): ?>
            <?php
              $remitente = $filtros['buzon'] === 'enviados'
                  ? $mensaje['destinatario_nombre']
                  : $mensaje['remitente_nombre'];
              $etiqueta = $filtros['buzon'] === 'enviados' ? 'Para' : 'De';
            ?>
            <article class="list-group-item px-0">
              <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                  <h2 class="h6 fw-bold mb-1"><?= htmlspecialchars((string) $mensaje['asunto'], ENT_QUOTES, 'UTF-8') ?></h2>
                  <div class="small text-muted">
                    <?= $etiqueta ?>: <?= htmlspecialchars((string) $remitente, ENT_QUOTES, 'UTF-8') ?>
                    · <?= htmlspecialchars($fechaLegible($mensaje['fecha_creacion']), ENT_QUOTES, 'UTF-8') ?>
                  </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                  <?php if ($filtros['buzon'] === 'recibidos'): ?>
                    <span class="badge <?= $mensaje['leido'] ? 'bg-secondary' : 'bg-primary' ?>">
                      <?= $mensaje['leido'] ? 'Leído' : 'Nuevo' ?>
                    </span>
                    <?php if (!$mensaje['leido']): ?>
                      <button type="button" class="btn btn-sm btn-outline-primary js-marcar-leido"
                              data-id="<?= (int) $mensaje['mensaje_id'] ?>">Marcar leído</button>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </div>
              <p class="small mt-2 mb-1"><?= nl2br(htmlspecialchars((string) $mensaje['cuerpo'], ENT_QUOTES, 'UTF-8')) ?></p>
              <?php if ($mensaje['curso_titulo']): ?>
                <div class="small text-muted">Curso: <?= htmlspecialchars((string) $mensaje['curso_titulo'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>

        <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
          <nav class="mt-3" aria-label="Paginación de mensajes">
            <ul class="pagination pagination-sm mb-0">
              <?php for ($pagina = 1; $pagina <= (int) $pagination['total_pages']; $pagina++): ?>
                <li class="page-item <?= (int) ($pagination['page'] ?? 1) === $pagina ? 'active' : '' ?>">
                  <a class="page-link"
                     href="<?= htmlspecialchars(Url::to('mensajes?buzon=' . $filtros['buzon'] . '&page=' . $pagina), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $pagina ?>
                  </a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card border shadow-sm">
      <div class="card-body p-4">
        <h2 class="h5 fw-bold mb-3">Nuevo mensaje</h2>
        <?php if ($destinatarios === []): ?>
          <p class="text-muted small mb-0">No hay destinatarios disponibles para tu rol.</p>
        <?php else: ?>
          <form id="form-mensaje">
            <div id="form-mensaje-alerta" aria-live="polite"></div>
            <div class="mb-3">
              <label class="form-label small" for="mensaje-destinatario">Destinatario</label>
              <select id="mensaje-destinatario" class="form-select form-select-sm" required>
                <option value="">Selecciona</option>
                <?php foreach ($destinatarios as $destinatario): ?>
                  <option value="<?= (int) $destinatario['usuario_id'] ?>">
                    <?= htmlspecialchars((string) $destinatario['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    (<?= htmlspecialchars((string) $destinatario['rol'], ENT_QUOTES, 'UTF-8') ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label small" for="mensaje-curso">Curso (opcional)</label>
              <select id="mensaje-curso" class="form-select form-select-sm">
                <option value="">Sin curso</option>
                <?php foreach ($cursos as $curso): ?>
                  <option value="<?= (int) $curso['curso_id'] ?>">
                    <?= htmlspecialchars((string) $curso['titulo'], ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label small" for="mensaje-asunto">Asunto</label>
              <input id="mensaje-asunto" class="form-control form-control-sm" type="text" maxlength="150" required>
            </div>
            <div class="mb-3">
              <label class="form-label small" for="mensaje-cuerpo">Mensaje</label>
              <textarea id="mensaje-cuerpo" class="form-control form-control-sm" rows="5" maxlength="4000" required></textarea>
            </div>
            <button id="btn-enviar-mensaje" type="submit" class="btn btn-primary-soscur btn-sm">Enviar mensaje</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
