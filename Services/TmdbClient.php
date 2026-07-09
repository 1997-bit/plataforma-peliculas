<?php

declare(strict_types=1);

namespace App\Services;

final class TmdbClient
{
    private const BASE_URL = 'https://api.themoviedb.org/3';

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) ($_ENV['TMDB_API_KEY'] ?? '');
    }

    /** @param array<string,string> $params @return array<string,mixed> */
    public function fetch(string $endpoint, array $params): array
    {
        $url = self::BASE_URL . $endpoint . '?' . http_build_query($params);

        $ch = curl_init($url);
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
            return ['results' => []];
        }

        $decoded = json_decode($result, true);
        return is_array($decoded) ? $decoded : ['results' => []];
    }
}
