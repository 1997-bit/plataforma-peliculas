<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Session;
use App\Models\ContenidoRepo;
use App\Services\User\PerfilService;

/**
 * Perfil de lectura: lo que el usuario ve de si mismo (y, mas adelante,
 * lo que otros podrian ver de el). No tiene edicion; eso vive en
 * SettingsController, que es la parte privada de la cuenta.
 */
final class UserController
{
    public function __construct(
        private PerfilService $perfilService,
        private ContenidoRepo $contenidoRepo,
    ) {
    }

    public function index(): void
    {
        $idUsuario = (string) Session::obtener('user_id');
        $usuario = $this->perfilService->obtenerPerfil($idUsuario);

        if ($usuario === null) {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            return;
        }

        $historial = $this->contenidoRepo->historialReciente($idUsuario, 10);
        $calificaciones = $this->contenidoRepo->calificacionesDeUsuario($idUsuario, 50);

        require ROOT . '/views/profile/index.php';
    }
}
