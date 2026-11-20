<?php

use App\Helpers\Url;

$scripts = $pageScripts ?? ($pageScript ?? []);
$scripts = is_array($scripts) ? $scripts : [$scripts];
?>
</main>
<footer class="container py-4 text-muted small">
  SOSCUR Portal — Programación Web Capa Intermedia
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= htmlspecialchars(Url::asset('js/api.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars(Url::asset('js/verificacion.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php foreach ($scripts as $script): ?>
  <?php if (is_string($script) && $script !== ''): ?>
    <script src="<?= htmlspecialchars(Url::asset($script), ENT_QUOTES, 'UTF-8') ?>"></script>
  <?php endif; ?>
<?php endforeach; ?>
</body>
</html>
