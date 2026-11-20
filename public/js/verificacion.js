(function () {
  'use strict';

  const api = window.SoscurApi;
  const boton = document.getElementById('btnReenviarVerificacion');

  if (!api || !boton) {
    return;
  }

  const contenedor = document.getElementById('verificacion-alert');

  boton.addEventListener('click', async function () {
    api.clearAlert(contenedor);
    api.setLoading(boton, true, 'Enviando...');

    try {
      const respuesta = await api.request('/verificar-correo/reenviar', {
        method: 'POST',
        body: {}
      });
      api.showAlert(contenedor, 'success', respuesta.mensaje || 'Correo de verificación reenviado.');
    } catch (error) {
      api.showError(contenedor, error);
    } finally {
      api.setLoading(boton, false);
    }
  });
})();
