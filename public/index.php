<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

use App\Core\Router;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\RegistroController;
use App\Middleware\AuthMiddleware;

$router = new Router();

$router->registrarGet('/', fn() => require ROOT . '/views/landing.php');
$router->registrarGet('/login', [LoginController::class, 'mostrarFormulario']);
$router->registrarPost('/login', [LoginController::class, 'procesarLogin']);
$router->registrarPost('/logout', [LoginController::class, 'cerrarSesion']);
$router->registrarGet('/register', [RegistroController::class, 'mostrarFormulario']);
$router->registrarPost('/register', [RegistroController::class, 'procesarRegistro']);

// Rutas protegidas (requieren sesion activa)
$router->registrarGet('/home', function () {
    AuthMiddleware::verificarAutenticacion();
    require ROOT . '/views/home.php';
});

$router->registrarGet('/onboarding/step1', function () {
    AuthMiddleware::verificarAutenticacion();
    require ROOT . '/views/onboarding/step1.php';
});

$router->registrarGet('/onboarding/step2', function () {
    AuthMiddleware::verificarAutenticacion();
    require ROOT . '/views/onboarding/step2.php';
});

$router->registrarGet('/profile', function () {
    AuthMiddleware::verificarAutenticacion();
    require ROOT . '/views/profile/index.php';
});

// Rutas de admin (requieren rol admin)
$router->registrarGet('/admin', function () {
    AuthMiddleware::requerirRol('admin');
    require ROOT . '/views/admin/index.php';
});

$router->despachar();
