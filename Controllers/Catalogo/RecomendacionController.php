<?php

declare(strict_types=1);

namespace App\Controllers\Catalogo;

use App\Core\Session;
use App\Models\UserRepo;

/**
 * Recomendaciones basicas: toma los generos favoritos guardados en
 * preferences.generos (perfil del usuario) y trae contenido de TMDB
 * que matchee esos generos via with_genres (OR logico).
 *
 * Es deliberadamente simple: no hay scoring, no hay collaborative
 * filtering, no hay peso por historial. Es el primer corte funcional;
 * si despues se quiere mezclar con view_history/ratings, este es el
 * punto de entrada a extender (ver TODO abajo).
 */
final class RecomendacionController
{
    private const TMDB_BASE = 'https://api.themoviedb.org/3';
    private const CACHE_DIR = ROOT . '/storage/tmdb_cache';
    private const CACHE_TTL = 3600;

    private string $apiKey;

    public function __construct(private UserRepo $userRepo)
    {
        $this->apiKey = (string) ($_ENV['TMDB_API_KEY'] ?? '');
    }

    public function index(): void
    {
        $idUsuario = (string) Session::obtener('user_id', '');
        $usuario = $idUsuario !== '' ? $this->userRepo->buscarPorId($idUsuario) : null;

        if ($usuario === null) {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            return;
        }

        $generosFavoritos = $usuario->preferences['generos'] ?? [];
        $tipo = ($_GET['tipo'] ?? 'movie') === 'series' ? 'series' : 'movie';
        $page = max(1, min(500, (int) ($_GET['page'] ?? 1)));

        $sinGenerosElegidos = $generosFavoritos === [];

        if ($sinGenerosElegidos) {
            // Sin generos elegidos no hay base para recomendar nada;
            // se muestra el estado vacio y se manda a elegir en /profile.
            $items = [];
            $totalPaginas = 1;
        } else {
            $recurso = $tipo === 'series' ? 'tv' : 'movie';
            $resultado = $this->descubrirPorGeneros($recurso, $generosFavoritos, $page);
            $items = $resultado['results'] ?? [];
            $totalPaginas = (int) ($resultado['total_pages'] ?? 1);
        }

        $tipoActual = $tipo;
        $csrf = Session::generarCsrf();

        require ROOT . '/views/catalogo/recomendaciones.php';
    }

    /**
     * @param list<int> $generoIds
     * @return array{results: list<array<string,mixed>>, total_pages?: int}
     */
    private function descubrirPorGeneros(string $recurso, array $generoIds, int $page): array
    {
        $params = [
            'language' => 'es-MX',
            'sort_by' => 'popularity.desc',
            'include_adult' => 'false',
            'vote_count.gte' => '50',
            'vote_average.gte' => '5.5',
            'with_genres' => implode('|', $generoIds),
            'page' => (string) $page,
        ];

        // certification solo existe en discover/movie, TV no lo soporta.
        if ($recurso === 'movie') {
            $params['certification_country'] = 'US';
            $params['certification.lte'] = 'R';
        }

        return $this->fetch("/discover/{$recurso}", $params);
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
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!$result) {
            if ($curlError !== '') {
                error_log('TMDB recomendaciones curl error: ' . $curlError);
            }
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
