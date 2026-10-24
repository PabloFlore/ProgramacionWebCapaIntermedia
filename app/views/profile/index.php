<?php

use App\Helpers\Csrf;
use App\Helpers\Url;

$title = 'Mi perfil';
require __DIR__ . '/../templates/header.php';

$valor = static fn (string $campo): string => htmlspecialchars(
    (string) ($old[$campo] ?? $usuario[$campo] ?? ''),
    ENT_QUOTES,
    'UTF-8'
);
$genero = (string) ($old['genero'] ?? $usuario['genero'] ?? '');
$foto = (string) ($usuario['foto_perfil'] ?? '');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h4 class="fw-bold mb-1">Mi perfil</h4>
    <p class="text-muted small mb-0">Actualiza tus datos personales y tu contraseña.</p>
  </div>
  <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars((string) $usuario['rol'], ENT_QUOTES, 'UTF-8') ?></span>
</div>

<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?>">
    <?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $error): ?>
        <li><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card border shadow-sm">
      <div class="card-body p-4">
        <h6 class="fw-bold mb-3">Datos personales</h6>
        <form method="post" action="<?= htmlspecialchars(Url::to('perfil'), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data">
          <?= Csrf::field() ?>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" for="nombre">Nombre completo</label>
              <input id="nombre" name="nombre" class="form-control" required value="<?= $valor('nombre') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label" for="correo">Correo electrónico</label>
              <input id="correo" type="email" class="form-control" value="<?= $valor('correo') ?>" readonly>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="rol">Rol</label>
              <input id="rol" class="form-control" value="<?= $valor('rol') ?>" readonly>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="genero">Género</label>
              <select id="genero" name="genero" class="form-select">
                <option value="">Prefiero no decirlo</option>
                <option value="femenino" <?= $genero === 'femenino' ? 'selected' : '' ?>>Femenino</option>
                <option value="masculino" <?= $genero === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                <option value="prefiero_no_decir" <?= $genero === 'prefiero_no_decir' ? 'selected' : '' ?>>Prefiero no decirlo</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="fecha_nacimiento">Fecha de nacimiento</label>
              <input id="fecha_nacimiento" name="fecha_nacimiento" type="date" class="form-control"
                     value="<?= $valor('fecha_nacimiento') ?>">
            </div>

            <div class="col-12">
              <label class="form-label" for="foto_perfil">Foto de perfil</label>
              <input id="foto_perfil" name="foto_perfil" type="file" class="form-control" accept="image/png,image/jpeg,image/webp">
              <div class="form-text">JPG, PNG o WebP. Máximo 2 MB.</div>
            </div>

            <div class="col-12">
              <button class="btn btn-primary-soscur" type="submit">Guardar cambios</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card border shadow-sm mb-4">
      <div class="card-body p-4 text-center">
        <?php if ($foto !== ''): ?>
          <img src="<?= htmlspecialchars(Url::asset($foto), ENT_QUOTES, 'UTF-8') ?>"
               alt="Foto de perfil" class="rounded-circle mb-3" style="width: 110px; height: 110px; object-fit: cover;">
        <?php else: ?>
          <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center mb-3"
               style="width: 110px; height: 110px; font-size: 2rem;">
            <?= htmlspecialchars(mb_strtoupper(mb_substr((string) $usuario['nombre'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>
        <h6 class="fw-bold mb-1"><?= htmlspecialchars((string) $usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></h6>
        <p class="text-muted small mb-2"><?= htmlspecialchars((string) $usuario['correo'], ENT_QUOTES, 'UTF-8') ?></p>
        <span class="badge <?= ((int) $usuario['bloqueado'] === 1) ? 'bg-danger' : 'bg-success' ?>">
          <?= ((int) $usuario['bloqueado'] === 1) ? 'Cuenta bloqueada' : 'Cuenta activa' ?>
        </span>
      </div>
    </div>

    <div class="card border shadow-sm">
      <div class="card-body p-4">
        <h6 class="fw-bold mb-3">Cambiar contraseña</h6>
        <form method="post" action="<?= htmlspecialchars(Url::to('perfil/contrasena'), ENT_QUOTES, 'UTF-8') ?>">
          <?= Csrf::field() ?>
          <div class="mb-3">
            <label class="form-label" for="contrasena_actual">Contraseña actual</label>
            <input id="contrasena_actual" name="contrasena_actual" type="password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="contrasena_nueva">Nueva contraseña</label>
            <input id="contrasena_nueva" name="contrasena_nueva" type="password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="contrasena_confirmacion">Confirmar nueva contraseña</label>
            <input id="contrasena_confirmacion" name="contrasena_confirmacion" type="password" class="form-control" required>
          </div>
          <div class="form-text mb-3">
            Mínimo 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.
          </div>
          <button class="btn btn-outline-secondary w-100" type="submit">Actualizar contraseña</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
