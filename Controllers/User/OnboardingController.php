<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Session;
use App\Helpers\GenerosTmdb;
use App\Services\User\PerfilService;

/**
 * Onboarding de un solo paso: elegir generos favoritos justo despues
 * de registrarse. Reusa PerfilService::actualizarPerfil() (mismo
 * metodo que /settings) para no duplicar validacion/sanitizacion.
 *
 * No hay columna "onboarding_completo" en BD a proposito (evitar
 * migracion nueva): si el usuario ya tiene generos guardados, se
 * considera que ya paso por aqui y se redirige a /home.
 */
final class OnboardingController
{
    public function __construct(
        private PerfilService $perfilService,
    ) {
    }

    public function mostrar(): void
    {
        $idUsuario = (string) Session::obtener('user_id');
        $usuario = $this->perfilService->obtenerPerfil($idUsuario);

        if ($usuario === null) {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            return;
        }

        $generosFavoritos = $usuario->preferences['generos'] ?? [];
        if ($generosFavoritos !== []) {
            header('Location: /home');
            exit;
        }

        $generos = GenerosTmdb::comoLista();
        $csrf = Session::generarCsrf();

        require ROOT . '/views/onboarding/step1.php';
    }

    public function procesar(): void
    {
        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            require ROOT . '/views/errors/403.php';
            exit;
        }

        $idUsuario = (string) Session::obtener('user_id');
        $usuario = $this->perfilService->obtenerPerfil($idUsuario);

        if ($usuario === null) {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            return;
        }

        /** @var list<string> $generosCrudos */
        $generosCrudos = is_array($_POST['generos'] ?? null) ? $_POST['generos'] : [];

        $resultado = $this->perfilService->actualizarPerfil($idUsuario, $usuario->username, $generosCrudos);

        if (!$resultado->success) {
            $generos = GenerosTmdb::comoLista();
            $generosFavoritos = $generosCrudos !== [] ? array_map('intval', $generosCrudos) : [];
            $errorMsg = $resultado->primerError();
            $csrf = Session::generarCsrf();

            require ROOT . '/views/onboarding/step1.php';
            return;
        }

        header('Location: /home');
        exit;
    }
}
