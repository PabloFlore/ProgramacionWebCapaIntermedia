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
    cargando: document.getElementById('catalogo-cargando'),
    vacio: document.getElementById('catalogo-vacio'),
    grid: document.getElementById('cursos-grid'),
    paginacion: document.getElementById('catalogo-paginacion')
  };

  const estado = {
    categorias: [],
    categoriaId: null,
    busqueda: '',
    pagina: 1,
    limite: 6,
    totalPaginas: 1,
    total: 0,
    controlador: null,
    temporizadorBusqueda: null
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

  function categoriaPorId(categoriaId) {
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

    if (!categoria) {
      elements.titulo.textContent = 'Cursos disponibles';
      elements.subtitulo.textContent = estado.busqueda === ''
        ? 'Selecciona una categoría o busca por título, descripción o categoría.'
        : 'Resultados para "' + estado.busqueda + '" en todas las categorías.';
      return;
    }

    elements.titulo.textContent = categoria.nombre;
    elements.subtitulo.textContent = estado.busqueda === ''
      ? (estado.total === 1
        ? '1 curso disponible en esta categoría.'
        : estado.total + ' cursos disponibles en esta categoría.')
      : (estado.total === 1
        ? '1 resultado para "' + estado.busqueda + '".'
        : estado.total + ' resultados para "' + estado.busqueda + '".');
  }

  function pintarCategorias() {
    elements.categorias.innerHTML = '';

    const todas = document.createElement('button');
    todas.type = 'button';
    todas.className = 'list-group-item list-group-item-action d-flex align-items-center gap-2';
    todas.dataset.categoriaId = '';

    if (estado.categoriaId === null) {
      todas.classList.add('active');
    }

    const iconoTodas = document.createElement('i');
    iconoTodas.className = 'bi bi-collection';
    todas.appendChild(iconoTodas);
    todas.appendChild(document.createTextNode('Todas las categorías'));
    todas.addEventListener('click', function () {
      seleccionarCategoria(null);
    });
    elements.categorias.appendChild(todas);

    if (estado.categorias.length === 0) {
      return;
    }

    estado.categorias.forEach(function (categoria) {
      const boton = document.createElement('button');
      boton.type = 'button';
      boton.className = 'list-group-item list-group-item-action d-flex align-items-center gap-2';
      boton.dataset.categoriaId = String(categoria.categoria_id);

      if (Number(categoria.categoria_id) === Number(estado.categoriaId)) {
        boton.classList.add('active');
      }

      const icono = document.createElement('i');
      icono.className = 'bi ' + iconoCategoria(categoria.nombre);
      boton.appendChild(icono);
      boton.appendChild(document.createTextNode(categoria.nombre));

      boton.addEventListener('click', function () {
        seleccionarCategoria(Number(categoria.categoria_id));
      });

      elements.categorias.appendChild(boton);
    });
  }

  function seleccionarCategoria(categoriaId) {
    const destino = categoriaId === null ? null : Number(categoriaId);

    if (estado.categoriaId === destino) {
      return;
    }

    estado.categoriaId = destino;
    estado.busqueda = '';
    estado.pagina = 1;

    if (elements.busqueda) {
      elements.busqueda.value = '';
    }

    Array.from(elements.categorias.querySelectorAll('[data-categoria-id]')).forEach(function (boton) {
      const valor = boton.dataset.categoriaId === '' ? null : Number(boton.dataset.categoriaId);
      boton.classList.toggle('active', valor === destino);
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
    tarjeta.dataset.search = normalizar([
      curso.titulo,
      curso.descripcion,
      nombreCategoria,
      curso.tipo_precio
    ].join(' '));

    const portada = document.createElement('div');
    portada.className = 'course-cover cover-' + (((idCategoria - 1) % 6) + 1);
    portada.textContent = etiquetaCategoria(nombreCategoria);

    const cuerpo = document.createElement('div');
    cuerpo.className = 'card-body d-flex flex-column';

    const categoriaBadge = document.createElement('span');
    categoriaBadge.className = 'badge bg-light text-dark mb-2 align-self-start';
    categoriaBadge.textContent = nombreCategoria;

    const calificacion = document.createElement('div');
    calificacion.className = 'small text-muted mb-2';
    if (curso.calificacion_promedio !== null && curso.calificacion_promedio !== undefined) {
      calificacion.textContent = 'Calificación ' + Number(curso.calificacion_promedio).toFixed(1).replace('.', ',')
        + '/10 (' + Number(curso.total_comentarios || 0) + ')';
    } else {
      calificacion.textContent = 'Sin calificaciones';
    }

    const titulo = document.createElement('h6');
    titulo.className = 'fw-bold';
    titulo.textContent = String(curso.titulo || 'Curso sin título');

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

    const enlace = document.createElement('a');
    enlace.className = 'btn btn-outline-primary btn-sm mt-3';
    enlace.href = api.asset('curso/' + Number(curso.curso_id));
    enlace.textContent = 'Ver curso';
    enlace.setAttribute('aria-label', 'Ver detalles de ' + String(curso.titulo || 'curso'));

    cuerpo.appendChild(categoriaBadge);
    cuerpo.appendChild(calificacion);
    cuerpo.appendChild(titulo);
    cuerpo.appendChild(descripcion);
    cuerpo.appendChild(pie);
    cuerpo.appendChild(enlace);

    tarjeta.appendChild(portada);
    tarjeta.appendChild(cuerpo);
    columna.appendChild(tarjeta);

    return columna;
  }

  function pintarCursos(cursos, paginacion) {
    elements.grid.innerHTML = '';
    ocultarVacio();

    if (!Array.isArray(cursos) || cursos.length === 0) {
      mostrarVacio('No hay cursos disponibles en esta categoría por el momento.');
      elements.paginacion.innerHTML = '';
      return;
    }

    cursos.forEach(function (curso) {
      elements.grid.appendChild(crearTarjeta(curso));
    });

    aplicarBusqueda();
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
      categoria_id: estado.categoriaId === null ? 'todas' : String(estado.categoriaId),
      page: String(estado.pagina),
      limit: String(estado.limite)
    });

    if (estado.busqueda !== '') {
      parametros.set('q', estado.busqueda);
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

  function aplicarBusqueda() {
    if (!elements.busqueda) {
      return;
    }

    const termino = normalizar(elements.busqueda.value.trim());
    const tarjetas = Array.from(elements.grid.querySelectorAll('.js-course-card'));
    let visibles = 0;

    tarjetas.forEach(function (tarjeta) {
      const coincide = termino === '' || tarjeta.dataset.search.includes(termino);
      tarjeta.parentElement.classList.toggle('d-none', !coincide);

      if (coincide) {
        visibles += 1;
      }
    });

    if (tarjetas.length > 0 && visibles === 0) {
      mostrarVacio('No hay cursos que coincidan con tu búsqueda.');
    } else if (tarjetas.length > 0) {
      ocultarVacio();
    }
  }

  async function iniciarCatalogo() {
    mostrarCargando(true);
    elements.grid.innerHTML = '';
    elements.paginacion.innerHTML = '';
    ocultarVacio();

    try {
      const respuesta = await api.request('/categorias');
      estado.categorias = respuesta && Array.isArray(respuesta.data) ? respuesta.data : [];

      if (estado.categorias.length === 0) {
        elements.categorias.innerHTML = '<p class="text-muted small mb-0">No hay categorías registradas.</p>';
        mostrarVacio('Todavía no hay categorías disponibles.');
        mostrarCargando(false);
        return;
      }

      estado.categoriaId = null;
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
    elements.busqueda.addEventListener('input', function () {
      estado.busqueda = elements.busqueda.value.trim();
      estado.pagina = 1;

      if (estado.temporizadorBusqueda) {
        clearTimeout(estado.temporizadorBusqueda);
      }

      estado.temporizadorBusqueda = setTimeout(function () {
        cargarCursos();
      }, 250);
    });
  }

  iniciarCatalogo();
})();
