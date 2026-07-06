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
use App\Controllers\Admin\AdminController;
use App\Controllers\Admin\TmdbImportController;
use App\Controllers\Api\V1\ContenidoApiController;
use App\Controllers\User\UserController;
use App\Controllers\User\SettingsController;
use App\Controllers\User\OnboardingController;

$router = new Router();

// Auth
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

// --- User ---
$homeController = new HomeController($contenidoRepo, $usuarioRepo, $adminContenidoRepo);
$router->registrarGet('/home', function () use ($homeController) {
    AuthMiddleware::bloquearRol('admin');
    $homeController->index();
});

$catalogoController = new CatalogoController($adminContenidoRepo);
$router->registrarGet('/catalogo', function () use ($catalogoController) {
    AuthMiddleware::bloquearRol('admin');
    $catalogoController->index();
});

$contenidoController = new ContenidoController($contenidoRepo, $adminContenidoRepo);
$router->registrarGet('/contenido', function () use ($contenidoController) {
    AuthMiddleware::bloquearRol('admin');
    $contenidoController->index();
});
$router->registrarPost('/contenido/calificar', function () use ($contenidoController) {
    AuthMiddleware::bloquearRol('admin');
    $contenidoController->calificar();
});

$recomendacionController = new RecomendacionController($usuarioRepo, $adminContenidoRepo);
$router->registrarGet('/recomendaciones', function () use ($recomendacionController) {
    AuthMiddleware::bloquearRol('admin');
    $recomendacionController->index();
});
$router->registrarGet('/recomendaciones/mas', function () use ($recomendacionController) {
    AuthMiddleware::bloquearRol('admin');
    $recomendacionController->mas();
});

$userController = new UserController($perfilService, $contenidoRepo);
$router->registrarGet('/perfil', function () use ($userController) {
    AuthMiddleware::bloquearRol('admin');
    $userController->index();
});

$settingsController = new SettingsController($perfilService);
$router->registrarGet('/settings', function () use ($settingsController) { AuthMiddleware::bloquearRol('admin'); $settingsController->index(); });
$router->registrarPost('/settings', function () use ($settingsController) { AuthMiddleware::bloquearRol('admin'); $settingsController->actualizar(); });
$router->registrarGet('/settings/exportar', function () use ($settingsController) { AuthMiddleware::bloquearRol('admin'); $settingsController->exportar(); });
$router->registrarPost('/settings/importar', function () use ($settingsController) { AuthMiddleware::bloquearRol('admin'); $settingsController->importar(); });

$onboardingController = new OnboardingController($perfilService);
$router->registrarGet('/onboarding', function () use ($onboardingController) { AuthMiddleware::bloquearRol('admin'); $onboardingController->mostrar(); });
$router->registrarPost('/onboarding', function () use ($onboardingController) { AuthMiddleware::bloquearRol('admin'); $onboardingController->procesar(); });

// Admin
$adminController = new AdminController($adminContenidoRepo, $contenidoRepo, $tmdbClient);
$router->registrarGet('/admin', function () use ($adminController) { AuthMiddleware::requerirRol('admin'); $adminController->index(); });
$router->registrarGet('/admin/generos', function () use ($adminController) { AuthMiddleware::requerirRol('admin'); $adminController->indexGeneros(); });
$router->registrarPost('/admin/generos/crear', function () use ($adminController) { AuthMiddleware::requerirRol('admin'); $adminController->crearGenero(); });
$router->registrarPost('/admin/generos/eliminar', function () use ($adminController) { AuthMiddleware::requerirRol('admin'); $adminController->eliminarGenero(); });
$router->registrarGet('/admin/contenido/nuevo', function () use ($adminController) { AuthMiddleware::requerirRol('admin'); $adminController->nuevo(); });
$router->registrarPost('/admin/contenido/nuevo', function () use ($adminController) { AuthMiddleware::requerirRol('admin'); $adminController->guardar(); });
$router->registrarGet('/admin/contenido/editar', function () use ($adminController) { AuthMiddleware::requerirRol('admin'); $adminController->editar(); });
$router->registrarPost('/admin/contenido/editar', function () use ($adminController) { AuthMiddleware::requerirRol('admin'); $adminController->actualizar(); });
$router->registrarPost('/admin/contenido/eliminar', function () use ($adminController) { AuthMiddleware::requerirRol('admin'); $adminController->eliminar(); });

// TMDB import (solo POST, el buscar vive en /admin/contenido/nuevo)
$tmdbImportController = new TmdbImportController($tmdbClient, $adminContenidoRepo);
$router->registrarPost('/admin/tmdb/importar', function () use ($tmdbImportController) { AuthMiddleware::requerirRol('admin'); $tmdbImportController->importar(); });

// API REST
$contenidoApiController = new ContenidoApiController($adminContenidoRepo);
$router->registrarPost('/api/v1/contenido', [$contenidoApiController, 'crear']);
$router->registrarPut('/api/v1/contenido', [$contenidoApiController, 'actualizar']);
$router->registrarDelete('/api/v1/contenido', [$contenidoApiController, 'eliminar']);
$router->registrarGet('/api/v1/generos', [$contenidoApiController, 'generos']);

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
