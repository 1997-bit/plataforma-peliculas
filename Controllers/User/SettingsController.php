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
        // Este exportador para preferencias, forma parte de
        // la idea de intercambiar datos en XML sin romper la experiencia del usuario.
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

        // Aquí hice el flujo de carga de preferencias para que el usuario pueda
        // traer su configuración sin tocar el resto de su cuenta.
        $idUsuario = (string) Session::obtener('user_id');
        $archivo = $_FILES['xml_config'] ?? null;

        if (!is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errorMsg = 'Debes seleccionar un archivo XML válido.';
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
            return;
        }

        if (!is_string($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
            $errorMsg = 'No se pudo leer el archivo XML subido.';
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
            return;
        }

        $contenidoXml = file_get_contents($archivo['tmp_name']);
        if ($contenidoXml === false || trim($contenidoXml) === '') {
            $errorMsg = 'El archivo XML está vacío.';
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
            return;
        }

        $resultado = $this->perfilService->importarSettingsXml($idUsuario, $contenidoXml);

        if (!$resultado->success) {
            $errorMsg = $resultado->primerError();
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
            return;
        }

        Session::establecer('username', $resultado->username);

        $tema = $resultado->preferences['tema'] ?? null;
        if (is_string($tema) && $tema !== '') {
            setcookie('tema', $tema, [
                'expires' => time() + (365 * 24 * 3600),
                'path' => '/',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443),
                'httponly' => false,
                'samesite' => 'Strict',
            ]);
            $_COOKIE['tema'] = $tema;
        }

        header('Location: /settings?actualizado=1&importado=1');
        exit;
    }
}
