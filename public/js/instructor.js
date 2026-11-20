(function () {
  'use strict';

  const api = window.SoscurApi;
  const form = document.getElementById('formCurso');

  if (!api || !form) {
    return;
  }

  const alertBox = document.getElementById('course-alert');
  const modalAlert = document.getElementById('course-modal-alert');
  const submitButton = document.getElementById('btnGuardarCurso');
  const categoriaSelect = document.getElementById('curso_categoria_id');
  const creados = document.getElementById('cursos-creados');
  const modalElement = document.getElementById('modalCurso');

  function formatoPrecio(valor) {
    return new Intl.NumberFormat('es-MX', {
      style: 'currency',
      currency: 'MXN',
      minimumFractionDigits: 2
    }).format(Number(valor) || 0);
  }

  function agregarCursoCreado(nombreCategoria, datos) {
    if (!creados) {
      return;
    }

    const vacio = creados.querySelector('.js-sin-cursos');

    if (vacio) {
      vacio.remove();
    }

    const item = document.createElement('li');
    item.className = 'list-group-item px-0 d-flex flex-wrap justify-content-between align-items-center gap-2';

    const info = document.createElement('div');
    const titulo = document.createElement('div');
    titulo.className = 'fw-semibold';
    titulo.textContent = datos.titulo;

    const detalle = document.createElement('div');
    detalle.className = 'text-muted small';
    detalle.textContent = nombreCategoria + ' · ' + formatoPrecio(datos.precio) + ' · ' + datos.tipo_precio;

    info.appendChild(titulo);
    info.appendChild(detalle);

    const estado = document.createElement('span');
    estado.className = 'badge ' + (datos.estatus === 'activo' ? 'st-activo' : 'st-inactivo');
    estado.textContent = datos.estatus === 'activo' ? 'Activo' : 'Inactivo';

    item.appendChild(info);
    item.appendChild(estado);
    creados.appendChild(item);
  }

  async function cargarCategorias() {
    try {
      const respuesta = await api.request('/categorias');
      const categorias = respuesta && Array.isArray(respuesta.data) ? respuesta.data : [];

      categoriaSelect.innerHTML = '';

      if (categorias.length === 0) {
        const opcion = document.createElement('option');
        opcion.value = '';
        opcion.textContent = 'No hay categorías registradas';
        categoriaSelect.appendChild(opcion);
        categoriaSelect.disabled = true;
        submitButton.disabled = true;
        return;
      }

      categorias.forEach(function (categoria) {
        const opcion = document.createElement('option');
        opcion.value = String(categoria.categoria_id);
        opcion.textContent = categoria.nombre;
        categoriaSelect.appendChild(opcion);
      });

      categoriaSelect.disabled = false;
      submitButton.disabled = false;
    } catch (error) {
      categoriaSelect.innerHTML = '<option value="">No se pudieron cargar las categorías</option>';
      categoriaSelect.disabled = true;
      submitButton.disabled = true;
      api.showError(modalAlert, error);
    }
  }

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    api.clearAlert(modalAlert);
    api.clearAlert(alertBox);

    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const titulo = form.querySelector('#curso_titulo');
    const descripcion = form.querySelector('#curso_descripcion');
    const precio = form.querySelector('#curso_precio');
    const tipoPrecio = form.querySelector('#curso_tipo_precio');
    const estatus = form.querySelector('#curso_estatus');
    const categoriaId = Number(categoriaSelect.value);
    const nombreCategoria = categoriaSelect.options[categoriaSelect.selectedIndex]
      ? categoriaSelect.options[categoriaSelect.selectedIndex].text
      : 'Sin categoría';

    if (!Number.isInteger(categoriaId) || categoriaId <= 0) {
      api.showAlert(modalAlert, 'danger', 'Selecciona una categoría válida.');
      return;
    }

    const datos = {
      titulo: titulo ? titulo.value.trim() : '',
      descripcion: descripcion && descripcion.value.trim() !== '' ? descripcion.value.trim() : null,
      precio: precio ? Number(precio.value) || 0 : 0,
      tipo_precio: tipoPrecio ? tipoPrecio.value : 'curso',
      estatus: estatus ? estatus.value : 'activo',
      categoria_id: categoriaId
    };

    api.setLoading(submitButton, true, 'Guardando...');

    try {
      const respuesta = await api.request('/cursos', {
        method: 'POST',
        body: datos
      });

      agregarCursoCreado(nombreCategoria, datos);

      api.showAlert(alertBox, 'success', respuesta && respuesta.mensaje
        ? respuesta.mensaje + ' (ID ' + respuesta.curso_id + ').'
        : 'Curso registrado correctamente.');

      form.reset();
      categoriaSelect.value = String(categoriaId);

      if (modalElement && window.bootstrap) {
        window.bootstrap.Modal.getOrCreateInstance(modalElement).hide();
      }
    } catch (error) {
      api.showError(modalAlert, error);

      if (error && error.status === 401) {
        window.setTimeout(function () {
          api.redirect('login');
        }, 1200);
      }
    } finally {
      api.setLoading(submitButton, false);
    }
  });

  cargarCategorias();
})();
