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

        $userId = (string) Session::obtener('user_id');
        $score = $estrellas * 2;

        $this->contenidoRepo->calificar($userId, $contentId, $score);

        echo json_encode(['ok' => true]);
    }

    /** @return array<string,mixed>|null */
    private function fetchDetalle(string $tipo, int $tmdbId): ?array
    {
        $params = ['language' => 'es-MX', 'append_to_response' => 'credits'];
        $query = '?' . http_build_query($params);
        $endpoint = "/{$tipo}/{$tmdbId}";

        $cacheKey = md5($endpoint . $query);
        $cacheFile = self::CACHE_DIR . '/' . $cacheKey . '.json';

        $cached = $this->leerCache($cacheFile);
        if ($cached !== null) {
            return $cached;
        }

        $ch = curl_init(self::TMDB_BASE . $endpoint . $query);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$result || $status >= 400) {
            return $this->leerCache($cacheFile, ignorarTtl: true);
        }

        $decoded = json_decode($result, true);
        if (!is_array($decoded)) {
            return null;
        }

        $this->escribirCache($cacheFile, $decoded);

        return $decoded;
    }

    /** @param array<string,mixed> $detalle */
    private function extraerAnio(array $detalle): ?int
    {
        $fecha = $detalle['release_date'] ?? $detalle['first_air_date'] ?? null;
        return $fecha ? (int) substr((string) $fecha, 0, 4) : null;
    }

    private function leerCache(string $path, bool $ignorarTtl = false): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        if (!$ignorarTtl && (time() - filemtime($path)) > self::CACHE_TTL) {
            return null;
        }

        $contenido = file_get_contents($path);
        if ($contenido === false) {
            return null;
        }

        $decoded = json_decode($contenido, true);
        return is_array($decoded) ? $decoded : null;
    }

    /** @param array<string,mixed> $data */
    private function escribirCache(string $path, array $data): void
    {
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0775, true);
        }
        file_put_contents($path, json_encode($data));
    }
}
