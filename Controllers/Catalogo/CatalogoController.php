<?php

declare(strict_types=1);

namespace App\Controllers\Catalogo;

use App\Core\Session;
use App\Helpers\GenerosTmdb;

final class CatalogoController
{
    private const TMDB_BASE = 'https://api.themoviedb.org/3';
    private const CACHE_DIR = ROOT . '/storage/tmdb_cache';
    private const CACHE_TTL = 3600; // 1 hora

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) ($_ENV['TMDB_API_KEY'] ?? '');
    }

    public function index(): void
    {
        $tipo = $_GET['tipo'] ?? 'movie';
        $tipo = in_array($tipo, ['movie', 'series'], true) ? $tipo : 'movie';

        $generoId = isset($_GET['genero']) && $_GET['genero'] !== '' ? (int) $_GET['genero'] : null;
        $busqueda = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $page = max(1, min(500, (int) ($_GET['page'] ?? 1)));

        $recursoTmdb = $tipo === 'series' ? 'tv' : 'movie';

        if ($busqueda !== '') {
            $resultado = $this->buscar($recursoTmdb, $busqueda, $page);
        } else {
            $resultado = $this->descubrir($recursoTmdb, $generoId, $page);
        }

        $items = $resultado['results'] ?? [];
        $totalPaginas = (int) ($resultado['total_pages'] ?? 1);

        $generos = GenerosTmdb::LISTA;
        $csrf = Session::generarCsrf();

        require ROOT . '/views/catalogo/index.php';
    }

    /**
     * Registra "vista" en este modo en-vivo: como no hay tabla content local,
     * por ahora solo confirma CSRF y responde ok. Cuando exista persistencia
     * propia de contenido (admin, #15) esto puede grabar view_history real.
     */
    public function registrarVista(): void
    {
        header('Content-Type: application/json');

        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'CSRF invalido']);
            return;
        }

        echo json_encode(['ok' => true]);
    }

    /**
     * @return array{results: list<array<string,mixed>>, total_pages?: int}
     */
    private function descubrir(string $recurso, ?int $generoId, int $page): array
    {
        $params = [
            'language' => 'es-MX',
            'sort_by' => 'popularity.desc',
            'include_adult' => 'false',
            'vote_count.gte' => '50',
            'vote_average.gte' => '5.5',
            'page' => (string) $page,
        ];

        if ($generoId !== null) {
            $params['with_genres'] = (string) $generoId;
        }

        return $this->fetch("/discover/{$recurso}", $params);
    }

    /**
     * @return array{results: list<array<string,mixed>>, total_pages?: int}
     */
    private function buscar(string $recurso, string $query, int $page): array
    {
        $params = [
            'language' => 'es-MX',
            'query' => $query,
            'include_adult' => 'false',
            'page' => (string) $page,
        ];

        return $this->fetch("/search/{$recurso}", $params);
    }

    /**
     * @param array<string,string> $params
     * @return array<string,mixed>
     */
    private function fetch(string $endpoint, array $params): array
    {
        $query = '?' . http_build_query($params);
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
        curl_close($ch);

        if (!$result) {
            return $this->leerCache($cacheFile, ignorarTtl: true) ?? ['results' => []];
        }

        $decoded = json_decode($result, true);
        $data = is_array($decoded) ? $decoded : ['results' => []];
        $this->escribirCache($cacheFile, $data);

        return $data;
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
