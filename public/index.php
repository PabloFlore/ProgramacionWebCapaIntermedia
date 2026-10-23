<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\ApiController;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/home', [HomeController::class, 'index']);
$router->get('/api/v1/cursos', [ApiController::class, 'cursos']);
$router->post('/api/v1/cursos', [ApiController::class, 'crearCurso']);

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($uri, $method);
