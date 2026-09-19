<?php
http_response_code(200);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SOSCUR Portal — Local</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #F4F4F4; }
    .box { max-width: 760px; margin: 4rem auto; }
    code { background: #E9ECEF; padding: .15rem .4rem; border-radius: .25rem; }
  </style>
</head>
<body>
  <div class="container">
    <div class="box card border shadow-sm">
      <div class="card-body p-4">
        <h1 class="h3 mb-3">SOSCUR Portal</h1>
        <p class="text-success mb-4"><strong>Servidor local funcional — HTTP 200 OK</strong></p>
        <table class="table table-sm">
          <tr><td>Versión de PHP</td><td><code><?php echo phpversion(); ?></code></td></tr>
          <tr><td>Servidor</td><td><code><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'n/a'; ?></code></td></tr>
          <tr><td>Host</td><td><code><?php echo $_SERVER['HTTP_HOST'] ?? 'n/a'; ?></code></td></tr>
          <tr><td>Fecha generada por PHP</td><td><code><?php echo date('Y-m-d H:i:s'); ?></code></td></tr>
        </table>
        <p class="mb-1">Punto de entrada operativo: <code>/soscur-portal/index.php</code></p>
        <p class="text-muted small">El contenido de esta página fue generado por PHP (no es un archivo estático).</p>
      </div>
    </div>
  </div>
</body>
</html>