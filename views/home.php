<?php

use App\Helpers\TmdbImagen;

/** @var string $username */
/** @var string $csrf */
/** @var array  $movies */
/** @var list<array{tmdb_id:int,type:string,title:string,poster_path:?string,viewed_at:string}> $vistoReciente */

$tema = $_COOKIE['tema'] ?? null;
?>
<!DOCTYPE html>
<html lang="es" <?= $tema ? 'data-tema="' . htmlspecialchars($tema, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
    <link rel="stylesheet" href="/assets/css/navbar.css" />
    <link rel="stylesheet" href="/assets/css/footer.css" />
    <link rel="stylesheet" href="/assets/css/base.css"/>
    <link rel="stylesheet" href="/assets/css/tokens.css"/>
    <link rel="stylesheet" href="/assets/css/componentes.css"/>
    <link rel="stylesheet" href="/assets/css/home.css"/>
</head>
<body>
    <?php require ROOT . '/views/partials/nav.php'; ?>

    <main class="home">
        <?php if ($vistoReciente !== []): ?>
        <section class="shelf">
            <h2 class="shelf-titulo">Visto recientemente</h2>
            <div class="shelf-viewport">
                <div class="shelf-fila" role="list" data-carousel>
                    <?php foreach ($vistoReciente as $item): ?>
                        <?php
                            $title  = htmlspecialchars($item['title'] ?? 'Sin título', ENT_QUOTES, 'UTF-8');
                            $tipoUrl = $item['type'] === 'series' ? 'series' : 'movie';
                            $poster = TmdbImagen::poster($item['poster_path'] ?? null, 'sm');
                        ?>
                        <article class="card" role="listitem">
                            <a href="/contenido?id=<?= urlencode((string) $item['tmdb_id']) ?>&tipo=<?= $tipoUrl ?>" class="card-link">
                                <div class="poster-marco poster-marco--md">
                                    <img class="poster-img" src="<?= $poster ?>" alt="<?= $title ?>" loading="lazy" draggable="false">
                                </div>
                                <div class="card-info">
                                    <p class="card-titulo"><?= $title ?></p>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section class="shelf">
            <h2 class="shelf-titulo">Películas populares</h2>
            <div class="shelf-viewport">
                <div class="shelf-fila" role="list" data-carousel>
                    <?php foreach ($movies as $item): ?>
                        <?php
                            $title  = htmlspecialchars($item['title'] ?? 'Sin título', ENT_QUOTES, 'UTF-8');
                        $year   = substr($item['release_date'] ?? '', 0, 4);
                        $poster = TmdbImagen::poster($item['poster_path'] ?? null, 'sm');
                        ?>
                        <article class="card" role="listitem">
                            <a href="/contenido?id=<?= urlencode((string) $item['id']) ?>&tipo=movie" class="card-link">
                                <div class="poster-marco poster-marco--md">
                                    <img class="poster-img" src="<?= $poster ?>" alt="<?= $title ?>" loading="lazy" draggable="false">
                                </div>
                                <div class="card-info">
                                    <p class="card-titulo"><?= $title ?></p>
                                    <p class="card-anio"><?= $year ?></p>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <script src="/assets/js/carousel.js" defer></script>

    <?php require ROOT . '/views/partials/footer.php'; ?>
</body>
</html>
