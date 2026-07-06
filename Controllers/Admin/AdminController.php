<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Session;
use App\Helpers\GenerosTmdb;
use App\Helpers\Http;
use App\Helpers\TmdbTipo;
use App\Models\AdminContenidoRepo;
use App\Models\ContenidoRepo;
use App\Services\ContenidoValidator;
use App\Services\PosterUploader;
use App\Services\TmdbClient;
use PDOException;

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
        $conteoPorTipo = $this->adminContenidoRepo->contarPorTipo();
        $generosMasVistos = $this->contenidoRepo->generosMasVistos(10);

        require ROOT . '/views/admin/index.php';
    }

    public function nuevo(): void
    {
        $csrf = Session::generarCsrf();
        $okMsg = $this->mensajeOk();

        $q = trim((string) ($_GET['q'] ?? ''));
        $tipoBusqueda = TmdbTipo::normalizar($_GET['tipo'] ?? null);
        $resultados = [];

        if ($q !== '') {
            $recurso = TmdbTipo::aRecursoTmdb($tipoBusqueda);
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
                    fn($id) => GenerosTmdb::LISTA[$id] ?? null,
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
        Session::exigirCsrfOFallar();

        $esAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

        $tmdbPosterId = trim((string) ($_POST['tmdb_poster_path'] ?? ''));
        $tmdbBackdropId = trim((string) ($_POST['tmdb_backdrop_path'] ?? ''));
        $tmdbId = trim((string) ($_POST['tmdb_id'] ?? ''));

        $datos = ContenidoValidator::validar($_POST, $this->adminContenidoRepo->listarGeneros());

        $poster = PosterUploader::subir($_FILES['poster'] ?? null, obligatorio: $tmdbPosterId === '');
        if ($poster['error'] !== null) {
            $datos['errores'][] = $poster['error'];
        }

        if ($datos['errores'] !== []) {
            if ($esAjax) {
                $this->responderJson(422, ['errores' => $datos['errores']]);
                return;
            }

            $csrf = Session::generarCsrf();
            $okMsg = null;
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

        try {
            $id = $this->adminContenidoRepo->crearContenidoLocal(
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
        } catch (PDOException $e) {
            $mensaje = $this->mensajeErrorDb($e);

            if ($esAjax) {
                $this->responderJson(409, ['errores' => [$mensaje]]);
                return;
            }

            $csrf = Session::generarCsrf();
            $okMsg = null;
            $q = '';
            $tipoBusqueda = 'movie';
            $resultados = [];
            $generos = $this->adminContenidoRepo->listarGeneros();
            $item = $_POST;
            $generoIdsSeleccionados = $datos['generoIds'];
            $errorMsg = $mensaje;

            require ROOT . '/views/admin/agregar.php';
            return;
        }

        if ($esAjax) {
            $this->responderJson(201, ['id' => $id, 'mensaje' => 'Contenido creado correctamente.']);
            return;
        }

        header('Location: /admin/contenido/nuevo?contenido_creado=1');
        exit;
    }

    /** @param array<string,mixed> $payload */
    private function responderJson(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    /** Traduce el codigo de error MySQL a un mensaje legible; 1062 = UNIQUE (tmdb_id o titulo+tipo+anio duplicado). */
    private function mensajeErrorDb(PDOException $e): string
    {
        $codigo = (int) ($e->errorInfo[1] ?? 0);

        if ($codigo === 1062) {
            return 'Ya existe un contenido con ese título (o el mismo ID de TMDB). Revisa el catálogo antes de crear uno nuevo.';
        }

        return 'Error al guardar en la base de datos.';
    }

    public function editar(): void
    {
        $id = (string) ($_GET['id'] ?? '');
        $item = $this->cargarItemOFallar($id);

        $csrf = Session::generarCsrf();
        $generos = $this->adminContenidoRepo->listarGeneros();
        $generoIdsSeleccionados = $item['genero_ids'];
        $errorMsg = null;

        require ROOT . '/views/admin/contenido/editar.php';
    }

    public function actualizar(): void
    {
        Session::exigirCsrfOFallar();

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
            $item = array_merge($itemActual, $_POST, ['id' => $itemActual['id']]);
            $generoIdsSeleccionados = $datos['generoIds'];
            $errorMsg = implode(' ', $datos['errores']);

            require ROOT . '/views/admin/contenido/editar.php';
            return;
        }

        $posterFinal = $poster['path'] ?? $itemActual['poster_path'];

        try {
            $this->adminContenidoRepo->actualizarContenidoLocal(
                $itemActual['id'],
                $datos['titulo'],
                $datos['descripcion'],
                $posterFinal,
                $datos['anio'],
                $datos['generoIds']
            );
        } catch (PDOException $e) {
            $csrf = Session::generarCsrf();
            $generos = $this->adminContenidoRepo->listarGeneros();
            $item = array_merge($itemActual, $_POST, ['id' => $itemActual['id']]);
            $generoIdsSeleccionados = $datos['generoIds'];
            $errorMsg = $this->mensajeErrorDb($e);

            require ROOT . '/views/admin/contenido/editar.php';
            return;
        }

        header('Location: /admin?contenido_actualizado=1');
        exit;
    }

    public function eliminar(): void
    {
        Session::exigirCsrfOFallar();

        $id = (string) ($_POST['id'] ?? '');
        if ($id === '') {
            Http::error404();
        }
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
        Session::exigirCsrfOFallar();

        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        if ($nombre !== '') {
            $this->adminContenidoRepo->crearGenero($nombre);
        }

        header('Location: /admin/generos?ok=creado');
        exit;
    }

    public function eliminarGenero(): void
    {
        Session::exigirCsrfOFallar();

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
            Http::error404();
        }

        try {
            $item = $this->adminContenidoRepo->obtenerLocalPorIdParaEditar($id);
        } catch (\InvalidArgumentException) {
            $item = null;
        }

        if ($item === null) {
            Http::error404();
        }

        return $item;
    }

    private function mensajeOk(): ?string
    {
        return match (true) {
            isset($_GET['contenido_creado']) => 'Contenido creado correctamente.',
            isset($_GET['contenido_actualizado']) => 'Contenido actualizado correctamente.',
            isset($_GET['contenido_eliminado']) => 'Contenido eliminado.',
            default => null,
        };
    }
}
