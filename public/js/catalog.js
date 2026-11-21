(function () {
  'use strict';

  const api = window.SoscurApi;
  const catalogo = document.getElementById('catalogo');

  if (!api || !catalogo) {
    return;
  }

  const elements = {
    categorias: document.getElementById('categorias-list'),
    alerta: document.getElementById('catalogo-alerta'),
    titulo: document.getElementById('catalogo-titulo'),
    subtitulo: document.getElementById('catalogo-subtitulo'),
    busqueda: document.getElementById('searchCurso'),
    instructor: document.getElementById('filtroInstructor'),
    desde: document.getElementById('filtroDesde'),
    hasta: document.getElementById('filtroHasta'),
    limpiar: document.getElementById('btnLimpiarFiltros'),
    cargando: document.getElementById('catalogo-cargando'),
    vacio: document.getElementById('catalogo-vacio'),
    grid: document.getElementById('cursos-grid'),
    paginacion: document.getElementById('catalogo-paginacion')
  };

  const estado = {
    categorias: [],
    categoriaId: 0, // 0 = Todas las categorías
    pagina: 1,
    limite: 6,
    totalPaginas: 1,
    total: 0,
    controlador: null,
    temporizador: null
  };

  const iconosCategoria = [
    [/animaci/, 'bi-controller'],
    [/modelado|3d/, 'bi-boxes'],
    [/video/, 'bi-camera-video'],
    [/audio|sonido/, 'bi-mic'],
    [/dise/, 'bi-palette'],
    [/web/, 'bi-code-slash']
  ];

  function normalizar(texto) {
    return String(texto || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
  }

  function iconoCategoria(nombre) {
    const valor = normalizar(nombre);

    for (const [patron, icono] of iconosCategoria) {
      if (patron.test(valor)) {
        return icono;
      }
    }

    return 'bi-collection';
  }

  function etiquetaCategoria(nombre) {
    const palabras = String(nombre || '').trim().split(/\s+/).filter(Boolean);

    if (palabras.length === 0) {
      return 'CURSO';
    }

    return palabras.map(function (palabra) {
      return palabra.charAt(0);
    }).join('').slice(0, 3).toUpperCase();
  }

  function formatoPrecio(valor) {
    const numero = Number(valor);

    if (!Number.isFinite(numero) || numero <= 0) {
      return 'Gratis';
    }

    return new Intl.NumberFormat('es-MX', {
      style: 'currency',
      currency: 'MXN',
      minimumFractionDigits: 2
    }).format(numero);
  }

  function formatoFecha(valor) {
    if (!valor) {
      return '';
    }

    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const partes = String(valor).slice(0, 10).split('-');

    if (partes.length !== 3) {
      return '';
    }

    const mes = meses[parseInt(partes[1], 10) - 1] || partes[1];
    return partes[2] + '/' + mes + '/' + partes[0];
  }

  function categoriaPorId(categoriaId) {
    if (!categoriaId || Number(categoriaId) === 0) {
      return null;
    }

    return estado.categorias.find(function (categoria) {
      return Number(categoria.categoria_id) === Number(categoriaId);
    }) || null;
  }

  function mostrarCargando(activo) {
    elements.cargando.classList.toggle('d-none', !activo);
  }

  function mostrarVacio(texto) {
    elements.vacio.textContent = texto;
    elements.vacio.classList.remove('d-none');
  }

  function ocultarVacio() {
    elements.vacio.classList.add('d-none');
  }

  function actualizarTitulo() {
    const categoria = categoriaPorId(estado.categoriaId);
    const nombreTitulo = categoria ? categoria.nombre : 'Todos los cursos';

    elements.titulo.textContent = nombreTitulo;
    elements.subtitulo.textContent = estado.total === 1
      ? '1 curso disponible.'
      : estado.total + ' cursos disponibles.';
  }

  function crearBotonCategoria(id, nombre, iconoClase) {
    const boton = document.createElement('button');
    boton.type = 'button';
    boton.className = 'list-group-item list-group-item-action d-flex align-items-center gap-2';
    boton.dataset.categoriaId = String(id);

    if (Number(id) === Number(estado.categoriaId)) {
      boton.classList.add('active');
    }

    const icono = document.createElement('i');
    icono.className = 'bi ' + iconoClase;
    boton.appendChild(icono);
    boton.appendChild(document.createTextNode(nombre));

    boton.addEventListener('click', function () {
      seleccionarCategoria(Number(id));
    });

    return boton;
  }

  function pintarCategorias() {
    elements.categorias.innerHTML = '';

    elements.categorias.appendChild(crearBotonCategoria(0, 'Todas las categorías', 'bi-grid'));

    estado.categorias.forEach(function (categoria) {
      elements.categorias.appendChild(
        crearBotonCategoria(categoria.categoria_id, categoria.nombre, iconoCategoria(categoria.nombre))
      );
    });
  }

  function seleccionarCategoria(categoriaId) {
    if (Number(estado.categoriaId) === Number(categoriaId)) {
      return;
    }

    estado.categoriaId = Number(categoriaId);
    estado.pagina = 1;

    Array.from(elements.categorias.querySelectorAll('[data-categoria-id]')).forEach(function (boton) {
      boton.classList.toggle('active', Number(boton.dataset.categoriaId) === estado.categoriaId);
    });

    cargarCursos();
  }

  function crearTarjeta(curso) {
    const categoria = categoriaPorId(curso.categoria_id);
    const nombreCategoria = categoria ? categoria.nombre : 'Curso';
    const idCategoria = Number(curso.categoria_id) || 1;

    const columna = document.createElement('div');
    columna.className = 'col-md-6 col-xl-4';

    const tarjeta = document.createElement('div');
    tarjeta.className = 'card h-100 border js-course-card';

    const portada = document.createElement('div');
    portada.className = 'course-cover cover-' + (((idCategoria - 1) % 6) + 1);
    portada.textContent = etiquetaCategoria(nombreCategoria);

    const cuerpo = document.createElement('div');
    cuerpo.className = 'card-body d-flex flex-column';

    const filaSuperior = document.createElement('div');
    filaSuperior.className = 'd-flex justify-content-between align-items-center mb-2';

    const categoriaBadge = document.createElement('span');
    categoriaBadge.className = 'badge bg-light text-dark';
    categoriaBadge.textContent = nombreCategoria;

    const calificacionBadge = document.createElement('span');
    calificacionBadge.className = 'small rating fw-semibold';
    if (curso.calificacion_promedio !== null && curso.calificacion_promedio !== undefined) {
      calificacionBadge.innerHTML = '<i class="bi bi-star-fill"></i> ' + Number(curso.calificacion_promedio).toFixed(1) + '/10';
    } else {
      calificacionBadge.className = 'small text-muted';
      calificacionBadge.textContent = 'Sin calificaciones';
    }

    filaSuperior.appendChild(categoriaBadge);
    filaSuperior.appendChild(calificacionBadge);

    const titulo = document.createElement('h6');
    titulo.className = 'fw-bold';
    titulo.textContent = String(curso.titulo || 'Curso sin título');

    const meta = document.createElement('p');
    meta.className = 'text-muted small mb-2';
    const fechaTxt = formatoFecha(curso.fecha_creacion);
    const instructorTxt = curso.instructor_nombre ? 'Por ' + curso.instructor_nombre : '';
    meta.textContent = [instructorTxt, fechaTxt].filter(Boolean).join(' · ');

    const descripcion = document.createElement('p');
    descripcion.className = 'text-muted small course-description';
    descripcion.textContent = curso.descripcion
      ? String(curso.descripcion)
      : 'Sin descripción disponible.';

    const pie = document.createElement('div');
    pie.className = 'd-flex justify-content-between align-items-center mt-auto pt-3';

    const precio = document.createElement('h5');
    precio.className = 'mb-0';
    precio.textContent = formatoPrecio(curso.precio);

    const tipo = document.createElement('span');
    tipo.className = 'badge st-curso text-uppercase';
    tipo.textContent = curso.tipo_precio === 'nivel' ? 'Por nivel' : 'Curso completo';

    pie.appendChild(precio);
    pie.appendChild(tipo);

    const botonDetalle = document.createElement('a');
    botonDetalle.className = 'btn btn-outline-primary btn-sm w-100 mt-3';
    botonDetalle.href = api.asset('curso/' + encodeURIComponent(curso.curso_id));
    botonDetalle.textContent = 'Ver detalle del curso';

    cuerpo.appendChild(filaSuperior);
    cuerpo.appendChild(titulo);
    if (meta.textContent !== '') {
      cuerpo.appendChild(meta);
    }
    cuerpo.appendChild(descripcion);
    cuerpo.appendChild(pie);
    cuerpo.appendChild(botonDetalle);

    tarjeta.appendChild(portada);
    tarjeta.appendChild(cuerpo);
    columna.appendChild(tarjeta);

    return columna;
  }

  function pintarCursos(cursos, paginacion) {
    elements.grid.innerHTML = '';
    ocultarVacio();

    if (!Array.isArray(cursos) || cursos.length === 0) {
      mostrarVacio('No se encontraron cursos con los filtros seleccionados.');
      elements.paginacion.innerHTML = '';
      return;
    }

    cursos.forEach(function (curso) {
      elements.grid.appendChild(crearTarjeta(curso));
    });

    pintarPaginacion(paginacion);
  }

  function crearItemPaginacion(contenido, destino, deshabilitado) {
    const item = document.createElement('li');
    item.className = 'page-item' + (deshabilitado ? ' disabled' : '');

    if (destino === null) {
      const texto = document.createElement('span');
      texto.className = 'page-link';
      texto.textContent = contenido;
      item.appendChild(texto);
    } else {
      const boton = document.createElement('button');
      boton.type = 'button';
      boton.className = 'page-link';
      boton.textContent = contenido;
      boton.disabled = deshabilitado;
      boton.addEventListener('click', function () {
        if (estado.pagina !== destino) {
          estado.pagina = destino;
          cargarCursos();
        }
      });
      item.appendChild(boton);
    }

    return item;
  }

  function pintarPaginacion(paginacion) {
    elements.paginacion.innerHTML = '';

    if (!paginacion) {
      return;
    }

    const pagina = Number(paginacion.page) || 1;
    const totalPaginas = Number(paginacion.total_pages) || 1;
    estado.totalPaginas = totalPaginas;

    if (totalPaginas <= 1) {
      return;
    }

    elements.paginacion.appendChild(crearItemPaginacion('Anterior', pagina - 1, pagina <= 1));
    elements.paginacion.appendChild(crearItemPaginacion('Página ' + pagina + ' de ' + totalPaginas, null, true));
    elements.paginacion.appendChild(crearItemPaginacion('Siguiente', pagina + 1, pagina >= totalPaginas));
  }

  async function cargarCursos() {
    if (estado.controlador) {
      estado.controlador.abort();
    }

    const controlador = new AbortController();
    estado.controlador = controlador;

    api.clearAlert(elements.alerta);
    ocultarVacio();
    elements.grid.innerHTML = '';
    elements.paginacion.innerHTML = '';
    mostrarCargando(true);

    const parametros = new URLSearchParams({
      categoria_id: String(estado.categoriaId),
      page: String(estado.pagina),
      limit: String(estado.limite)
    });

    const q = elements.busqueda ? elements.busqueda.value.trim() : '';
    const instructor = elements.instructor ? elements.instructor.value.trim() : '';
    const desde = elements.desde ? elements.desde.value : '';
    const hasta = elements.hasta ? elements.hasta.value : '';

    if (q !== '') {
      parametros.set('q', q);
    }
    if (instructor !== '') {
      parametros.set('instructor', instructor);
    }
    if (desde !== '') {
      parametros.set('desde', desde);
    }
    if (hasta !== '') {
      parametros.set('hasta', hasta);
    }

    try {
      const respuesta = await api.request('/cursos?' + parametros.toString(), {
        signal: controlador.signal
      });

      const cursos = respuesta && Array.isArray(respuesta.data) ? respuesta.data : [];
      const paginacion = respuesta && respuesta.pagination ? respuesta.pagination : null;

      estado.total = paginacion ? Number(paginacion.total) || cursos.length : cursos.length;
      actualizarTitulo();
      pintarCursos(cursos, paginacion);
    } catch (error) {
      if (error && error.name === 'AbortError') {
        return;
      }

      if (error && error.status === 404) {
        estado.total = 0;
        actualizarTitulo();
        pintarCursos([], null);
        return;
      }

      api.showError(elements.alerta, error);
      mostrarVacio('No se pudieron cargar los cursos. Inténtalo de nuevo.');
    } finally {
      if (estado.controlador === controlador) {
        estado.controlador = null;
        mostrarCargando(false);
      }
    }
  }

  function programarBusqueda() {
    window.clearTimeout(estado.temporizador);
    estado.temporizador = window.setTimeout(function () {
      estado.pagina = 1;
      cargarCursos();
    }, 300);
  }

  async function iniciarCatalogo() {
    mostrarCargando(true);
    elements.grid.innerHTML = '';
    elements.paginacion.innerHTML = '';
    ocultarVacio();

    try {
      const respuesta = await api.request('/categorias');
      estado.categorias = respuesta && Array.isArray(respuesta.data) ? respuesta.data : [];
      pintarCategorias();
      await cargarCursos();
    } catch (error) {
      elements.categorias.innerHTML = '<p class="text-muted small mb-0">No se pudieron cargar las categorías.</p>';
      api.showError(elements.alerta, error);
      mostrarVacio('No se pudo cargar el catálogo. Recarga la página para intentarlo de nuevo.');
      mostrarCargando(false);
    }
  }

  if (elements.busqueda) {
    elements.busqueda.addEventListener('input', programarBusqueda);
  }
  if (elements.instructor) {
    elements.instructor.addEventListener('input', programarBusqueda);
  }
  if (elements.desde) {
    elements.desde.addEventListener('change', function () {
      estado.pagina = 1;
      cargarCursos();
    });
  }
  if (elements.hasta) {
    elements.hasta.addEventListener('change', function () {
      estado.pagina = 1;
      cargarCursos();
    });
  }
  if (elements.limpiar) {
    elements.limpiar.addEventListener('click', function () {
      if (elements.busqueda) elements.busqueda.value = '';
      if (elements.instructor) elements.instructor.value = '';
      if (elements.desde) elements.desde.value = '';
      if (elements.hasta) elements.hasta.value = '';
      estado.pagina = 1;
      cargarCursos();
    });
  }

  iniciarCatalogo();
})();