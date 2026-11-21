(function () {
  'use strict';

  const alerta = document.getElementById('moderacion-alerta');
  const modalElemento = document.getElementById('modalModeracion');
  const formulario = document.getElementById('formModeracion');
  const campoId = document.getElementById('moderacion_id');
  const campoCausa = document.getElementById('moderacion_causa');
  const detalle = document.getElementById('moderacion_detalle');
  const botonModerar = document.getElementById('btnModerar');
  const alertaModal = document.getElementById('moderacion-modal-alerta');
  const modal = window.bootstrap && modalElemento ? new window.bootstrap.Modal(modalElemento) : null;

  document.addEventListener('click', function (evento) {
    const boton = evento.target.closest('.js-moderar-comentario');

    if (!boton) {
      return;
    }

    window.SoscurApi.clearAlert(alertaModal);
    formulario.reset();
    campoId.value = boton.dataset.id;
    detalle.textContent = 'Curso: ' + boton.dataset.curso + ' · Estudiante: ' + boton.dataset.estudiante;
    modal.show();
  });

  if (formulario) {
    formulario.addEventListener('submit', function (evento) {
      evento.preventDefault();
      const causa = campoCausa.value.trim();

      if (causa === '') {
        window.SoscurApi.showAlert(alertaModal, 'danger', 'La causa de eliminación es obligatoria.');
        return;
      }

      window.SoscurConfirm({
        titulo: 'Confirmar moderación',
        mensaje: '¿Eliminar el comentario indicando la causa?',
        aceptar: 'Eliminar'
      }).then(function (confirmado) {
        if (!confirmado) {
          return;
        }

        window.SoscurApi.setLoading(botonModerar, true, 'Eliminando...');

        window.SoscurApi.request('/admin/comentarios/' + campoId.value + '/eliminar', {
          method: 'POST',
          body: { causa_eliminacion: causa }
        })
          .then(function () {
            window.location.reload();
          })
          .catch(function (error) {
            window.SoscurApi.showError(alertaModal, error);
          })
          .finally(function () {
            window.SoscurApi.setLoading(botonModerar, false);
          });
      });
    });
  }
})();
