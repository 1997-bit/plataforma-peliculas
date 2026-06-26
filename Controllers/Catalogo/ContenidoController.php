<?php

declare(strict_types=1);

namespace App\Controllers\Catalogo;

use App\Core\Session;
use App\Models\ContenidoRepo;

final class ContenidoController
{
    private const TMDB_BASE = 'https://api.themoviedb.org/3';
    private const CACHE_DIR = ROOT . '/storage/tmdb_cache';
    private const CACHE_TTL = 3600;

    private string $apiKey;

    public function __construct(private ContenidoRepo $contenidoRepo)
    {
        $this->apiKey = (string) ($_ENV['TMDB_API_KEY'] ?? '');
    }

    public function index(): void
    {
        $tmdbId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $tipo = ($_GET['tipo'] ?? 'movie') === 'series' ? 'tv' : 'movie';

        if ($tmdbId <= 0) {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            return;
        }

        $detalle = $this->fetchDetalle($tipo, $tmdbId);
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
            tipo: $tipo,
            titulo: $detalle['title'] ?? $detalle['name'] ?? 'Sin título',
            descripcion: $detalle['overview'] ?? null,
            posterPath: $detalle['poster_path'] ?? null,
            anio: $this->extraerAnio($detalle),
            generoIdsTmdb: $generoIds
        );

        $userId = (string) Session::obtener('user_id');
        $this->contenidoRepo->registrarVista($userId, $contentId);

        $infoLocal = $this->contenidoRepo->buscarPorTmdbId($tmdbId);
        $miCalificacion = $this->contenidoRepo->obtenerCalificacionUsuario($userId, $contentId);

        $cast = array_slice($detalle['credits']['cast'] ?? [], 0, 12);
        $csrf = Session::generarCsrf();
<?php

declare(strict_types=1);

namespace App\Controllers\Catalogo;

use App\Core\Session;
use App\Helpers\TmdbTipo;
use App\Models\ContenidoRepo;
use App\Services\TmdbClient;

final class ContenidoController
{
    public function __construct(
        private ContenidoRepo $contenidoRepo,
        private TmdbClient $tmdb,
    ) {
    }

    public function index(): void
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
        $params = ['language' => 'es-MX', 'append_to_response' => 'credits'];

        return $this->tmdb->fetch("/{$recurso}/{$tmdbId}", $params) ?: null;
    }

    /** @param array<string,mixed> $detalle */
    private function extraerAnio(array $detalle): ?int
    {
        $fecha = $detalle['release_date'] ?? $detalle['first_air_date'] ?? null;
        return $fecha ? (int) substr((string) $fecha, 0, 4) : null;
    }
}
