<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Router;
use App\Controllers\HomeController;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/home', [HomeController::class, 'index']);

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($uri, $method);
