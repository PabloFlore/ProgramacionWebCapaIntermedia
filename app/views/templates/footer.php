<?php

use App\Helpers\Url;

$scripts = $pageScripts ?? ($pageScript ?? []);
$scripts = is_array($scripts) ? $scripts : [$scripts];
?>
</main>
<footer class="container py-4 text-muted small">
  SOSCUR Portal — Programación Web Capa Intermedia
</footer>
<div class="modal fade" id="soscurConfirmModal" tabindex="-1" aria-labelledby="soscurConfirmTitulo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="soscurConfirmTitulo">Confirmar acción</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0" id="soscurConfirmMensaje"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="soscurConfirmCancelar">Cancelar</button>
        <button type="button" class="btn btn-danger" id="soscurConfirmAceptar">Confirmar</button>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  'use strict';

  const elemento = document.getElementById('soscurConfirmModal');

  if (!elemento || !window.bootstrap) {
    return;
  }

  const modal = new bootstrap.Modal(elemento);
  const titulo = document.getElementById('soscurConfirmTitulo');
  const mensaje = document.getElementById('soscurConfirmMensaje');
  const botonAceptar = document.getElementById('soscurConfirmAceptar');
  const botonCancelar = document.getElementById('soscurConfirmCancelar');
  let resolver = null;

  function cerrar(resultado) {
    if (resolver !== null) {
      resolver(resultado);
      resolver = null;
    }

    modal.hide();
  }

  botonAceptar.addEventListener('click', function () {
    cerrar(true);
  });
  botonCancelar.addEventListener('click', function () {
    cerrar(false);
  });
  elemento.addEventListener('hidden.bs.modal', function () {
    cerrar(false);
  });

  window.SoscurConfirm = function (opciones) {
    const config = opciones || {};

    titulo.textContent = config.titulo || 'Confirmar acción';
    mensaje.textContent = config.mensaje || '¿Deseas continuar?';
    botonAceptar.textContent = config.aceptar || 'Confirmar';
    botonAceptar.className = 'btn ' + (config.claseBoton || 'btn-danger');

    return new Promise(function (resolve) {
      resolver = resolve;
      modal.show();
    });
  };
})();
</script>
<script src="<?= htmlspecialchars(Url::asset('js/api.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars(Url::asset('js/verificacion.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php foreach ($scripts as $script): ?>
  <?php if (is_string($script) && $script !== ''): ?>
    <script src="<?= htmlspecialchars(Url::asset($script), ENT_QUOTES, 'UTF-8') ?>"></script>
  <?php endif; ?>
<?php endforeach; ?>
</body>
</html>
