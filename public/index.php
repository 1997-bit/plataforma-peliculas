<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/container.php';
use App\Core\Router;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\RegistroController;
use App\Middleware\AuthMiddleware;
use App\Controllers\Home\HomeController;

$router = new Router();

$loginController = new LoginController($procesarLogin, $cerrarSesion);
$registroController = new RegistroController($registrarUsuario);
$router->registrarGet('/', fn () => require ROOT . '/views/landing.php');
$router->registrarGet('/privacidad', fn () => require ROOT . '/views/privacidad.php');
$router->registrarGet('/login', [$loginController, 'mostrarFormulario']);
$router->registrarPost('/login', [$loginController, 'login']);
$router->registrarPost('/logout', [$loginController, 'logout']);
$router->registrarGet('/register', [$registroController, 'mostrarFormulario']);
$router->registrarPost('/register', [$registroController, 'procesarRegistro']);


// Rutas protegidas (requieren sesion activa)
$homeController = new HomeController();
$router->registrarGet('/home', function () use ($homeController) {
    AuthMiddleware::verificarAutenticacion();
    $homeController->index();
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

try {
    $router->despachar();
} catch (\Throwable $e) {
    error_log('Unhandled exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);

    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        throw $e;
    }

    require ROOT . '/views/errors/500.php';
}
