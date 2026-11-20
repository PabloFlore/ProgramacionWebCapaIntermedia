(function () {
  'use strict';

  const alerta = document.getElementById('mensajes-alerta');
  const formulario = document.getElementById('form-mensaje');

  document.addEventListener('click', function (evento) {
    const boton = evento.target.closest('.js-marcar-leido');

    if (!boton) {
      return;
    }

    window.SoscurApi.setLoading(boton, true, '...');

    window.SoscurApi.request('/mensajes/' + boton.dataset.id + '/leido', { method: 'PATCH' })
      .then(function () {
        window.location.reload();
      })
      .catch(function (error) {
        window.SoscurApi.showError(alerta, error);
      })
      .finally(function () {
        window.SoscurApi.setLoading(boton, false);
      });
  });

  if (formulario) {
    const boton = document.getElementById('btn-enviar-mensaje');

    formulario.addEventListener('submit', function (evento) {
      evento.preventDefault();

      const datos = {
        destinatario_id: Number(document.getElementById('mensaje-destinatario').value),
        curso_id: document.getElementById('mensaje-curso').value,
        asunto: document.getElementById('mensaje-asunto').value,
        cuerpo: document.getElementById('mensaje-cuerpo').value
      };

      window.SoscurApi.setLoading(boton, true, 'Enviando...');

      window.SoscurApi.request('/mensajes', { method: 'POST', body: datos })
        .then(function () {
          window.location.reload();
        })
        .catch(function (error) {
          window.SoscurApi.showError(alerta, error);
        })
        .finally(function () {
          window.SoscurApi.setLoading(boton, false);
        });
    });
  }
})();
