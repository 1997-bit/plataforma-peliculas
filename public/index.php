<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/container.php';

use App\Core\Router;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\RegistroController;
use App\Middleware\AuthMiddleware;
use App\Controllers\Home\HomeController;
use App\Controllers\Catalogo\CatalogoController;
use App\Controllers\Catalogo\ContenidoController;
use App\Controllers\Catalogo\RecomendacionController;
use App\Controllers\User\UserController;
use App\Controllers\User\SettingsController;

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
$homeController = new HomeController($contenidoRepo);
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

$catalogoController = new CatalogoController();
$router->registrarGet('/catalogo', function () use ($catalogoController) {
    AuthMiddleware::verificarAutenticacion();
    $catalogoController->index();
});
$router->registrarPost('/catalogo/vista', function () use ($catalogoController) {
    AuthMiddleware::verificarAutenticacion();
    $catalogoController->registrarVista();
});

$contenidoController = new ContenidoController($contenidoRepo);
$router->registrarGet('/contenido', function () use ($contenidoController) {
    AuthMiddleware::verificarAutenticacion();
    $contenidoController->index();
});
$router->registrarPost('/contenido/calificar', function () use ($contenidoController) {
    AuthMiddleware::verificarAutenticacion();
    $contenidoController->calificar();
});

$recomendacionController = new RecomendacionController($usuarioRepo);
$router->registrarGet('/recomendaciones', function () use ($recomendacionController) {
    AuthMiddleware::verificarAutenticacion();
    $recomendacionController->index();
});

$userController = new UserController($perfilService, $contenidoRepo);
$router->registrarGet('/profile', function () use ($userController) {
    AuthMiddleware::verificarAutenticacion();
    $userController->index();
});

$settingsController = new SettingsController($perfilService);
$router->registrarGet('/settings', function () use ($settingsController) {
    AuthMiddleware::verificarAutenticacion();
    $settingsController->index();
});
$router->registrarPost('/settings', function () use ($settingsController) {
    AuthMiddleware::verificarAutenticacion();
    $settingsController->actualizar();
});
$router->registrarGet('/settings/exportar', function () use ($settingsController) {
    AuthMiddleware::verificarAutenticacion();
    $settingsController->exportar();
});
$router->registrarPost('/settings/importar', function () use ($settingsController) {
    AuthMiddleware::verificarAutenticacion();
    $settingsController->importar();
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
