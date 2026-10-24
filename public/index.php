<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\ApiController;
use App\Helpers\Session;

Session::start();

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/home', [HomeController::class, 'index']);
$router->get('/api/v1/cursos', [ApiController::class, 'cursos']);
$router->post('/api/v1/cursos', [ApiController::class, 'crearCurso']);
$router->post('/api/v1/login', [ApiController::class, 'login']);

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/public/index.php';
$projectBase = rtrim(dirname(dirname($scriptName)), '/');

if ($projectBase !== '' && str_starts_with($requestPath, $projectBase . '/')) {
    $requestPath = substr($requestPath, strlen($projectBase));
}

$requestPath = preg_replace('#^/public(?=/|$)#', '', $requestPath) ?? $requestPath;
$requestPath = preg_replace('#^/index\.php(?=/|$)#', '', $requestPath) ?? $requestPath;
$requestPath = $requestPath === '' ? '/' : $requestPath;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $router->dispatch($requestPath, $method);
} catch (\Throwable $exception) {
    error_log('Error no controlado: ' . $exception->getMessage());
    http_response_code(500);

    if (str_starts_with($requestPath, '/api/')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Error interno del servidor'], JSON_UNESCAPED_UNICODE);
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo '500 Internal Server Error';
    }
}
