<?php
use App\Helpers\TmdbImagen;
/** @var string $csrf */
/** @var list<array<string,mixed>> $items */
/** @var int $totalPaginas */
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
            <a href="/profile" class="feed-vacio-link">Elígelos en tu perfil</a>
        </main>
    <?php elseif ($items === []): ?>
        <main class="feed-vacio">
            <p>No encontramos contenido para tus géneros elegidos.</p>
            <a href="/profile" class="feed-vacio-link">Probar con otros géneros</a>
        </main>
    <?php else: ?>
        <main class="feed" data-feed>
            <div class="feed-tipo-switch">
                <a href="/recomendaciones?tipo=movie" class="feed-tipo-boton <?= $tipoActual === 'movie' ? 'feed-tipo-activo' : '' ?>">Películas</a>
                <a href="/recomendaciones?tipo=series" class="feed-tipo-boton <?= $tipoActual === 'series' ? 'feed-tipo-activo' : '' ?>">Series</a>
            </div>
            <?php foreach ($items as $item): ?>
                <?php
                    $titulo = htmlspecialchars($item['title'] ?? 'Sin título', ENT_QUOTES, 'UTF-8');
                    $fecha = $item['release_date'] ?? '';
                    $anio = $fecha !== '' ? substr($fecha, 0, 4) : '';
                    $overview = htmlspecialchars($item['overview'] ?? 'Sin descripción disponible.', ENT_QUOTES, 'UTF-8');
                    $backdrop = TmdbImagen::backdrop($item['backdrop_path'] ?? null);
                    $poster = TmdbImagen::poster($item['poster_path'] ?? null, 'sm');
                    $tipoUrlItem = $item['type'] === 'series' ? 'series' : 'movie';
                ?>
                <section class="feed-slide" data-feed-slide>
                    <div class="feed-fondo" <?= $backdrop ? 'style="background-image: url(' . htmlspecialchars($backdrop, ENT_QUOTES, 'UTF-8') . ')"' : '' ?>></div>
                    <div class="feed-veladura"></div>
                    <div class="feed-info" data-feed-info>
                        <div class="poster-marco poster-marco--sm feed-poster">
                            <img class="poster-img" src="<?= $poster ?>" alt="" loading="lazy" draggable="false">
                        </div>
                        <div class="feed-texto">
                            <h2 class="feed-titulo">
                                <?= $titulo ?>
                                <?php if ($anio !== ''): ?><span class="feed-anio"><?= $anio ?></span><?php endif; ?>
                            </h2>
                            <p class="feed-sinopsis"><?= $overview ?></p>
                            <a href="/contenido?id=<?= urlencode((string) $item['id']) ?>&tipo=<?= $tipoUrlItem ?>" class="boton boton--claro feed-boton">Ver detalle</a>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        </main>
    <?php endif; ?>
    <script src="/assets/js/feed.js" defer></script>
</body>
</html>
