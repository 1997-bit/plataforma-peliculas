<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Session;
use App\Models\AdminContenidoRepo;
use App\Models\ContenidoRepo;
use App\Services\ContenidoValidator;
use App\Services\PosterUploader;
use App\Services\TmdbClient;

final class AdminController
{
    public function __construct(
        private AdminContenidoRepo $adminContenidoRepo,
        private ContenidoRepo $contenidoRepo,
        private TmdbClient $tmdbClient,
    ) {
    }

    public function index(): void
    {
        $csrf = Session::generarCsrf();
        $okMsg = $this->mensajeOk();
        $errorMsg = null;
        $contenidoLocal = $this->adminContenidoRepo->listarContenidoLocal(50);
        $generosMasVistos = $this->contenidoRepo->generosMasVistos(10);

        require ROOT . '/views/admin/index.php';
    }

    public function nuevo(): void
    {
        $csrf = Session::generarCsrf();

        $q = trim((string) ($_GET['q'] ?? ''));
        $tipoBusqueda = ($_GET['tipo'] ?? 'movie') === 'series' ? 'series' : 'movie';
        $resultados = [];

        if ($q !== '') {
            $recurso = $tipoBusqueda === 'series' ? 'tv' : 'movie';
            $data = $this->tmdbClient->fetch("/search/{$recurso}", [
                'query' => $q,
                'language' => 'es-MX',
                'include_adult' => 'false',
            ]);
            $resultados = $data['results'] ?? [];

            $tmdbIds = array_map(fn($r) => (int) ($r['id'] ?? 0), $resultados);
            $existentes = $this->adminContenidoRepo->existenPorTmdbId($tmdbIds);

            foreach ($resultados as &$res) {
                $res['_generos'] = array_values(array_filter(array_map(
                    fn($id) => \App\Helpers\GenerosTmdb::LISTA[$id] ?? null,
                    (array) ($res['genre_ids'] ?? [])
                )));
                $res['_existe'] = in_array((int) ($res['id'] ?? 0), $existentes, true);
            }
            unset($res);
        }

        $generos = $this->adminContenidoRepo->listarGeneros();
        $item = null;
        $generoIdsSeleccionados = [];
        $errorMsg = null;
        $tmdbPosterId = '';
        $tmdbBackdropId = '';
        $tmdbId = '';

        require ROOT . '/views/admin/agregar.php';
    }

    public function guardar(): void
    {
        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            require ROOT . '/views/errors/403.php';
            exit;
        }

        $tmdbPosterId = trim((string) ($_POST['tmdb_poster_path'] ?? ''));
        $tmdbBackdropId = trim((string) ($_POST['tmdb_backdrop_path'] ?? ''));
        $tmdbId = trim((string) ($_POST['tmdb_id'] ?? ''));

        $datos = ContenidoValidator::validar($_POST, $this->adminContenidoRepo->listarGeneros());

        $poster = PosterUploader::subir($_FILES['poster'] ?? null, obligatorio: $tmdbPosterId === '');
        if ($poster['error'] !== null) {
            $datos['errores'][] = $poster['error'];
        }

        if ($datos['errores'] !== []) {
            $csrf = Session::generarCsrf();
            $q = '';
            $tipoBusqueda = 'movie';
            $resultados = [];
            $generos = $this->adminContenidoRepo->listarGeneros();
            $item = $_POST;
            $generoIdsSeleccionados = $datos['generoIds'];
            $errorMsg = implode(' ', $datos['errores']);

            require ROOT . '/views/admin/agregar.php';
            return;
        }

        $posterFinal = $poster['path'];
        if ($posterFinal === null && $tmdbPosterId !== '') {
            $posterFinal = $this->adminContenidoRepo->descargarImagenTmdb($tmdbPosterId, 'w500');
        }

        $backdropFinal = null;
        if ($tmdbBackdropId !== '') {
            $backdropFinal = $this->adminContenidoRepo->descargarImagenTmdb($tmdbBackdropId, 'w1280');
        }

        $this->adminContenidoRepo->crearContenidoLocal(
            $datos['tipo'],
            $datos['titulo'],
            $datos['descripcion'],
            $posterFinal,
            $datos['anio'],
            $datos['generoIds'],
            (string) Session::obtener('user_id'),
            $tmdbId !== '' ? (int) $tmdbId : null,
            $backdropFinal,
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

    public function indexGeneros(): void
    {
        $csrf = Session::generarCsrf();
        $generos = $this->adminContenidoRepo->listarGeneros();
        $okMsg = match ($_GET['ok'] ?? '') {
            'creado' => 'Género creado.',
            'eliminado' => 'Género eliminado.',
            default => null,
        };

        require ROOT . '/views/admin/generos.php';
    }

    public function crearGenero(): void
    {
        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            require ROOT . '/views/errors/403.php';
            exit;
        }

        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        if ($nombre !== '') {
            $this->adminContenidoRepo->crearGenero($nombre);
        }

        header('Location: /admin/generos?ok=creado');
        exit;
    }

    public function eliminarGenero(): void
    {
        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            require ROOT . '/views/errors/403.php';
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->adminContenidoRepo->eliminarGenero($id);
        }

        header('Location: /admin/generos?ok=eliminado');
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
