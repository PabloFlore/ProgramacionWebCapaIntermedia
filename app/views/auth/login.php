<?php

use App\Helpers\Csrf;
use App\Helpers\Url;

$title = 'Iniciar sesión';
require __DIR__ . '/../templates/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-7 col-lg-5">
    <div class="card border shadow-sm">
      <div class="card-body p-4">
        <h4 class="fw-bold mb-1">Iniciar sesión</h4>
        <p class="text-muted small mb-4">Accede a tu cuenta de SOSCUR.</p>

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

        <form method="post" action="<?= htmlspecialchars(Url::to('login'), ENT_QUOTES, 'UTF-8') ?>">
          <?= Csrf::field() ?>
          <div class="mb-3">
            <label class="form-label" for="correo">Correo electrónico</label>
            <input id="correo" name="correo" type="email" class="form-control" required
                   value="<?= htmlspecialchars((string) ($old['correo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label" for="contrasena">Contraseña</label>
            <input id="contrasena" name="contrasena" type="password" class="form-control" required>
          </div>
          <button class="btn btn-primary-soscur w-100" type="submit">Entrar</button>
        </form>

        <p class="text-center small mt-3 mb-0">
          ¿No tienes cuenta?
          <a href="<?= htmlspecialchars(Url::to('registro'), ENT_QUOTES, 'UTF-8') ?>">Regístrate aquí</a>
        </p>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
