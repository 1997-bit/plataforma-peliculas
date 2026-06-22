<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

$key = $_ENV['TMDB_API_KEY'] ?? 'NO ENCONTRADA';
echo "Key: " . substr($key, 0, 20) . "...<br>";

$ch = curl_init('https://api.themoviedb.org/3/trending/all/day?language=es-MX&page=1');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
        'Authorization: Bearer ' . $key,
    ],
    CURLOPT_SSL_VERIFYPEER => true,
]);

$result = curl_exec($ch);
$err    = curl_error($ch);
$code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP: $code<br>";
echo "Error cURL: $err<br>";
echo "<pre>" . htmlspecialchars(substr($result, 0, 500)) . "</pre>";
