<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Controllers\ApiAdminController;
use App\Controllers\ApiAuthController;
use App\Controllers\ApiCategoriaController;
use App\Controllers\ApiComentarioController;
use App\Controllers\ApiController;
use App\Controllers\ApiCourseController;
use App\Controllers\ApiInscripcionController;
use App\Controllers\ApiKardexController;
use App\Controllers\ApiMensajeController;
use App\Controllers\ApiReporteController;
use App\Controllers\AuthController;
use App\Controllers\CategoriaController;
use App\Controllers\CourseController;
use App\Controllers\HomeController;
use App\Controllers\InscripcionController;
use App\Controllers\KardexController;
use App\Controllers\MensajeController;
use App\Controllers\ModeracionController;
use App\Controllers\ReporteController;
use App\Controllers\UserController;
use App\Core\Container;
use App\Core\Router;
use App\Helpers\Auth;
use App\Helpers\Session;
use App\Models\Contracts\UsuarioRepositoryInterface;

Session::start();

$container = new Container();
$bindings = require dirname(__DIR__) . '/app/config/bindings.php';
$bindings($container);

Auth::setUserRepository($container->get(UsuarioRepositoryInterface::class));

$router = new Router($container);

// --- Rutas Web ---
$router->get('/', [HomeController::class, 'index']);
$router->get('/home', [HomeController::class, 'index']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/verificar-correo', [AuthController::class, 'verificarCorreo']);
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
$router->get('/admin/categorias', [CategoriaController::class, 'index']);
$router->get('/admin/comentarios', [ModeracionController::class, 'index']);

$router->get('/curso/{id}', [CourseController::class, 'show']);
$router->get('/admin/cursos', [CourseController::class, 'gestion']);
$router->get('/mis-cursos', [InscripcionController::class, 'index']);
$router->get('/kardex', [KardexController::class, 'index']);
$router->get('/diploma/{id}', [InscripcionController::class, 'diploma']);
$router->get('/verificar-diploma', [InscripcionController::class, 'verificar']);
$router->get('/mensajes', [MensajeController::class, 'index']);
$router->get('/reportes', [ReporteController::class, 'index']);

// --- Rutas API v1 ---
$router->get('/api/v1/categorias', [ApiController::class, 'categorias']);
$router->get('/api/v1/categorias/gestion', [ApiCategoriaController::class, 'gestion']);
$router->post('/api/v1/categorias', [ApiCategoriaController::class, 'crear']);
$router->patch('/api/v1/categorias/{id}', [ApiCategoriaController::class, 'actualizar']);
$router->delete('/api/v1/categorias/{id}', [ApiCategoriaController::class, 'eliminar']);

$router->get('/api/v1/cursos', [ApiController::class, 'cursos']);
$router->get('/api/v1/cursos/{id}', [ApiCourseController::class, 'detalle']);
$router->get('/api/v1/cursos/{id}/comentarios', [ApiComentarioController::class, 'listar']);
$router->post('/api/v1/cursos/{id}/comentarios', [ApiComentarioController::class, 'crear']);
$router->get('/api/v1/cursos/gestion', [ApiCourseController::class, 'gestion']);
$router->get('/api/v1/cursos/gestion/{id}', [ApiCourseController::class, 'gestionDetalle']);
$router->post('/api/v1/cursos', [ApiCourseController::class, 'crear']);
$router->patch('/api/v1/cursos/{id}', [ApiCourseController::class, 'actualizar']);
$router->post('/api/v1/cursos/{id}/baja', [ApiCourseController::class, 'baja']);
$router->post('/api/v1/cursos/{id}/reactivar', [ApiCourseController::class, 'reactivar']);
$router->post('/api/v1/cursos/{id}/niveles', [ApiCourseController::class, 'crearNivel']);
$router->patch('/api/v1/niveles/{id}', [ApiCourseController::class, 'actualizarNivel']);
$router->delete('/api/v1/niveles/{id}', [ApiCourseController::class, 'eliminarNivel']);

$router->get('/api/v1/metodos-pago', [ApiInscripcionController::class, 'metodosPago']);
$router->get('/api/v1/mis-cursos', [ApiInscripcionController::class, 'misCursos']);
$router->get('/api/v1/kardex', [ApiKardexController::class, 'index']);
$router->post('/api/v1/inscripciones', [ApiInscripcionController::class, 'comprar']);
$router->post('/api/v1/pagos/{id}/confirmar', [ApiInscripcionController::class, 'confirmarPago']);
$router->post('/api/v1/inscripciones/{id}/niveles/{nivelId}/completar', [ApiInscripcionController::class, 'completarNivel']);
$router->get('/api/v1/inscripciones/{id}/diploma', [ApiInscripcionController::class, 'diploma']);
$router->get('/api/v1/diplomas/verificar', [ApiInscripcionController::class, 'verificarDiploma']);

$router->get('/api/v1/mensajes', [ApiMensajeController::class, 'index']);
$router->get('/api/v1/mensajes/destinatarios', [ApiMensajeController::class, 'destinatarios']);
$router->post('/api/v1/mensajes', [ApiMensajeController::class, 'crear']);
$router->patch('/api/v1/mensajes/{id}/leido', [ApiMensajeController::class, 'marcarLeido']);

$router->get('/api/v1/reportes', [ApiReporteController::class, 'index']);

$router->get('/api/v1/admin/comentarios', [ApiComentarioController::class, 'listarModeracion']);
$router->post('/api/v1/admin/comentarios/{id}/eliminar', [ApiComentarioController::class, 'eliminar']);
$router->get('/api/v1/admin/usuarios', [ApiAdminController::class, 'usuarios']);
$router->post('/api/v1/admin/usuarios/{id}/bloquear', [ApiAdminController::class, 'bloquear']);
$router->post('/api/v1/admin/usuarios/{id}/reactivar', [ApiAdminController::class, 'reactivar']);

$router->post('/api/v1/registro', [ApiAuthController::class, 'registro']);
$router->post('/api/v1/login', [ApiAuthController::class, 'login']);
$router->post('/api/v1/verificar-correo', [ApiAuthController::class, 'verificarCorreo']);
$router->post('/api/v1/verificar-correo/reenviar', [ApiAuthController::class, 'reenviarVerificacion']);
$router->post('/api/v1/logout', [ApiAuthController::class, 'logout']);
$router->get('/api/v1/perfil', [ApiAuthController::class, 'perfil']);
$router->patch('/api/v1/perfil', [ApiAuthController::class, 'actualizarPerfil']);
$router->post('/api/v1/perfil/contrasena', [ApiAuthController::class, 'cambiarContrasena']);
$router->post('/api/v1/perfil/foto', [ApiAuthController::class, 'subirFoto']);

// --- Despacho de petición ---
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

$router->dispatch($requestPath, $method);