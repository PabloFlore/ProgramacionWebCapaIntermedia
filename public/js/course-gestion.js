(function () {
  'use strict';

  const api = window.SoscurApi;
  const root = document.getElementById('gestion-cursos');

  if (!api || !root) {
    return;
  }

  const elementos = {
    alerta: document.getElementById('gestion-alerta'),
    cargando: document.getElementById('gestion-cargando'),
    tabla: document.getElementById('gestion-tabla'),
    filas: document.getElementById('gestion-filas'),
    alertaModal: document.getElementById('modal-curso-alerta'),
    formCurso: document.getElementById('form-curso-gestion'),
    guardarCurso: document.getElementById('btn-guardar-curso'),
    categorias: document.getElementById('curso_categoria_gestion'),
    panelNiveles: document.getElementById('panel-niveles'),
    nivelesTitulo: document.getElementById('niveles-titulo'),
    nivelesAlerta: document.getElementById('niveles-alerta'),
    nivelesListado: document.getElementById('niveles-listado'),
    cerrarNiveles: document.getElementById('btn-cerrar-niveles'),
    guardarNivel: document.getElementById('btn-guardar-nivel'),
    nivelTitulo: document.getElementById('nivel_titulo'),
    nivelVideo: document.getElementById('nivel_video_url'),
    nivelOrden: document.getElementById('nivel_orden'),
    nivelPrecio: document.getElementById('nivel_precio'),
    nivelMaterialUrl: document.getElementById('nivel_material_url'),
    nivelMaterialTipo: document.getElementById('nivel_material_tipo')
  };

  let cursoActivo = null;

  function precio(valor) {
    return '$' + Number(valor || 0).toLocaleString('es-MX', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function boton(texto, clase, manejador) {
    const elemento = document.createElement('button');
    elemento.type = 'button';
    elemento.className = 'btn ' + clase + ' btn-sm';
    elemento.textContent = texto;
    elemento.addEventListener('click', manejador);
    return elemento;
  }

  async function cargarCategorias() {
    try {
      const respuesta = await api.request('/categorias');
      const categorias = respuesta && Array.isArray(respuesta.data) ? respuesta.data : [];
      elementos.categorias.innerHTML = '';

      categorias.forEach(function (categoria) {
        const opcion = document.createElement('option');
        opcion.value = String(categoria.categoria_id);
        opcion.textContent = categoria.nombre;
        elementos.categorias.appendChild(opcion);
      });
    } catch (error) {
      api.showError(elementos.alertaModal, error);
    }
  }

  async function cargarCursos() {
    elementos.cargando.classList.remove('d-none');
    elementos.tabla.classList.add('d-none');

    try {
      const respuesta = await api.request('/cursos/gestion?limit=50');
      const cursos = respuesta && Array.isArray(respuesta.data) ? respuesta.data : [];
      elementos.filas.innerHTML = '';

      if (cursos.length === 0) {
        const fila = document.createElement('tr');
        const celda = document.createElement('td');
        celda.colSpan = 6;
        celda.className = 'text-muted text-center py-4';
        celda.textContent = 'Aún no hay cursos registrados.';
        fila.appendChild(celda);
        elementos.filas.appendChild(fila);
      }

      cursos.forEach(function (curso) {
        const fila = document.createElement('tr');

        const id = document.createElement('td');
        id.textContent = String(curso.curso_id);

        const titulo = document.createElement('td');
        const enlace = document.createElement('a');
        enlace.href = api.asset('curso/' + Number(curso.curso_id));
        enlace.textContent = curso.titulo;
        titulo.appendChild(enlace);

        const categoria = document.createElement('td');
        categoria.textContent = curso.categoria_nombre;

        const monto = document.createElement('td');
        monto.textContent = precio(curso.precio);

        const estatus = document.createElement('td');
        const badge = document.createElement('span');
        badge.className = 'badge ' + (curso.estatus === 'activo' ? 'bg-success' : 'bg-secondary');
        badge.textContent = curso.estatus;
        estatus.appendChild(badge);

        const acciones = document.createElement('td');
        acciones.className = 'text-end';
        const grupo = document.createElement('div');
        grupo.className = 'd-flex justify-content-end gap-1 flex-wrap';
        grupo.appendChild(boton('Niveles', 'btn-outline-secondary', function () {
          abrirNiveles(Number(curso.curso_id));
        }));
        grupo.appendChild(boton(
          curso.estatus === 'activo' ? 'Dar de baja' : 'Reactivar',
          curso.estatus === 'activo' ? 'btn-outline-danger' : 'btn-outline-success',
          function () {
            cambiarEstatus(curso);
          }
        ));
        acciones.appendChild(grupo);

        fila.appendChild(id);
        fila.appendChild(titulo);
        fila.appendChild(categoria);
        fila.appendChild(monto);
        fila.appendChild(estatus);
        fila.appendChild(acciones);
        elementos.filas.appendChild(fila);
      });

      elementos.cargando.classList.add('d-none');
      elementos.tabla.classList.remove('d-none');
    } catch (error) {
      elementos.cargando.classList.add('d-none');
      api.showError(elementos.alerta, error);
    }
  }

  async function crearCurso(evento) {
    evento.preventDefault();
    api.clearAlert(elementos.alertaModal);

    const cuerpo = {
      titulo: document.getElementById('curso_titulo_gestion').value.trim(),
      descripcion: document.getElementById('curso_descripcion_gestion').value.trim(),
      categoria_id: Number(elementos.categorias.value),
      precio: Number(document.getElementById('curso_precio_gestion').value || 0),
      tipo_precio: document.getElementById('curso_tipo_gestion').value
    };

    api.setLoading(elementos.guardarCurso, true, 'Guardando...');

    try {
      const respuesta = await api.request('/cursos', { method: 'POST', body: cuerpo });
      api.showAlert(elementos.alerta, 'success', respuesta.message || respuesta.mensaje);
      elementos.formCurso.reset();
      await cargarCursos();
    } catch (error) {
      api.showError(elementos.alertaModal, error);
    } finally {
      api.setLoading(elementos.guardarCurso, false);
    }
  }

  async function cambiarEstatus(curso) {
    const accion = curso.estatus === 'activo' ? 'baja' : 'reactivar';
    const confirmado = window.SoscurConfirm
      ? await window.SoscurConfirm({
        titulo: accion === 'baja' ? 'Dar de baja curso' : 'Reactivar curso',
        mensaje: accion === 'baja'
          ? 'El curso dejará de mostrarse en el catálogo. ¿Continuar?'
          : 'El curso volverá a mostrarse en el catálogo. ¿Continuar?',
        aceptar: accion === 'baja' ? 'Dar de baja' : 'Reactivar',
        claseBoton: accion === 'baja' ? 'btn-danger' : 'btn-success'
      })
      : window.confirm('¿Deseas continuar?');

    if (!confirmado) {
      return;
    }

    try {
      const respuesta = await api.request('/cursos/' + Number(curso.curso_id) + '/' + accion, { method: 'POST' });
      api.showAlert(elementos.alerta, 'success', respuesta.message);
      await cargarCursos();
    } catch (error) {
      api.showError(elementos.alerta, error);
    }
  }

  async function abrirNiveles(cursoId) {
    cursoActivo = cursoId;
    elementos.panelNiveles.classList.remove('d-none');
    elementos.nivelesTitulo.textContent = 'Niveles del curso #' + cursoId;
    api.clearAlert(elementos.nivelesAlerta);
    await cargarNiveles();
    elementos.panelNiveles.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  async function cargarNiveles() {
    if (cursoActivo === null) {
      return;
    }

    try {
      const respuesta = await api.request('/cursos/' + cursoActivo);
      const niveles = respuesta && respuesta.data && Array.isArray(respuesta.data.niveles)
        ? respuesta.data.niveles
        : [];
      elementos.nivelesListado.innerHTML = '';

      if (niveles.length === 0) {
        const mensaje = document.createElement('p');
        mensaje.className = 'text-muted small mb-0';
        mensaje.textContent = 'Este curso aún no tiene niveles.';
        elementos.nivelesListado.appendChild(mensaje);
        return;
      }

      niveles.forEach(function (nivel) {
        const fila = document.createElement('div');
        fila.className = 'list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2 px-0';

        const texto = document.createElement('span');
        texto.textContent = '[' + nivel.orden + '] ' + nivel.titulo;

        const eliminar = boton('Eliminar', 'btn-outline-danger', function () {
          eliminarNivel(nivel);
        });
        fila.appendChild(texto);
        fila.appendChild(eliminar);
        elementos.nivelesListado.appendChild(fila);
      });
    } catch (error) {
      api.showError(elementos.nivelesAlerta, error);
    }
  }

  async function crearNivel() {
    if (cursoActivo === null) {
      return;
    }

    const cuerpo = {
      titulo: elementos.nivelTitulo.value.trim(),
      video_url: elementos.nivelVideo.value.trim(),
      orden: Number(elementos.nivelOrden.value),
      precio: elementos.nivelPrecio.value === '' ? null : Number(elementos.nivelPrecio.value),
      material_url: elementos.nivelMaterialUrl.value.trim() || null,
      material_tipo: elementos.nivelMaterialTipo.value || null
    };

    api.clearAlert(elementos.nivelesAlerta);
    api.setLoading(elementos.guardarNivel, true, 'Guardando...');

    try {
      const respuesta = await api.request('/cursos/' + cursoActivo + '/niveles', { method: 'POST', body: cuerpo });
      api.showAlert(elementos.nivelesAlerta, 'success', respuesta.message);
      elementos.nivelTitulo.value = '';
      elementos.nivelVideo.value = '';
      elementos.nivelOrden.value = String(Number(elementos.nivelOrden.value) + 1);
      elementos.nivelPrecio.value = '';
      elementos.nivelMaterialUrl.value = '';
      elementos.nivelMaterialTipo.value = '';
      await cargarNiveles();
    } catch (error) {
      api.showError(elementos.nivelesAlerta, error);
    } finally {
      api.setLoading(elementos.guardarNivel, false);
    }
  }

  async function eliminarNivel(nivel) {
    const confirmado = window.SoscurConfirm
      ? await window.SoscurConfirm({
        titulo: 'Eliminar nivel',
        mensaje: '¿Eliminar el nivel "' + nivel.titulo + '"? Esta acción no se puede deshacer.',
        aceptar: 'Eliminar'
      })
      : window.confirm('¿Eliminar el nivel?');

    if (!confirmado) {
      return;
    }

    try {
      const respuesta = await api.request('/niveles/' + Number(nivel.nivel_id), { method: 'DELETE' });
      api.showAlert(elementos.nivelesAlerta, 'success', respuesta.message);
      await cargarNiveles();
    } catch (error) {
      api.showError(elementos.nivelesAlerta, error);
    }
  }

  elementos.formCurso.addEventListener('submit', crearCurso);
  elementos.guardarNivel.addEventListener('click', crearNivel);
  elementos.cerrarNiveles.addEventListener('click', function () {
    elementos.panelNiveles.classList.add('d-none');
    cursoActivo = null;
  });

  cargarCategorias();
  cargarCursos();
})();
