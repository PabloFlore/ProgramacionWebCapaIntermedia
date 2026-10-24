<?php

use App\Helpers\Csrf;
use App\Helpers\Url;

$title = 'Crear cuenta';
require __DIR__ . '/../templates/header.php';

$rolSeleccionado = (string) ($old['rol'] ?? 'estudiante');
?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card border shadow-sm">
      <div class="card-body p-4">
        <h4 class="fw-bold mb-1">Crear cuenta</h4>
        <p class="text-muted small mb-4">Únete como estudiante o instructor.</p>

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

        <form method="post" action="<?= htmlspecialchars(Url::to('registro'), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data">
          <?= Csrf::field() ?>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" for="nombre">Nombre completo</label>
              <input id="nombre" name="nombre" class="form-control" required
                     value="<?= htmlspecialchars((string) ($old['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label" for="genero">Género</label>
              <select id="genero" name="genero" class="form-select">
                <option value="">Prefiero no decirlo</option>
                <option value="femenino" <?= ($old['genero'] ?? '') === 'femenino' ? 'selected' : '' ?>>Femenino</option>
                <option value="masculino" <?= ($old['genero'] ?? '') === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                <option value="prefiero_no_decir" <?= ($old['genero'] ?? '') === 'prefiero_no_decir' ? 'selected' : '' ?>>Prefiero no decirlo</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="fecha_nacimiento">Fecha de nacimiento</label>
              <input id="fecha_nacimiento" name="fecha_nacimiento" type="date" class="form-control"
                     value="<?= htmlspecialchars((string) ($old['fecha_nacimiento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-12">
              <label class="form-label" for="foto_perfil">Foto de perfil <span class="text-muted">(opcional)</span></label>
              <input id="foto_perfil" name="foto_perfil" type="file" class="form-control" accept="image/png,image/jpeg,image/webp">
              <div class="form-text">JPG, PNG o WebP. Máximo 2 MB.</div>
            </div>

            <div class="col-12">
              <label class="form-label" for="correo">Correo electrónico</label>
              <input id="correo" name="correo" type="email" class="form-control" required
                     value="<?= htmlspecialchars((string) ($old['correo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label" for="contrasena">Contraseña</label>
              <input id="contrasena" name="contrasena" type="password" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="contrasena_confirmacion">Confirmar contraseña</label>
              <input id="contrasena_confirmacion" name="contrasena_confirmacion" type="password" class="form-control" required>
            </div>

            <div class="col-12">
              <div class="form-text">
                Mínimo 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.
              </div>
            </div>

            <div class="col-12">
              <label class="form-label d-block">Rol inicial</label>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="rol" id="rol_estudiante" value="estudiante"
                       <?= $rolSeleccionado === 'estudiante' ? 'checked' : '' ?>>
                <label class="form-check-label" for="rol_estudiante">Estudiante</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="rol" id="rol_instructor" value="instructor"
                       <?= $rolSeleccionado === 'instructor' ? 'checked' : '' ?>>
                <label class="form-check-label" for="rol_instructor">Instructor</label>
              </div>
            </div>

            <div class="col-12">
              <button class="btn btn-primary-soscur w-100" type="submit">Crear cuenta</button>
            </div>
          </div>
        </form>

        <p class="text-center small mt-3 mb-0">
          ¿Ya tienes cuenta?
          <a href="<?= htmlspecialchars(Url::to('login'), ENT_QUOTES, 'UTF-8') ?>">Inicia sesión</a>
        </p>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
