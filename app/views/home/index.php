<?php

use App\Helpers\Url;

$title = 'Inicio';
require __DIR__ . '/../templates/header.php';
?>
<header class="hero p-4 p-md-5 mb-4">
  <div class="row align-items-center">
    <div class="col-lg-8">
      <p class="mb-2"><i class="bi bi-stars"></i> Formación verificable en línea</p>
      <h1 class="display-6 mb-3">Aprende con los mejores cursos en línea</h1>
      <p class="lead mb-4">Multimedia y animación digital con certificados verificables.</p>
      <a href="#catalogo" class="btn btn-light btn-lg me-2">Explorar cursos</a>
      <?php if (\App\Helpers\Auth::user() === null): ?>
        <a href="<?= htmlspecialchars(Url::to('registro'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-light btn-lg">Crear cuenta</a>
      <?php endif; ?>
    </div>
    <div class="col-lg-4 text-center d-none d-lg-block">
      <i class="bi bi-play-circle-fill" style="font-size: 6rem;"></i>
    </div>
  </div>
</header>

<div class="row" id="catalogo">
  <aside class="col-lg-3 mb-4">
    <div class="category-sidebar p-3">
      <h6 class="fw-bold mb-3">Categorías</h6>
      <div id="categorias-list" class="list-group list-group-flush">
        <div class="d-flex align-items-center gap-2 text-muted small py-2">
          <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
          Cargando categorías...
        </div>
      </div>
    </div>
  </aside>

  <section class="col-lg-9">
    <div id="catalogo-alerta" aria-live="polite"></div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
      <div>
        <h4 id="catalogo-titulo" class="section-title mb-0">Cursos disponibles</h4>
        <p id="catalogo-subtitulo" class="text-muted small mb-0">Explora y filtra los cursos activos del portal.</p>
      </div>
      <div class="input-group" style="max-width: 320px;">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input id="searchCurso" class="form-control" type="search" placeholder="Buscar por título o palabra..." autocomplete="off">
      </div>
    </div>

    <div class="card border shadow-sm mb-4">
      <div class="card-body p-3">
        <div class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small mb-1" for="filtroInstructor">Instructor</label>
            <input id="filtroInstructor" class="form-control form-control-sm" type="search" placeholder="Nombre del instructor..." autocomplete="off">
          </div>
          <div class="col-md-3">
            <label class="form-label small mb-1" for="filtroDesde">Publicado desde</label>
            <input id="filtroDesde" class="form-control form-control-sm" type="date">
          </div>
          <div class="col-md-3">
            <label class="form-label small mb-1" for="filtroHasta">Publicado hasta</label>
            <input id="filtroHasta" class="form-control form-control-sm" type="date">
          </div>
          <div class="col-md-2">
            <button id="btnLimpiarFiltros" class="btn btn-outline-secondary btn-sm w-100" type="button">Limpiar</button>
          </div>
        </div>
      </div>
    </div>

    <div id="catalogo-cargando" class="text-center text-muted py-5">
      <div class="spinner-border text-secondary mb-2" role="status" aria-hidden="true"></div>
      <p class="mb-0 small">Cargando cursos...</p>
    </div>

    <div id="catalogo-vacio" class="alert alert-info d-none" role="status">
      No se encontraron cursos con los filtros seleccionados.
    </div>

    <div id="cursos-grid" class="row g-4"></div>

    <nav aria-label="Paginación del catálogo">
      <ul id="catalogo-paginacion" class="pagination justify-content-center mt-4 mb-0"></ul>
    </nav>
  </section>
</div>
<?php
$pageScript = 'js/catalog.js';
require __DIR__ . '/../templates/footer.php';