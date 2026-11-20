(function () {
  'use strict';

  const api = window.SoscurApi;
  const form = document.getElementById('form-verificar-diploma');

  if (!api || !form) {
    return;
  }

  const elementos = {
    alerta: document.getElementById('verificar-alerta'),
    codigo: document.getElementById('codigo-diploma'),
    boton: document.getElementById('btn-verificar-diploma'),
    resultado: document.getElementById('resultado-diploma'),
    estudiante: document.getElementById('resultado-estudiante'),
    curso: document.getElementById('resultado-curso'),
    fecha: document.getElementById('resultado-fecha')
  };

  function formatearFecha(valor) {
    const partes = String(valor || '').substring(0, 10).split('-');

    if (partes.length !== 3) {
      return String(valor || '');
    }

    return partes[2] + '/' + partes[1] + '/' + partes[0];
  }

  async function verificar(evento) {
    evento.preventDefault();
    api.clearAlert(elementos.alerta);
    elementos.resultado.classList.add('d-none');

    const codigo = elementos.codigo.value.trim();

    if (codigo === '') {
      api.showAlert(elementos.alerta, 'warning', 'Ingresa un código de verificación.');
      return;
    }

    api.setLoading(elementos.boton, true, 'Verificando...');

    try {
      const parametros = new URLSearchParams({ codigo: codigo });
      const respuesta = await api.request('/diplomas/verificar?' + parametros.toString());
      const diploma = respuesta.data;

      elementos.estudiante.textContent = diploma.estudiante_nombre;
      elementos.curso.textContent = diploma.curso_titulo;
      elementos.fecha.textContent = formatearFecha(diploma.fecha_emision);
      elementos.resultado.classList.remove('d-none');
    } catch (error) {
      api.showError(elementos.alerta, error);
    } finally {
      api.setLoading(elementos.boton, false);
    }
  }

  form.addEventListener('submit', verificar);
})();
