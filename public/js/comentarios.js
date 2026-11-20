(function () {
  'use strict';

  const contenedor = document.getElementById('curso-detalle');
  const lista = document.getElementById('comentarios-lista');
  const resumen = document.getElementById('comentarios-promedio');
  const total = document.getElementById('comentarios-total');
  const alerta = document.getElementById('comentarios-alerta');
  const formulario = document.getElementById('form-comentario');

  if (!contenedor || !lista) {
    return;
  }

  const cursoId = contenedor.dataset.cursoId;

  function fechaLegible(valor) {
    if (!valor) {
      return '—';
    }

    const partes = String(valor).slice(0, 10).split('-');

    return partes.length === 3 ? partes[2] + '/' + partes[1] + '/' + partes[0] : '—';
  }

  function promedioLegible(valor) {
    if (valor === null || valor === undefined || valor === '') {
      return 'Sin calificaciones';
    }

    return Number(valor).toFixed(1).replace('.', ',') + '/10';
  }

  function pintar(payload) {
    lista.innerHTML = '';

    if (resumen) {
      resumen.textContent = promedioLegible(payload.resumen && payload.resumen.calificacion_promedio);
    }

    if (total) {
      const cantidad = payload.resumen ? Number(payload.resumen.total) : 0;
      total.textContent = cantidad + ' comentario(s) publicado(s).';
    }

    const comentarios = Array.isArray(payload.data) ? payload.data : [];

    if (comentarios.length === 0) {
      const mensaje = document.createElement('p');
      mensaje.className = 'text-muted small mb-0';
      mensaje.textContent = 'Todavía no hay comentarios publicados.';
      lista.appendChild(mensaje);
      return;
    }

    comentarios.forEach(function (comentario) {
      const fila = document.createElement('div');
      fila.className = 'list-group-item px-0';

      const cabecera = document.createElement('div');
      cabecera.className = 'd-flex flex-wrap justify-content-between align-items-center gap-2';

      const autor = document.createElement('strong');
      autor.className = 'small';
      autor.textContent = String(comentario.estudiante_nombre || 'Estudiante');

      const nota = document.createElement('span');
      nota.className = 'badge bg-warning text-dark';
      nota.textContent = Number(comentario.calificacion) + '/10';

      cabecera.appendChild(autor);
      cabecera.appendChild(nota);

      const texto = document.createElement('p');
      texto.className = 'small text-muted mt-2 mb-1';
      texto.textContent = comentario.comentario ? String(comentario.comentario) : 'Sin texto adicional.';

      const fecha = document.createElement('div');
      fecha.className = 'text-muted small';
      fecha.textContent = fechaLegible(comentario.fecha_comentario);

      fila.appendChild(cabecera);
      fila.appendChild(texto);
      fila.appendChild(fecha);
      lista.appendChild(fila);
    });
  }

  function cargar() {
    window.SoscurApi.request('/cursos/' + cursoId + '/comentarios')
      .then(function (payload) {
        pintar(payload);
      })
      .catch(function (error) {
        window.SoscurApi.showError(alerta, error);
      });
  }

  if (formulario) {
    const boton = document.getElementById('btn-publicar-comentario');
    const campoNota = document.getElementById('comentario-calificacion');
    const campoTexto = document.getElementById('comentario-texto');
    const alertaFormulario = document.getElementById('comentario-alerta');

    formulario.addEventListener('submit', function (evento) {
      evento.preventDefault();
      window.SoscurApi.clearAlert(alertaFormulario);
      window.SoscurApi.setLoading(boton, true, 'Publicando...');

      window.SoscurApi.request('/cursos/' + cursoId + '/comentarios', {
        method: 'POST',
        body: {
          calificacion: Number(campoNota.value),
          comentario: campoTexto.value
        }
      })
        .then(function (payload) {
          formulario.reset();
          if (payload.resumen) {
            if (resumen) {
              resumen.textContent = promedioLegible(payload.resumen.calificacion_promedio);
            }
            if (total) {
              total.textContent = Number(payload.resumen.total) + ' comentario(s) publicado(s).';
            }
          }
          cargar();
        })
        .catch(function (error) {
          window.SoscurApi.showError(alertaFormulario, error);
        })
        .finally(function () {
          window.SoscurApi.setLoading(boton, false);
        });
    });
  }

  cargar();
})();
