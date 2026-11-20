(function () {
  'use strict';

  const api = window.SoscurApi;
  const form = document.getElementById('formLogout');

  if (!api || !form) {
    return;
  }

  const alertBox = document.getElementById('logout-alert');
  const boton = form.querySelector('button[type="submit"]');

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    api.clearAlert(alertBox);
    api.setLoading(boton, true, 'Saliendo...');

    try {
      await api.request('/logout', { method: 'POST', body: {} });
      api.redirect('login');
    } catch (error) {
      api.showError(alertBox, error);

      if (error && (error.status === 401 || error.status === 403)) {
        window.setTimeout(function () {
          api.redirect('login');
        }, 1200);
      }
    } finally {
      api.setLoading(boton, false);
    }
  });
})();
