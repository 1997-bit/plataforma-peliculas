<?php
/** @var string $csrf */
/** @var list<array<string,mixed>> $items */
/** @var bool $hasMore */
/** @var string $tipoActual */
/** @var bool $sinGenerosElegidos */
$tema = $_COOKIE['tema'] ?? null;

$renderEstrella = static function (): string {
    return '<svg class="feed-estrella-icono" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" aria-hidden="true">'
        . '<rect width="256" height="256" fill="none"/>'
        . '<path d="M239.18,97.26A16.38,16.38,0,0,0,224.92,86l-59-4.76L143.14,26.15a16.36,16.36,0,0,0-30.27,0L90.11,81.23,31.08,86a16.46,16.46,0,0,0-9.37,28.86l45,38.83L53,211.75a16.4,16.4,0,0,0,24.5,17.82L128,198.49l50.53,31.08A16.4,16.4,0,0,0,203,211.75l-13.76-58.07,45-38.83A16.43,16.43,0,0,0,239.18,97.26Z"/>'
        . '</svg>';
};
?>
<!DOCTYPE html>
<html lang="es" <?= $tema ? 'data-tema="' . htmlspecialchars($tema, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Para ti</title>
    <link rel="stylesheet" href="/assets/css/navbar.css" />
    <link rel="stylesheet" href="/assets/css/base.css"/>
    <link rel="stylesheet" href="/assets/css/tokens.css"/>
    <link rel="stylesheet" href="/assets/css/componentes.css"/>
    <link rel="stylesheet" href="/assets/css/recomendaciones.css"/>
</head>
<body class="cuerpo-feed">
    <?php require ROOT . '/views/partials/nav.php'; ?>

    <?php if ($items === [] && $sinGenerosElegidos): ?>
        <main class="feed-vacio">
            <p>Todavía no elegiste géneros favoritos.</p>
            <a href="/profile" class="feed-vacio-link">Elígelos en tu perfil</a>
        </main>
    <?php elseif ($items === []): ?>
        <main class="feed-vacio">
            <p>No encontramos contenido para tus géneros elegidos.</p>
            <a href="/profile" class="feed-vacio-link">Probar con otros géneros</a>
        </main>
    <?php else: ?>
        <main class="feed" data-feed data-tipo="<?= htmlspecialchars($tipoActual, ENT_QUOTES, 'UTF-8') ?>" data-offset="<?= count($items) ?>" data-has-more="<?= $hasMore ? '1' : '0' ?>">
            <div class="feed-tipo-switch">
                <a href="/recomendaciones?tipo=movie" class="feed-tipo-boton <?= $tipoActual === 'movie' ? 'feed-tipo-activo' : '' ?>">Películas</a>
                <a href="/recomendaciones?tipo=series" class="feed-tipo-boton <?= $tipoActual === 'series' ? 'feed-tipo-activo' : '' ?>">Series</a>
            </div>
            <div class="feed-lista" data-feed-lista>
                <?php foreach ($items as $item): ?>
                    <?php require ROOT . '/views/catalogo/partials/feed-slide.php'; ?>
                <?php endforeach; ?>
            </div>
            <p class="feed-cargando" data-feed-cargando hidden>Cargando más recomendaciones…</p>
            <p class="feed-fin" data-feed-fin hidden>No hay más recomendaciones por ahora.</p>
            <div class="feed-sentinel" data-feed-sentinel aria-hidden="true"></div>
        </main>
    <?php endif; ?>
    <script id="feed-plantilla-estrella" type="text/plain"><?= $renderEstrella() ?></script>
    <script src="/assets/js/recomendaciones.js" defer></script>
</body>
</html>
