<?php

declare(strict_types=1);

namespace App\Controllers\Home;

use App\Core\Session;

class HomeController
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
    $username = Session::obtener('username');
    $csrf = Session::generarCsrf();
    $page = max(1, min(10, (int) ($_GET['page'] ?? 1)));
    $movies = $this->fetch('/discover/movie', $page);

    require ROOT . '/views/home.php';
  }

  private function fetch(string $endpoint, int $page = 1): array
  {
    $query = '?language=es-MX&page=' . $page .
      '&sort_by=popularity.desc' .
      '&without_genres=27,53' .
      '&vote_average.gte=6.0' .
      '&include_adult=false';

    $cacheKey = md5($endpoint . $query);
    $cacheFile = self::CACHE_DIR . '/' . $cacheKey . '.json';

    $cached = $this->leerCache($cacheFile);
    if ($cached !== null) {
      return $cached;
    }

    $ch = curl_init(self::TMDB_BASE . $endpoint . $query);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT        => 8,
      CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
        'Authorization: Bearer ' . $this->apiKey,
      ],
      CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $result = curl_exec($ch);
    curl_close($ch);

    if (!$result) {
      // Fallback: si la API falla, devuelve cache aunque esté vencida.
      return $this->leerCache($cacheFile, ignorarTtl: true) ?? [];
    }

    $movies = json_decode($result, true)['results'] ?? [];
    $this->escribirCache($cacheFile, $movies);

    return $movies;
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

  private function escribirCache(string $path, array $movies): void
  {
    if (!is_dir(self::CACHE_DIR)) {
      mkdir(self::CACHE_DIR, 0775, true);
    }
    file_put_contents($path, json_encode($movies));
  }
}
