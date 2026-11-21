(function () {
  'use strict';

  const api = window.SoscurApi;
  const tabla = document.getElementById('admin-tabla');
  const tbody = document.getElementById('usuarios-tbody');

  if (!api || !tabla || !tbody) {
    return;
  }

  const alertBox = document.getElementById('admin-alert');
  const resumen = document.getElementById('admin-resumen');
  const paginacion = document.getElementById('paginacion-usuarios');
  const formFiltros = document.getElementById('formFiltrosUsuarios');
  const inputQ = document.getElementById('q');
  const selectLimite = document.getElementById('limite');
  const adminId = Number(tabla.dataset.adminId) || 0;

  const estado = {
    q: inputQ ? inputQ.value.trim() : '',
    rol: document.getElementById('rol') ? document.getElementById('rol').value : '',
    bloqueado: document.getElementById('bloqueado') ? document.getElementById('bloqueado').value : '',
    orden: tabla.dataset.orden || 'usuario_id',
    direccion: tabla.dataset.direccion || 'desc',
    pagina: Number(tabla.dataset.pagina) || 1,
    limite: Number(tabla.dataset.limite) || 25
  };

  let temporizadorBusqueda = null;

  function escapeHtml(valor) {
    return String(valor === null || valor === undefined ? '' : valor).replace(/[&<>"']/g, function (caracter) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      }[caracter];
    });
  }

  function formatearFecha(valor) {
    const coincidencia = String(valor || '').match(/^(\d{4})-(\d{2})-(\d{2})/);

    if (!coincidencia) {
      return escapeHtml(valor);
    }

    return coincidencia[3] + '/' + coincidencia[2] + '/' + coincidencia[1];
  }

  function acciones(usuario) {
    const id = Number(usuario.usuario_id);

    if (id === adminId) {
      return '<span class="text-muted small">Tu cuenta</span>';
    }

    const bloqueado = Number(usuario.bloqueado) === 1;
    const urlBase = api.asset('admin/usuarios/' + id);

    return ''
      + '<div class="js-acciones-bloqueado ' + (bloqueado ? '' : 'd-none') + '">'
      + '<form method="post" action="' + escapeHtml(urlBase + '/reactivar') + '" data-user-action="reactivar" data-usuario-id="' + id + '">'
      + '<button class="btn btn-sm btn-outline-success" type="submit">Reactivar</button>'
      + '</form>'
      + '</div>'
      + '<div class="js-acciones-activo ' + (bloqueado ? 'd-none' : '') + '">'
      + '<form method="post" action="' + escapeHtml(urlBase + '/bloquear') + '" class="d-flex justify-content-end gap-1" data-user-action="bloquear" data-usuario-id="' + id + '">'
      + '<input name="motivo" class="form-control form-control-sm" style="max-width: 180px;" placeholder="Motivo" maxlength="255" required>'
      + '<button class="btn btn-sm btn-outline-danger" type="submit">Bloquear</button>'
      + '</form>'
      + '</div>';
  }

  function renderFilas(usuarios) {
    if (!Array.isArray(usuarios) || usuarios.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No hay usuarios para este filtro.</td></tr>';
      return;
    }

    tbody.innerHTML = usuarios.map(function (usuario) {
      const bloqueado = Number(usuario.bloqueado) === 1;
      const motivo = bloqueado && usuario.motivo_bloqueo ? usuario.motivo_bloqueo : '';

      return ''
        + '<tr data-usuario-id="' + Number(usuario.usuario_id) + '" data-bloqueado="' + (bloqueado ? '1' : '0') + '">'
        + '<td>'
        + '<div class="fw-semibold">' + escapeHtml(usuario.nombre) + '</div>'
        + '<div class="text-muted small">' + escapeHtml(usuario.correo) + '</div>'
        + '</td>'
        + '<td><span class="badge bg-secondary text-uppercase">' + escapeHtml(String(usuario.rol || '').toUpperCase()) + '</span></td>'
        + '<td>'
        + '<span class="badge js-estado-badge ' + (bloqueado ? 'bg-danger' : 'bg-success') + '">' + (bloqueado ? 'Bloqueado' : 'Activo') + '</span>'
        + '<div class="js-motivo-bloqueo text-muted small">' + escapeHtml(motivo) + '</div>'
        + '</td>'
        + '<td class="small text-muted">' + formatearFecha(usuario.fecha_registro) + '</td>'
        + '<td class="text-end">' + acciones(usuario) + '</td>'
        + '</tr>';
    }).join('');
  }

  function actualizarIndicadores() {
    document.querySelectorAll('[data-orden]').forEach(function (boton) {
      const indicador = boton.querySelector('.js-orden-indicador');

      if (!indicador) {
        return;
      }

      indicador.textContent = estado.orden === boton.dataset.orden
        ? (estado.direccion === 'asc' ? '↑' : '↓')
        : '';
    });
  }

  function actualizarPie(respuesta) {
    const total = Number(respuesta.total) || 0;
    const paginas = Number(respuesta.paginas) || 0;
    const pagina = Number(respuesta.pagina) || 1;
    const limite = Number(respuesta.limite) || estado.limite;
    const filas = Array.isArray(respuesta.data) ? respuesta.data.length : 0;
    const desde = total === 0 ? 0 : ((pagina - 1) * limite) + 1;
    const hasta = desde === 0 ? 0 : desde + filas - 1;
    const inicio = Math.max(1, pagina - 2);
    const fin = Math.min(Math.max(1, paginas), inicio + 4);
    const inicioAjustado = Math.max(1, fin - 4);

    if (resumen) {
      resumen.textContent = 'Mostrando ' + desde + ' a ' + hasta + ' de ' + total + ' usuarios';
    }

    if (!paginacion) {
      return;
    }

    let html = '<ul class="pagination pagination-sm mb-0">';
    html += '<li class="page-item ' + (pagina <= 1 ? 'disabled' : '') + '">'
      + '<button class="page-link" type="button" data-pagina="' + Math.max(1, pagina - 1) + '">Anterior</button>'
      + '</li>';

    for (let actual = inicioAjustado; actual <= fin; actual++) {
      html += '<li class="page-item ' + (actual === pagina ? 'active' : '') + '">'
        + '<button class="page-link" type="button" data-pagina="' + actual + '">' + actual + '</button>'
        + '</li>';
    }

    html += '<li class="page-item ' + (paginas === 0 || pagina >= paginas ? 'disabled' : '') + '">'
      + '<button class="page-link" type="button" data-pagina="' + Math.min(Math.max(1, paginas), pagina + 1) + '">Siguiente</button>'
      + '</li>';
    html += '</ul>';

    paginacion.innerHTML = html;
  }

  function construirConsulta() {
    const parametros = new URLSearchParams();

    if (estado.q !== '') {
      parametros.set('q', estado.q);
    }

    if (estado.rol !== '') {
      parametros.set('rol', estado.rol);
    }

    if (estado.bloqueado !== '') {
      parametros.set('bloqueado', estado.bloqueado);
    }

    parametros.set('orden', estado.orden);
    parametros.set('direccion', estado.direccion);
    parametros.set('pagina', String(estado.pagina));
    parametros.set('limite', String(estado.limite));

    return parametros.toString();
  }

  async function cargar(pagina) {
    estado.pagina = Math.max(1, Number(pagina) || 1);

    try {
      const respuesta = await api.request('/admin/usuarios?' + construirConsulta());
      renderFilas(respuesta.data);
      actualizarPie(respuesta);
      tabla.dataset.pagina = String(estado.pagina);

      const campoPagina = formFiltros ? formFiltros.querySelector('input[name="pagina"]') : null;
      if (campoPagina) {
        campoPagina.value = String(estado.pagina);
      }

      actualizarIndicadores();
    } catch (error) {
      api.showError(alertBox, error);

      if (error && error.status === 401) {
        window.setTimeout(function () {
          api.redirect('login');
        }, 1200);
      }
    }
  }

  function sincronizarFiltros() {
    estado.q = inputQ ? inputQ.value.trim() : '';
    estado.rol = document.getElementById('rol') ? document.getElementById('rol').value : '';
    estado.bloqueado = document.getElementById('bloqueado') ? document.getElementById('bloqueado').value : '';
    estado.limite = selectLimite ? Number(selectLimite.value) || 25 : estado.limite;
  }

  if (formFiltros) {
    formFiltros.addEventListener('submit', function (event) {
      event.preventDefault();
      api.clearAlert(alertBox);
      sincronizarFiltros();
      cargar(1);
    });
  }

  if (inputQ) {
    inputQ.addEventListener('input', function () {
      window.clearTimeout(temporizadorBusqueda);
      temporizadorBusqueda = window.setTimeout(function () {
        sincronizarFiltros();
        cargar(1);
      }, 350);
    });
  }

  if (selectLimite) {
    selectLimite.addEventListener('change', function () {
      sincronizarFiltros();
      cargar(1);
    });
  }

  document.querySelectorAll('[data-orden]').forEach(function (boton) {
    boton.addEventListener('click', function () {
      const campo = boton.dataset.orden;

      if (estado.orden === campo) {
        estado.direccion = estado.direccion === 'asc' ? 'desc' : 'asc';
      } else {
        estado.orden = campo;
        estado.direccion = 'asc';
      }

      actualizarIndicadores();
      cargar(1);
    });
  });

  if (paginacion) {
    paginacion.addEventListener('click', function (event) {
      const boton = event.target.closest('button[data-pagina]');

      if (!boton || boton.disabled) {
        return;
      }

      cargar(boton.dataset.pagina);
    });
  }

  tbody.addEventListener('submit', async function (event) {
    const form = event.target.closest('form[data-user-action]');

    if (!form) {
      return;
    }

    event.preventDefault();
    api.clearAlert(alertBox);

    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const accion = form.dataset.userAction;
    const usuarioId = form.dataset.usuarioId;
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
      const opciones = { method: 'POST' };

      if (accion === 'bloquear') {
        opciones.body = { motivo: motivo };
      }

      const respuesta = await api.request('/admin/usuarios/' + encodeURIComponent(usuarioId) + '/' + accion, opciones);
      await cargar(estado.pagina);
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

  actualizarIndicadores();
})();
