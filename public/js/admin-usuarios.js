(function () {
  'use strict';

  const api = window.SoscurApi;
  const alertBox = document.getElementById('admin-alert');
  const forms = document.querySelectorAll('form[data-user-action]');

  if (!api || !alertBox || forms.length === 0) {
    return;
  }

  function actualizarFila(fila, bloqueado, motivo) {
    if (!fila) {
      return;
    }

    fila.dataset.bloqueado = bloqueado ? '1' : '0';

    const badge = fila.querySelector('.js-estado-badge');

    if (badge) {
      badge.className = 'badge js-estado-badge ' + (bloqueado ? 'bg-danger' : 'bg-success');
      badge.textContent = bloqueado ? 'Bloqueado' : 'Activo';
    }

    const motivoElemento = fila.querySelector('.js-motivo-bloqueo');

    if (motivoElemento) {
      motivoElemento.textContent = bloqueado ? motivo : '';
    }

    const accionesBloqueado = fila.querySelector('.js-acciones-bloqueado');
    const accionesActivo = fila.querySelector('.js-acciones-activo');

    if (accionesBloqueado) {
      accionesBloqueado.classList.toggle('d-none', !bloqueado);
    }

    if (accionesActivo) {
      accionesActivo.classList.toggle('d-none', bloqueado);
    }
  }

  forms.forEach(function (form) {
    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      api.clearAlert(alertBox);

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const accion = form.dataset.userAction;
      const usuarioId = form.dataset.usuarioId;
      const fila = form.closest('tr');
      const boton = form.querySelector('button[type="submit"]');
      const motivoInput = form.querySelector('input[name="motivo"]');
      const motivo = motivoInput ? motivoInput.value.trim() : '';

      if (accion === 'bloquear' && motivo === '') {
        api.showAlert(alertBox, 'danger', 'Escribe un motivo para bloquear al usuario.');

        if (motivoInput) {
          motivoInput.focus();
        }

        return;
      }

      const mensaje = accion === 'bloquear'
        ? '¿Bloquear al usuario con el motivo: ' + motivo + '?'
        : '¿Reactivar al usuario seleccionado?';

      let confirmado = true;

      if (typeof window.SoscurConfirm === 'function') {
        confirmado = await window.SoscurConfirm({
          titulo: accion === 'bloquear' ? 'Bloquear usuario' : 'Reactivar usuario',
          mensaje: mensaje,
          aceptar: accion === 'bloquear' ? 'Bloquear' : 'Reactivar',
          claseBoton: accion === 'bloquear' ? 'btn-danger' : 'btn-success'
        });
      } else {
        confirmado = window.confirm(mensaje);
      }

      if (!confirmado) {
        return;
      }

      api.setLoading(boton, true, 'Guardando...');

      try {
        const ruta = '/admin/usuarios/' + encodeURIComponent(usuarioId) + '/' + accion;
        const opciones = { method: 'POST' };

        if (accion === 'bloquear') {
          opciones.body = { motivo: motivo };
        }

        const respuesta = await api.request(ruta, opciones);

        actualizarFila(fila, accion === 'bloquear', motivo);

        if (motivoInput) {
          motivoInput.value = '';
        }

        api.showAlert(alertBox, 'success', respuesta && respuesta.mensaje
          ? respuesta.mensaje
          : 'Usuario actualizado correctamente.');
      } catch (error) {
        api.showError(alertBox, error);

        if (error && error.status === 401) {
          window.setTimeout(function () {
            api.redirect('login');
          }, 1200);
        }
      } finally {
        api.setLoading(boton, false);
      }
    });
  });
})();
