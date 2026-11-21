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
    modal: document.getElementById('modalCursoGestion'),
    modalTitulo: document.getElementById('modalCursoGestionTitulo'),
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
  let cursoEditandoId = null;
  let nivelEditandoId = null;

  function precio(valor) {
    return '$' + Number(valor || 0).toLocaleString('es-MX', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }) + ' MXN';
  }

  function boton(texto, clase, manejador) {
    const elemento = document.createElement('button');
    elemento.type = 'button';
    elemento.className = 'btn ' + clase + ' btn-sm';
    elemento.textContent = texto;
    elemento.addEventListener('click', manejador);
    return elemento;
  }

  function resetCursoForm() {
    cursoEditandoId = null;
    elementos.formCurso.reset();
    elementos.modalTitulo.textContent = 'Nuevo curso';
    elementos.guardarCurso.textContent = 'Guardar';
    api.clearAlert(elementos.alertaModal);

    if (elementos.categorias.options.length > 0) {
      elementos.categorias.selectedIndex = 0;
    }
  }

  function resetNivelForm() {
    nivelEditandoId = null;
    elementos.nivelTitulo.value = '';
    elementos.nivelVideo.value = '';
    elementos.nivelOrden.value = '1';
    elementos.nivelPrecio.value = '';
    elementos.nivelMaterialUrl.value = '';
    elementos.nivelMaterialTipo.value = '';
    elementos.guardarNivel.textContent = 'Agregar nivel';
    api.clearAlert(elementos.nivelesAlerta);
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

        const editar = boton('Editar', 'btn-outline-primary', function () {
          editarCurso(Number(curso.curso_id));
        });
        editar.setAttribute('data-bs-toggle', 'modal');
        editar.setAttribute('data-bs-target', '#modalCursoGestion');
        grupo.appendChild(editar);

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

  async function editarCurso(cursoId) {
    api.clearAlert(elementos.alertaModal);

    try {
      const respuesta = await api.request('/cursos/gestion/' + cursoId);
      const curso = respuesta && respuesta.curso ? respuesta.curso : null;

      if (!curso) {
        throw new Error('No se pudo cargar el curso solicitado.');
      }

      cursoEditandoId = Number(curso.curso_id);
      document.getElementById('curso_titulo_gestion').value = curso.titulo || '';
      document.getElementById('curso_descripcion_gestion').value = curso.descripcion || '';
      elementos.categorias.value = String(curso.categoria_id);
      document.getElementById('curso_precio_gestion').value = Number(curso.precio || 0).toFixed(2);
      document.getElementById('curso_tipo_gestion').value = curso.tipo_precio || 'curso';
      elementos.modalTitulo.textContent = 'Editar curso';
      elementos.guardarCurso.textContent = 'Actualizar';
    } catch (error) {
      api.showError(elementos.alertaModal, error);
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

    const editando = cursoEditandoId !== null;
    const url = editando ? '/cursos/' + cursoEditandoId : '/cursos';

    api.setLoading(elementos.guardarCurso, true, 'Guardando...');

    try {
      const respuesta = await api.request(url, { method: editando ? 'PATCH' : 'POST', body: cuerpo });
      api.showAlert(elementos.alerta, 'success', respuesta.message || respuesta.mensaje);
      resetCursoForm();
      await cargarCursos();
    } catch (error) {
      api.showError(elementos.alertaModal, error);
    } finally {
      api.setLoading(elementos.guardarCurso, false, editando ? 'Actualizar' : 'Guardar');
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
    resetNivelForm();
    await cargarNiveles();
    elementos.panelNiveles.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  async function cargarNiveles() {
    if (cursoActivo === null) {
      return;
    }

    try {
      const respuesta = await api.request('/cursos/gestion/' + cursoActivo);
      const niveles = respuesta && respuesta.curso && Array.isArray(respuesta.curso.niveles)
        ? respuesta.curso.niveles
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

        const acciones = document.createElement('div');
        acciones.className = 'd-flex gap-2';
        acciones.appendChild(boton('Editar', 'btn-outline-primary', function () {
          editarNivel(nivel);
        }));
        acciones.appendChild(boton('Eliminar', 'btn-outline-danger', function () {
          eliminarNivel(nivel);
        }));

        fila.appendChild(texto);
        fila.appendChild(acciones);
        elementos.nivelesListado.appendChild(fila);
      });
    } catch (error) {
      api.showError(elementos.nivelesAlerta, error);
    }
  }

  function editarNivel(nivel) {
    nivelEditandoId = Number(nivel.nivel_id);
    elementos.nivelTitulo.value = nivel.titulo || '';
    elementos.nivelVideo.value = nivel.video_url || '';
    elementos.nivelOrden.value = String(nivel.orden || 1);
    elementos.nivelPrecio.value = nivel.precio === null || nivel.precio === undefined ? '' : Number(nivel.precio).toFixed(2);
    elementos.nivelMaterialUrl.value = nivel.material_url || '';
    elementos.nivelMaterialTipo.value = nivel.material_tipo || '';
    elementos.guardarNivel.textContent = 'Actualizar nivel';
    api.clearAlert(elementos.nivelesAlerta);
    elementos.nivelTitulo.focus();
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

    const editando = nivelEditandoId !== null;
    const url = editando ? '/niveles/' + nivelEditandoId : '/cursos/' + cursoActivo + '/niveles';

    api.clearAlert(elementos.nivelesAlerta);
    api.setLoading(elementos.guardarNivel, true, 'Guardando...');

    try {
      const respuesta = await api.request(url, { method: editando ? 'PATCH' : 'POST', body: cuerpo });
      api.showAlert(elementos.nivelesAlerta, 'success', respuesta.message);
      const siguienteOrden = Number(elementos.nivelOrden.value || 0) + 1;
      resetNivelForm();
      elementos.nivelOrden.value = String(siguienteOrden);
      await cargarNiveles();
    } catch (error) {
      api.showError(elementos.nivelesAlerta, error);
    } finally {
      api.setLoading(elementos.guardarNivel, false, editando ? 'Actualizar nivel' : 'Agregar nivel');
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
    resetNivelForm();
  });
  elementos.modal.addEventListener('hidden.bs.modal', resetCursoForm);

  cargarCategorias();
  cargarCursos();
})();
