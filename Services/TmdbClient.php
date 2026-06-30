<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Unico punto de acceso a la API de TMDB con cache en disco.
 *
 * Antes esta misma logica (fetch + cache en archivo) estaba copiada
 * en CatalogoController, ContenidoController, RecomendacionController
 * y HomeController, con pequeñas diferencias entre copias.  */
final class TmdbClient
{
    private const BASE_URL = 'https://api.themoviedb.org/3';
    private const CACHE_DIR = ROOT . '/storage/tmdb_cache';
    private const CACHE_TTL = 3600; // 1 hora

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) ($_ENV['TMDB_API_KEY'] ?? '');
    }

    /**
     * @param array<string,string> $params
     * @return array<string,mixed>
     */
    public function fetch(string $endpoint, array $params): array
    {
        $query = '?' . http_build_query($params);
        $cacheFile = $this->rutaCache($endpoint, $query);

        $cached = $this->leerCache($cacheFile);
        if ($cached !== null) {
            return $cached;
        }

        $ch = curl_init(self::BASE_URL . $endpoint . $query);
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
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!$result || $status >= 400) {
            if ($curlError !== '') {
                error_log("TMDB fetch error en {$endpoint}: {$curlError}");
            } elseif ($status >= 400) {
                error_log("TMDB fetch HTTP {$status} en {$endpoint}");
            }
            return $this->leerCache($cacheFile, ignorarTtl: true) ?? ['results' => []];
        }

        $decoded = json_decode($result, true);
        $data = is_array($decoded) ? $decoded : ['results' => []];
        $this->escribirCache($cacheFile, $data);

        return $data;
    }

    private function rutaCache(string $endpoint, string $query): string
    {
        return self::CACHE_DIR . '/' . md5($endpoint . $query) . '.json';
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
