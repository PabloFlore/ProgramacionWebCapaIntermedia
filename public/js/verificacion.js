(function () {
  'use strict';

  const api = window.SoscurApi;
  const botonReenviar = document.getElementById('btnReenviarVerificacion');
  const formCanjear = document.getElementById('formCanjearToken');
  const contenedor = document.getElementById('verificacion-alert');

  if (!api || (!botonReenviar && !formCanjear)) {
    return;
  }

  if (botonReenviar) {
    botonReenviar.addEventListener('click', async function () {
      api.clearAlert(contenedor);
      api.setLoading(botonReenviar, true, 'Enviando...');

      try {
        const respuesta = await api.request('/verificar-correo/reenviar', {
          method: 'POST',
          body: {}
        });
        const mensajes = [respuesta.mensaje || 'Correo de verificación reenviado.'];

        if (respuesta && respuesta.token_verificacion) {
          mensajes.push('Token de sandbox: ' + respuesta.token_verificacion);
        }

        api.showAlert(contenedor, 'success', mensajes);
      } catch (error) {
        api.showError(contenedor, error);
      } finally {
        api.setLoading(botonReenviar, false);
      }
    });
  }

  if (formCanjear) {
    formCanjear.addEventListener('submit', async function (event) {
      event.preventDefault();
      api.clearAlert(contenedor);

      const input = document.getElementById('tokenVerificacion');
      const token = input ? input.value.trim() : '';

      if (token === '') {
        api.showAlert(contenedor, 'danger', 'Escribe el token de verificación.');
        if (input) {
          input.focus();
        }
        return;
      }

      const boton = formCanjear.querySelector('button[type="submit"]');
      api.setLoading(boton, true, 'Verificando...');

      try {
        const respuesta = await api.request('/verificar-correo', {
          method: 'POST',
          body: { token: token }
        });
        api.showAlert(
          contenedor,
          'success',
          respuesta && respuesta.mensaje ? respuesta.mensaje : 'Correo verificado correctamente.'
        );
        formCanjear.reset();

        const aviso = document.getElementById('aviso-correo-no-verificado');
        if (aviso) {
          aviso.classList.add('d-none');
        }
      } catch (error) {
        api.showError(contenedor, error);
      } finally {
        api.setLoading(boton, false);
      }
    });
  }
})();
