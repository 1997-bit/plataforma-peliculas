<?php
// Lee .env manualmente sin depender de bootstrap
function leerEnv(string $key): string {
    $root = dirname(__DIR__); // asume que está en /public/
    $envFile = $root . '/.env';
    if (!file_exists($envFile)) return '';
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        if (trim($k) === $key) return trim($v, " \t\"'");
    }
    return '';
}

$apiKey = leerEnv('TMDB_API_KEY');
$base = 'https://api.themoviedb.org/3';

function tmdb(string $url): array {
    $ctx = stream_context_create(['http' => ['timeout' => 8]]);
    $t   = microtime(true);
    $raw = @file_get_contents($url, false, $ctx);
    $ms  = round((microtime(true) - $t) * 1000);
    if ($raw === false) return ['ok' => false, 'ms' => $ms, 'data' => null];
    $data = json_decode($raw, true);
    $ok   = !isset($data['success']) || $data['success'] !== false;
    return ['ok' => $ok, 'ms' => $ms, 'data' => $data];
}

$tests = [
    'configuration' => tmdb("$base/configuration?api_key=$apiKey"),
    'trending'=> tmdb("$base/trending/movie/week?api_key=$apiKey&language=es-MX"),
    'genres'=> tmdb("$base/genre/movie/list?api_key=$apiKey&language=es-MX"),
    'search'=> tmdb("$base/search/movie?api_key=$apiKey&query=Inception&language=es-MX"),
];
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>TMDb Test</title></head>
<body>
<h1>TMDb API Test</h1>
<p>Key cargada: <code><?= $apiKey ? substr($apiKey, 0, 6) . '...' : 'NO ENCONTRADA' ?></code></p>
<hr>

<?php foreach ($tests as $name => $r): ?>
<h3><?= $name ?> — <?= $r['ok'] ? 'OK' : 'FAIL' ?> (<?= $r['ms'] ?>ms)</h3>
<?php if (!$r['ok']): ?>
    <p><b>Error:</b> <?= htmlspecialchars($r['data']['status_message'] ?? 'sin respuesta') ?></p>
<?php else: ?>
    <details>
        <summary>Ver JSON</summary>
        <pre><?= htmlspecialchars(json_encode($r['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
    </details>
<?php endif ?>
<hr>
<?php endforeach ?>

<?php
$movies = $tests['trending']['data']['results'] ?? [];
if ($movies):
?>
<h2>Trending (<?= count($movies) ?>)</h2>
<ul>
<?php foreach (array_slice($movies, 0, 10) as $m): ?>
    <li><?= htmlspecialchars($m['title'] ?? $m['name'] ?? '?') ?>
        — <?= substr($m['release_date'] ?? '', 0, 4) ?>
        — ★<?= $m['vote_average'] ?></li>
<?php endforeach ?>
</ul>
<?php endif ?>

</body>
</html>