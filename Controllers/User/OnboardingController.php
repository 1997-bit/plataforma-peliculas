<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Session;
use App\Helpers\Http;
use App\Models\AdminContenidoRepo;
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
        private AdminContenidoRepo $adminContenidoRepo,
    ) {
    }

    public function mostrar(): void
    {
        $idUsuario = (string) Session::obtener('user_id');
        $usuario = $this->perfilService->obtenerPerfil($idUsuario);

        if ($usuario === null) {
            Http::error404();
        }

        $generosFavoritos = $usuario->preferences['generos'] ?? [];
        if ($generosFavoritos !== []) {
            header('Location: /home');
            exit;
        }

        $generos = $this->adminContenidoRepo->listarGeneros();
        $csrf = Session::generarCsrf();

        require ROOT . '/views/onboarding/step1.php';
    }

    public function procesar(): void
    {
        Session::exigirCsrfOFallar();

        $idUsuario = (string) Session::obtener('user_id');
        $usuario = $this->perfilService->obtenerPerfil($idUsuario);

        if ($usuario === null) {
            Http::error404();
        }

        /** @var list<string> $generosCrudos */
        $generosCrudos = is_array($_POST['generos'] ?? null) ? $_POST['generos'] : [];

        $resultado = $this->perfilService->actualizarPerfil($idUsuario, $usuario->username, $generosCrudos);

        if (!$resultado->success) {
            $generos = $this->adminContenidoRepo->listarGeneros();
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
