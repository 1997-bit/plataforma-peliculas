<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Session;
use App\Models\AdminContenidoRepo;
use App\Services\ContenidoValidator;
use App\Services\PosterUploader;

final class AdminController
{
    public function __construct(private AdminContenidoRepo $adminContenidoRepo)
    {
    }

    public function index(): void
    {
        $csrf = Session::generarCsrf();
        $okMsg = $this->mensajeOk();
        $errorMsg = null;
        $contenidoLocal = $this->adminContenidoRepo->listarContenidoLocal(50);

        require ROOT . '/views/admin/index.php';
    }

    public function nuevo(): void
    {
        $csrf = Session::generarCsrf();
        $generos = $this->adminContenidoRepo->listarGeneros();
        $modo = 'crear';
        $item = null;
        $generoIdsSeleccionados = [];
        $errorMsg = null;

        require ROOT . '/views/admin/form.php';
    }

    public function guardar(): void
    {
        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            require ROOT . '/views/errors/403.php';
            exit;
        }

        $datos = ContenidoValidator::validar($_POST, $this->adminContenidoRepo->listarGeneros());

        $poster = PosterUploader::subir($_FILES['poster'] ?? null, obligatorio: true);
        if ($poster['error'] !== null) {
            $datos['errores'][] = $poster['error'];
        }

        if ($datos['errores'] !== []) {
            $csrf = Session::generarCsrf();
            $generos = $this->adminContenidoRepo->listarGeneros();
            $modo = 'crear';
            $item = $_POST;
            $generoIdsSeleccionados = $datos['generoIds'];
            $errorMsg = implode(' ', $datos['errores']);

            require ROOT . '/views/admin/form.php';
            return;
        }

        $this->adminContenidoRepo->crearContenidoLocal(
            $datos['tipo'],
            $datos['titulo'],
            $datos['descripcion'],
            $poster['path'],
            $datos['anio'],
            $datos['generoIds'],
            (string) Session::obtener('user_id')
        );

        header('Location: /admin?contenido_creado=1');
        exit;
    }

    public function editar(): void
    {
        $id = (string) ($_GET['id'] ?? '');
        $item = $this->cargarItemOFallar($id);

        $csrf = Session::generarCsrf();
        $generos = $this->adminContenidoRepo->listarGeneros();
        $modo = 'editar';
        $generoIdsSeleccionados = $item['genero_ids'];
        $errorMsg = null;

        require ROOT . '/views/admin/form.php';
    }

    public function actualizar(): void
    {
        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            require ROOT . '/views/errors/403.php';
            exit;
        }

        $id = (string) ($_GET['id'] ?? $_POST['id'] ?? '');
        $itemActual = $this->cargarItemOFallar($id);

        $datos = ContenidoValidator::validar($_POST, $this->adminContenidoRepo->listarGeneros());

        $poster = PosterUploader::subir($_FILES['poster'] ?? null, obligatorio: false);
        if ($poster['error'] !== null) {
            $datos['errores'][] = $poster['error'];
        }

        if ($datos['errores'] !== []) {
            $csrf = Session::generarCsrf();
            $generos = $this->adminContenidoRepo->listarGeneros();
            $modo = 'editar';
            $item = array_merge($itemActual, $_POST, ['id' => $itemActual['id']]);
            $generoIdsSeleccionados = $datos['generoIds'];
            $errorMsg = implode(' ', $datos['errores']);

            require ROOT . '/views/admin/form.php';
            return;
        }

        $posterFinal = $poster['path'] ?? $itemActual['poster_path'];

        $this->adminContenidoRepo->actualizarContenidoLocal(
            $itemActual['id'],
            $datos['titulo'],
            $datos['descripcion'],
            $posterFinal,
            $datos['anio'],
            $datos['generoIds']
        );

        header('Location: /admin?contenido_actualizado=1');
        exit;
    }

    public function eliminar(): void
    {
        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            require ROOT . '/views/errors/403.php';
            exit;
        }

        $id = (string) ($_POST['id'] ?? '');
        $this->cargarItemOFallar($id);
        $this->adminContenidoRepo->eliminarContenidoLocal($id);

        header('Location: /admin?contenido_eliminado=1');
        exit;
    }

    private function cargarItemOFallar(string $id): array
    {
        if ($id === '') {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            exit;
        }

        try {
            $item = $this->adminContenidoRepo->obtenerLocalPorIdParaEditar($id);
        } catch (\InvalidArgumentException) {
            $item = null;
        }

        if ($item === null) {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            exit;
        }

        return $item;
    }

    private function mensajeOk(): ?string
    {
        if (isset($_GET['contenido_creado']))      return 'Contenido creado correctamente.';
        if (isset($_GET['contenido_actualizado'])) return 'Contenido actualizado correctamente.';
        if (isset($_GET['contenido_eliminado']))   return 'Contenido eliminado.';

        return null;
    }
}
