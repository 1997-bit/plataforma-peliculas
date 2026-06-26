<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Session;
use App\Helpers\GenerosTmdb;
use App\Services\User\PerfilService;

/**
 * Configuracion de cuenta: lo que solo el propio usuario puede ver/editar
 * (username, generos favoritos, tema, exportar/importar settings).
 * Separado de UserController (perfil de lectura) a proposito.
 */
final class SettingsController
{
    public function __construct(
        private PerfilService $perfilService,
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

        $generos = GenerosTmdb::comoLista();
        $generosFavoritos = $usuario->preferences['generos'] ?? [];
        $csrf = Session::generarCsrf();

        require ROOT . '/views/settings/index.php';
    }

    public function actualizar(): void
    {
        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            require ROOT . '/views/errors/403.php';
            exit;
        }

        $idUsuario = (string) Session::obtener('user_id');
        $username = (string) ($_POST['username'] ?? '');

        /** @var list<string> $generosCrudos */
        $generosCrudos = is_array($_POST['generos'] ?? null) ? $_POST['generos'] : [];

        $resultado = $this->perfilService->actualizarPerfil($idUsuario, $username, $generosCrudos);

        if (!$resultado->success) {
            $usuario = $this->perfilService->obtenerPerfil($idUsuario);
            $generos = GenerosTmdb::comoLista();
            $generosFavoritos = $generosCrudos !== [] ? array_map('intval', $generosCrudos) : ($usuario->preferences['generos'] ?? []);
            $errorMsg = $resultado->primerError();
            $csrf = Session::generarCsrf();

            require ROOT . '/views/settings/index.php';
            return;
        }

        // la navbar debe reflejar el cambio de nombre sin re-login.
        Session::establecer('username', $resultado->username);

        header('Location: /settings?actualizado=1');
    }

    /**
     * Descarga las preferencias del usuario como archivo XML.
     */
    public function exportar(): void
    {
        $idUsuario = (string) Session::obtener('user_id');
        $xml = $this->perfilService->exportarSettingsXml($idUsuario);

        header('Content-Type: application/xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="cineapp-settings.xml"');
        echo $xml;
    }

    /**
     * TODO: importar settings desde XML subido. PerfilService::importarSettingsXml
     * ya tiene el placeholder con la firma esperada; falta:
     *  - recibir el archivo via $_FILES, validar tamaño/mime antes de leer
     *  - llamar a $this->perfilService->importarSettingsXml($idUsuario, $contenido)
     *  - re-render de /settings con el resultado (igual que actualizar())
     * No implementado todavia, ver nota en PerfilService.
     */
    public function importar(): void
    {
        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            require ROOT . '/views/errors/403.php';
            exit;
        }

        http_response_code(501);
        header('Location: /settings?error=importar_no_implementado');
    }
}
