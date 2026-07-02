<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/container.php';

use App\Core\Router;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\RegistroController;
use App\Controllers\Setup\SetupController;
use App\Middleware\AuthMiddleware;
use App\Controllers\Home\HomeController;
use App\Controllers\Catalogo\CatalogoController;
use App\Controllers\Catalogo\ContenidoController;
use App\Controllers\Catalogo\RecomendacionController;
use App\Controllers\Admin\AdminController;
use App\Controllers\User\UserController;
use App\Controllers\User\SettingsController;
use App\Controllers\User\OnboardingController;

$router = new Router();

$loginController = new LoginController($procesarLogin, $cerrarSesion);
$registroController = new RegistroController($registrarUsuario);
$router->registrarGet('/', function () {
    AuthMiddleware::redirigirSiAutenticado();
    require ROOT . '/views/landing.php';
});
$router->registrarGet('/privacidad', fn () => require ROOT . '/views/privacidad.php');
$router->registrarGet('/login', [$loginController, 'mostrarFormulario']);
$router->registrarPost('/login', [$loginController, 'login']);
$router->registrarPost('/logout', [$loginController, 'logout']);
$router->registrarGet('/register', [$registroController, 'mostrarFormulario']);
$router->registrarPost('/register', [$registroController, 'procesarRegistro']);

// Setup: crea el primer admin. Sin AuthMiddleware a proposito (todavia
// no hay nadie logueado la primera vez que se usa). Se autobloquea con
// 403 en cuanto existe cualquier admin (ver SetupController).
$setupController = new SetupController($usuarioRepo);
$router->registrarGet('/setup/admin', [$setupController, 'mostrar']);
$router->registrarPost('/setup/admin', [$setupController, 'crear']);

// Rutas protegidas (requieren sesion activa)
$homeController = new HomeController($contenidoRepo, $usuarioRepo, $tmdbClient, $adminContenidoRepo);
$router->registrarGet('/home', function () use ($homeController) {
    AuthMiddleware::bloquearRol('admin');
    $homeController->index();
});

$catalogoController = new CatalogoController($tmdbClient, $adminContenidoRepo);
$router->registrarGet('/catalogo', function () use ($catalogoController) {
    AuthMiddleware::bloquearRol('admin');
    $catalogoController->index();
});
$router->registrarPost('/catalogo/vista', function () use ($catalogoController) {
    AuthMiddleware::bloquearRol('admin');
    $catalogoController->registrarVista();
});

$contenidoController = new ContenidoController($contenidoRepo, $tmdbClient, $adminContenidoRepo);
$router->registrarGet('/contenido', function () use ($contenidoController) {
    AuthMiddleware::bloquearRol('admin');
    $contenidoController->index();
});
$router->registrarPost('/contenido/calificar', function () use ($contenidoController) {
    AuthMiddleware::bloquearRol('admin');
    $contenidoController->calificar();
});

$recomendacionController = new RecomendacionController($usuarioRepo, $tmdbClient, $adminContenidoRepo);
$router->registrarGet('/recomendaciones', function () use ($recomendacionController) {
    AuthMiddleware::bloquearRol('admin');
    $recomendacionController->index();
});

$userController = new UserController($perfilService, $contenidoRepo);
$router->registrarGet('/profile', function () use ($userController) {
    AuthMiddleware::bloquearRol('admin');
    $userController->index();
});

$settingsController = new SettingsController($perfilService);
$router->registrarGet('/settings', function () use ($settingsController) {
    AuthMiddleware::bloquearRol('admin');
    $settingsController->index();
});
$router->registrarPost('/settings', function () use ($settingsController) {
    AuthMiddleware::bloquearRol('admin');
    $settingsController->actualizar();
});
$router->registrarGet('/settings/exportar', function () use ($settingsController) {
    AuthMiddleware::bloquearRol('admin');
    $settingsController->exportar();
});
$router->registrarPost('/settings/importar', function () use ($settingsController) {
    AuthMiddleware::bloquearRol('admin');
    $settingsController->importar();
});

$onboardingController = new OnboardingController($perfilService);
$router->registrarGet('/onboarding', function () use ($onboardingController) {
    AuthMiddleware::bloquearRol('admin');
    $onboardingController->mostrar();
});
$router->registrarPost('/onboarding', function () use ($onboardingController) {
    AuthMiddleware::bloquearRol('admin');
    $onboardingController->procesar();
});

$adminController = new AdminController($adminContenidoRepo);
// Aquí se conectan las rutas XML al panel de admin sin mover el resto del enrutado.
$router->registrarGet('/admin', function () use ($adminController) {
    AuthMiddleware::requerirRol('admin');
    $adminController->index();
});
$router->registrarGet('/admin/xml/exportar', function () use ($adminController) {
    AuthMiddleware::requerirRol('admin');
    $adminController->exportarXml();
});
$router->registrarPost('/admin/xml/importar', function () use ($adminController) {
    AuthMiddleware::requerirRol('admin');
    $adminController->importarXml();
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
