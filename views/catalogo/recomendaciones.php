<?php
use App\Helpers\IconoEstrella;
/** @var string $csrf */
/** @var list<array<string,mixed>> $items */
/** @var bool $hasMore */
/** @var string $tipoActual */
/** @var bool $sinGenerosElegidos */
$tema = $_COOKIE['tema'] ?? null;
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
            <a href="/perfil" class="feed-vacio-link">Elígelos en tu perfil</a>
        </main>
    <?php elseif ($items === []): ?>
        <main class="feed-vacio">
            <p>No encontramos contenido para tus géneros elegidos.</p>
            <a href="/perfil" class="feed-vacio-link">Probar con otros géneros</a>
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
    <template id="feed-plantilla-estrella"><?= IconoEstrella::svg('feed-estrella-icono') ?></template>
    <script src="/assets/js/recomendaciones.js" defer></script>
</body>
</html>
