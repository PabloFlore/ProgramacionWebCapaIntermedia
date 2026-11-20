<?php

use App\Helpers\Csrf;
use App\Helpers\Url;

$title = 'Administración de usuarios';
require __DIR__ . '/../templates/header.php';

$adminId = (int) $admin['usuario_id'];
$pagina = (int) $opciones['pagina'];
$limite = (int) $opciones['limite'];
$total = (int) $total;
$paginas = (int) $paginas;
$desde = $total === 0 ? 0 : (($pagina - 1) * $limite) + 1;
$hasta = $desde === 0 ? 0 : $desde + count($usuarios) - 1;
$inicioPaginacion = max(1, $pagina - 2);
$finPaginacion = min(max(1, $paginas), $inicioPaginacion + 4);
$inicioPaginacion = max(1, $finPaginacion - 4);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h4 class="fw-bold mb-1">Usuarios</h4>
    <p class="text-muted small mb-0">Lista, busca, bloquea y reactiva cuentas del portal.</p>
  </div>
</div>

<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?>">
    <?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<div id="admin-alert" aria-live="polite"></div>

<form id="formFiltrosUsuarios" method="get" action="<?= htmlspecialchars(Url::to('admin/usuarios'), ENT_QUOTES, 'UTF-8') ?>" class="row g-2 align-items-end mb-3">
  <div class="col-md-4">
    <label class="form-label small" for="q">Buscar</label>
    <input id="q" name="q" class="form-control" maxlength="100" placeholder="Nombre o correo" value="<?= htmlspecialchars((string) $filtroQ, ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label small" for="rol">Rol</label>
    <select id="rol" name="rol" class="form-select">
      <option value="">Todos</option>
      <option value="estudiante" <?= $filtroRol === 'estudiante' ? 'selected' : '' ?>>Estudiante</option>
      <option value="instructor" <?= $filtroRol === 'instructor' ? 'selected' : '' ?>>Instructor</option>
      <option value="admin" <?= $filtroRol === 'admin' ? 'selected' : '' ?>>Administrador</option>
    </select>
  </div>
  <div class="col-md-2">
    <label class="form-label small" for="bloqueado">Estado</label>
    <select id="bloqueado" name="bloqueado" class="form-select">
      <option value="">Todos</option>
      <option value="0" <?= $filtroBloqueado === '0' ? 'selected' : '' ?>>Activos</option>
      <option value="1" <?= $filtroBloqueado === '1' ? 'selected' : '' ?>>Bloqueados</option>
    </select>
  </div>
  <div class="col-md-2">
    <label class="form-label small" for="limite">Por página</label>
    <select id="limite" name="limite" class="form-select">
      <option value="10" <?= $limite === 10 ? 'selected' : '' ?>>10</option>
      <option value="25" <?= $limite === 25 ? 'selected' : '' ?>>25</option>
      <option value="50" <?= $limite === 50 ? 'selected' : '' ?>>50</option>
      <option value="100" <?= $limite === 100 ? 'selected' : '' ?>>100</option>
    </select>
  </div>
  <div class="col-md-2">
    <button class="btn btn-outline-secondary w-100" type="submit">Filtrar</button>
  </div>
  <input type="hidden" name="orden" value="<?= htmlspecialchars((string) $opciones['orden'], ENT_QUOTES, 'UTF-8') ?>">
  <input type="hidden" name="direccion" value="<?= htmlspecialchars((string) $opciones['direccion'], ENT_QUOTES, 'UTF-8') ?>">
  <input type="hidden" name="pagina" value="<?= $pagina ?>">
</form>

<div id="admin-tabla" class="card border shadow-sm"
     data-admin-id="<?= $adminId ?>"
     data-total="<?= $total ?>"
     data-pagina="<?= $pagina ?>"
     data-paginas="<?= $paginas ?>"
     data-limite="<?= $limite ?>"
     data-orden="<?= htmlspecialchars((string) $opciones['orden'], ENT_QUOTES, 'UTF-8') ?>"
     data-direccion="<?= htmlspecialchars((string) $opciones['direccion'], ENT_QUOTES, 'UTF-8') ?>">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>
            <button class="btn btn-link p-0 text-decoration-none text-reset" type="button" data-orden="nombre">
              Usuario <span class="js-orden-indicador" aria-hidden="true"></span>
            </button>
          </th>
          <th>
            <button class="btn btn-link p-0 text-decoration-none text-reset" type="button" data-orden="rol">
              Rol <span class="js-orden-indicador" aria-hidden="true"></span>
            </button>
          </th>
          <th>
            <button class="btn btn-link p-0 text-decoration-none text-reset" type="button" data-orden="bloqueado">
              Estado <span class="js-orden-indicador" aria-hidden="true"></span>
            </button>
          </th>
          <th>
            <button class="btn btn-link p-0 text-decoration-none text-reset" type="button" data-orden="fecha_registro">
              Registro <span class="js-orden-indicador" aria-hidden="true"></span>
            </button>
          </th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody id="usuarios-tbody">
        <?php if (empty($usuarios)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No hay usuarios para este filtro.</td></tr>
        <?php endif; ?>

        <?php foreach ($usuarios as $usuario): ?>
          <?php
          $esPropio = (int) $usuario['usuario_id'] === $adminId;
          $bloqueado = (int) $usuario['bloqueado'] === 1;
          $fechaRegistro = '';
          if (!empty($usuario['fecha_registro'])) {
              $marcaTiempo = strtotime((string) $usuario['fecha_registro']);
              $fechaRegistro = $marcaTiempo === false ? '' : date('d/m/Y', $marcaTiempo);
          }
          ?>
          <tr data-usuario-id="<?= (int) $usuario['usuario_id'] ?>" data-bloqueado="<?= $bloqueado ? '1' : '0' ?>">
            <td>
              <div class="fw-semibold"><?= htmlspecialchars((string) $usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
              <div class="text-muted small"><?= htmlspecialchars((string) $usuario['correo'], ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td><span class="badge bg-secondary text-uppercase"><?= htmlspecialchars((string) $usuario['rol'], ENT_QUOTES, 'UTF-8') ?></span></td>
            <td>
              <span class="badge js-estado-badge <?= $bloqueado ? 'bg-danger' : 'bg-success' ?>">
                <?= $bloqueado ? 'Bloqueado' : 'Activo' ?>
              </span>
              <div class="js-motivo-bloqueo text-muted small">
                <?= $bloqueado && !empty($usuario['motivo_bloqueo'])
                    ? htmlspecialchars((string) $usuario['motivo_bloqueo'], ENT_QUOTES, 'UTF-8')
                    : '' ?>
              </div>
            </td>
            <td class="small text-muted"><?= htmlspecialchars($fechaRegistro, ENT_QUOTES, 'UTF-8') ?></td>
            <td class="text-end">
              <?php if ($esPropio): ?>
                <span class="text-muted small">Tu cuenta</span>
              <?php else: ?>
                <div class="js-acciones-bloqueado <?= $bloqueado ? '' : 'd-none' ?>">
                  <form method="post"
                        action="<?= htmlspecialchars(Url::to('admin/usuarios/' . (int) $usuario['usuario_id'] . '/reactivar'), ENT_QUOTES, 'UTF-8') ?>"
                        data-user-action="reactivar"
                        data-usuario-id="<?= (int) $usuario['usuario_id'] ?>">
                    <?= Csrf::field() ?>
                    <button class="btn btn-sm btn-outline-success" type="submit">Reactivar</button>
                  </form>
                </div>

                <div class="js-acciones-activo <?= $bloqueado ? 'd-none' : '' ?>">
                  <form method="post"
                        action="<?= htmlspecialchars(Url::to('admin/usuarios/' . (int) $usuario['usuario_id'] . '/bloquear'), ENT_QUOTES, 'UTF-8') ?>"
                        class="d-flex justify-content-end gap-1"
                        data-user-action="bloquear"
                        data-usuario-id="<?= (int) $usuario['usuario_id'] ?>">
                    <?= Csrf::field() ?>
                    <input name="motivo" class="form-control form-control-sm" style="max-width: 180px;" placeholder="Motivo" maxlength="255" required>
                    <button class="btn btn-sm btn-outline-danger" type="submit">Bloquear</button>
                  </form>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-top p-3">
    <span id="admin-resumen" class="small text-muted">
      Mostrando <?= $desde ?> a <?= $hasta ?> de <?= $total ?> usuarios
    </span>
    <nav id="paginacion-usuarios" aria-label="Paginación de usuarios">
      <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
          <button class="page-link" type="button" data-pagina="<?= max(1, $pagina - 1) ?>">Anterior</button>
        </li>
        <?php for ($paginaActual = $inicioPaginacion; $paginaActual <= $finPaginacion; $paginaActual++): ?>
          <li class="page-item <?= $paginaActual === $pagina ? 'active' : '' ?>">
            <button class="page-link" type="button" data-pagina="<?= $paginaActual ?>"><?= $paginaActual ?></button>
          </li>
        <?php endfor; ?>
        <li class="page-item <?= $paginas === 0 || $pagina >= $paginas ? 'disabled' : '' ?>">
          <button class="page-link" type="button" data-pagina="<?= min(max(1, $paginas), $pagina + 1) ?>">Siguiente</button>
        </li>
      </ul>
    </nav>
  </div>
</div>
<?php
$pageScript = 'js/admin-usuarios.js';
require __DIR__ . '/../templates/footer.php';
?>
