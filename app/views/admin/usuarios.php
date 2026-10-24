<?php

use App\Helpers\Csrf;
use App\Helpers\Url;

$title = 'Administración de usuarios';
require __DIR__ . '/../templates/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h4 class="fw-bold mb-1">Usuarios</h4>
    <p class="text-muted small mb-0">Lista, bloquea y reactiva cuentas del portal.</p>
  </div>
</div>

<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?>">
    <?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<form method="get" action="<?= htmlspecialchars(Url::to('admin/usuarios'), ENT_QUOTES, 'UTF-8') ?>" class="row g-2 align-items-end mb-3">
  <div class="col-md-4">
    <label class="form-label small" for="rol">Rol</label>
    <select id="rol" name="rol" class="form-select">
      <option value="">Todos</option>
      <option value="estudiante" <?= $filtroRol === 'estudiante' ? 'selected' : '' ?>>Estudiante</option>
      <option value="instructor" <?= $filtroRol === 'instructor' ? 'selected' : '' ?>>Instructor</option>
      <option value="admin" <?= $filtroRol === 'admin' ? 'selected' : '' ?>>Administrador</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label small" for="bloqueado">Estado</label>
    <select id="bloqueado" name="bloqueado" class="form-select">
      <option value="">Todos</option>
      <option value="0" <?= $filtroBloqueado === '0' ? 'selected' : '' ?>>Activos</option>
      <option value="1" <?= $filtroBloqueado === '1' ? 'selected' : '' ?>>Bloqueados</option>
    </select>
  </div>
  <div class="col-md-4">
    <button class="btn btn-outline-secondary w-100" type="submit">Filtrar</button>
  </div>
</form>

<div class="card border shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Usuario</th>
          <th>Rol</th>
          <th>Estado</th>
          <th>Registro</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($usuarios)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No hay usuarios para este filtro.</td></tr>
        <?php endif; ?>

        <?php foreach ($usuarios as $usuario): ?>
          <?php
          $esPropio = (int) $usuario['usuario_id'] === (int) $admin['usuario_id'];
          $bloqueado = (int) $usuario['bloqueado'] === 1;
          ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= htmlspecialchars((string) $usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
              <div class="text-muted small"><?= htmlspecialchars((string) $usuario['correo'], ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td><span class="badge bg-secondary text-uppercase"><?= htmlspecialchars((string) $usuario['rol'], ENT_QUOTES, 'UTF-8') ?></span></td>
            <td>
              <span class="badge <?= $bloqueado ? 'bg-danger' : 'bg-success' ?>">
                <?= $bloqueado ? 'Bloqueado' : 'Activo' ?>
              </span>
              <?php if ($bloqueado && !empty($usuario['motivo_bloqueo'])): ?>
                <div class="text-muted small"><?= htmlspecialchars((string) $usuario['motivo_bloqueo'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </td>
            <td class="small text-muted"><?= htmlspecialchars((string) ($usuario['fecha_registro'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="text-end">
              <?php if ($esPropio): ?>
                <span class="text-muted small">Tu cuenta</span>
              <?php elseif ($bloqueado): ?>
                <form method="post" action="<?= htmlspecialchars(Url::to('admin/usuarios/' . (int) $usuario['usuario_id'] . '/reactivar'), ENT_QUOTES, 'UTF-8') ?>">
                  <?= Csrf::field() ?>
                  <button class="btn btn-sm btn-outline-success" type="submit">Reactivar</button>
                </form>
              <?php else: ?>
                <form method="post" action="<?= htmlspecialchars(Url::to('admin/usuarios/' . (int) $usuario['usuario_id'] . '/bloquear'), ENT_QUOTES, 'UTF-8') ?>" class="d-flex justify-content-end gap-1">
                  <?= Csrf::field() ?>
                  <input name="motivo" class="form-control form-control-sm" style="max-width: 180px;" placeholder="Motivo" maxlength="255" required>
                  <button class="btn btn-sm btn-outline-danger" type="submit">Bloquear</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
