<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Controllers\ApiAdminController;
use App\Controllers\ApiAuthController;
use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\UserController;
use App\Core\Router;
use App\Helpers\Session;

Session::start();

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/home', [HomeController::class, 'index']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/registro', [AuthController::class, 'showRegister']);
$router->post('/registro', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/dashboard', [AuthController::class, 'dashboard']);
$router->get('/perfil', [AuthController::class, 'showProfile']);
$router->post('/perfil', [AuthController::class, 'updateProfile']);
$router->post('/perfil/contrasena', [AuthController::class, 'changePassword']);

$router->get('/admin/usuarios', [UserController::class, 'index']);
$router->post('/admin/usuarios/{id}/bloquear', [UserController::class, 'bloquear']);
$router->post('/admin/usuarios/{id}/reactivar', [UserController::class, 'reactivar']);

$router->get('/api/v1/cursos', [ApiController::class, 'cursos']);
$router->post('/api/v1/cursos', [ApiController::class, 'crearCurso']);

$router->post('/api/v1/registro', [ApiAuthController::class, 'registro']);
$router->post('/api/v1/login', [ApiAuthController::class, 'login']);
$router->post('/api/v1/logout', [ApiAuthController::class, 'logout']);
$router->get('/api/v1/perfil', [ApiAuthController::class, 'perfil']);
$router->patch('/api/v1/perfil', [ApiAuthController::class, 'actualizarPerfil']);
$router->post('/api/v1/perfil/contrasena', [ApiAuthController::class, 'cambiarContrasena']);
$router->post('/api/v1/perfil/foto', [ApiAuthController::class, 'subirFoto']);

$router->get('/api/v1/admin/usuarios', [ApiAdminController::class, 'usuarios']);
$router->post('/api/v1/admin/usuarios/{id}/bloquear', [ApiAdminController::class, 'bloquear']);
$router->post('/api/v1/admin/usuarios/{id}/reactivar', [ApiAdminController::class, 'reactivar']);

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/public/index.php';
$projectBase = str_replace('\\', '/', dirname(dirname($scriptName)));
$projectBase = rtrim($projectBase, '/');

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
