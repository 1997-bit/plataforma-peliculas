<?php

declare(strict_types=1);

namespace App\Controllers\Catalogo;

use App\Core\Session;
use App\Helpers\TmdbTipo;
use App\Models\AdminContenidoRepo;
use App\Models\ContenidoRepo;
use App\Services\TmdbClient;

final class ContenidoController
{
    public function __construct(
        private ContenidoRepo $contenidoRepo,
        private TmdbClient $tmdb,
        private AdminContenidoRepo $adminContenidoRepo,
    ) {
    }

    public function index(): void
    {
        $origen = ($_GET['origen'] ?? '') === 'local' ? 'local' : 'tmdb';

        if ($origen === 'local') {
            $this->mostrarLocal();
            return;
        }

        $this->mostrarTmdb();
    }

    /**
     * Detalle de contenido creado por un admin (sin tmdb_id real). No pega
     * a la API: todo sale de la tabla contenido. Se normaliza al mismo
     * shape minimo que espera views/catalogo/detalle.php (title, overview,
     * genres como list<{name}>, etc) para no tener que tocar esa vista.
     */
    private function mostrarLocal(): void
    {
        $contentId = (string) ($_GET['id'] ?? '');

        if ($contentId === '') {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            return;
        }

        $local = $this->adminContenidoRepo->buscarLocalPorId($contentId);
        if ($local === null) {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            return;
        }

        $idUsuario = (string) Session::obtener('user_id');
        $this->contenidoRepo->registrarVistaConThrottle($idUsuario, $contentId);

        $detalle = [
            'title' => $local['titulo'],
            'overview' => $local['descripcion'],
            'genres' => array_map(static fn (string $nombre): array => ['name' => $nombre], $local['generos']),
            'poster_path' => $local['poster_path'],
            'backdrop_path' => null,
            'release_date' => $local['anio_lanzamiento'] ? $local['anio_lanzamiento'] . '-01-01' : null,
        ];

        $miCalificacion = $this->contenidoRepo->obtenerCalificacionUsuario($idUsuario, $contentId);
        $infoLocal = [
            'rating_avg' => (float) $local['rating_avg'],
            'rating_count' => (int) $local['rating_count'],
        ];
        $cast = [];
        $backdropPath = null;
        $csrf = Session::generarCsrf();

        require ROOT . '/views/catalogo/detalle.php';
    }

    /** Detalle de contenido que viene de la API de TMDB (flujo original, sin cambios de comportamiento). */
    private function mostrarTmdb(): void
    {
        $tmdbId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $tipo = TmdbTipo::normalizar($_GET['tipo'] ?? null);
        $recurso = TmdbTipo::aRecursoTmdb($tipo);

        if ($tmdbId <= 0) {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            return;
        }

        $detalle = $this->fetchDetalle($recurso, $tmdbId);
        if ($detalle === null || empty($detalle['id'])) {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            return;
        }

        $generoIds = array_map(
            static fn (array $g): int => (int) $g['id'],
            $detalle['genres'] ?? []
        );

        $contentId = $this->contenidoRepo->upsertDesdeTmdb(
            tmdbId: $tmdbId,
            tipo: $recurso,
            titulo: $detalle['title'] ?? $detalle['name'] ?? 'Sin título',
            descripcion: $detalle['overview'] ?? null,
            posterPath: $detalle['poster_path'] ?? null,
            anio: $this->extraerAnio($detalle),
            generoIdsTmdb: $generoIds
        );

        $idUsuario = (string) Session::obtener('user_id');
        $this->contenidoRepo->registrarVistaConThrottle($idUsuario, $contentId);

        $infoLocal = $this->contenidoRepo->buscarPorTmdbId($tmdbId);
        $miCalificacion = $this->contenidoRepo->obtenerCalificacionUsuario($idUsuario, $contentId);

        $cast = array_slice($detalle['credits']['cast'] ?? [], 0, 12);

        // Backdrop random en cada visita: 'images' viene en el mismo
        // request via append_to_response (cero llamadas extra). Si el
        // titulo no tiene backdrops alternativos, cae al principal.
        $backdropPath = $this->elegirBackdropRandom($detalle);

        $csrf = Session::generarCsrf();

        require ROOT . '/views/catalogo/detalle.php';
    }

    public function calificar(): void
    {
        header('Content-Type: application/json');

        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'CSRF invalido']);
            return;
        }

        $contentId = (string) ($_POST['content_id'] ?? '');
        $estrellas = (int) ($_POST['estrellas'] ?? 0);

        if ($contentId === '' || $estrellas < 1 || $estrellas > 5) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos invalidos']);
            return;
        }

        $idUsuario = (string) Session::obtener('user_id', '');
        if ($idUsuario === '') {
            http_response_code(401);
            echo json_encode(['error' => 'No autenticado']);
            return;
        }

        try {
            if (!$this->contenidoRepo->existeContenido($contentId)) {
                http_response_code(404);
                echo json_encode(['error' => 'Contenido no encontrado']);
                return;
            }

            $score = $estrellas * 2;
            $this->contenidoRepo->calificar($idUsuario, $contentId, $score);

            echo json_encode(['ok' => true]);
        } catch (\Throwable $e) {
            error_log('ContenidoController::calificar - ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error interno']);
        }
    }

    /** @return array<string,mixed>|null */
    private function fetchDetalle(string $recurso, int $tmdbId): ?array
    {
        $params = [
            'language' => 'es-MX',
            'append_to_response' => 'credits,images',
            'include_image_language' => 'es,en,null',
        ];

        return $this->tmdb->fetch("/{$recurso}/{$tmdbId}", $params) ?: null;
    }

    /**
     * Elige un backdrop al azar entre los disponibles del titulo para
     * que el hero de detalle no se vea siempre igual. Cae al backdrop
     * principal si 'images' no trajo alternativas (titulo con pocas
     * imagenes subidas a TMDB).
     *
     * @param array<string,mixed> $detalle
     */
    private function elegirBackdropRandom(array $detalle): ?string
    {
        $backdrops = $detalle['images']['backdrops'] ?? [];
        if ($backdrops === []) {
            return $detalle['backdrop_path'] ?? null;
        }

        $elegido = $backdrops[array_rand($backdrops)];

        return $elegido['file_path'] ?? ($detalle['backdrop_path'] ?? null);
    }

    /** @param array<string,mixed> $detalle */
    private function extraerAnio(array $detalle): ?int
    {
        $fecha = $detalle['release_date'] ?? $detalle['first_air_date'] ?? null;
        return $fecha ? (int) substr((string) $fecha, 0, 4) : null;
    }
}
