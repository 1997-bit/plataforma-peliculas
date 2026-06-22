<?php
/** @var string $username */
/** @var string $csrf */
/** @var array  $movies */

$tema = $_COOKIE['tema'] ?? null;
?>
<!DOCTYPE html>
<html lang="es" <?= $tema ? 'data-tema="' . htmlspecialchars($tema, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio — CineApp</title>
    <link rel="stylesheet" href="/assets/css/navbar.css" />
    <link rel="stylesheet" href="/assets/css/footer.css" />
    <link rel="stylesheet" href="/assets/css/base.css"/>
    <link rel="stylesheet" href="/assets/css/home.css"/>
</head>
<body>
    <?php require ROOT . '/views/partials/nav.php'; ?>

    <main class="home">
        <section class="shelf">
            <h2 class="shelf-titulo">Películas populares</h2>
            <div class="shelf-viewport">
                <div class="shelf-fila" role="list" data-carousel>
                    <?php foreach ($movies as $item): ?>
                        <?php
                            $title  = htmlspecialchars($item['title'] ?? 'Sin título', ENT_QUOTES, 'UTF-8');
                            $year   = substr($item['release_date'] ?? '', 0, 4);
                            $poster = $item['poster_path']
                                ? 'https://image.tmdb.org/t/p/w342' . $item['poster_path']
                                : '/assets/images/placeholder.webp';
                        ?>
                        <article class="card" role="listitem">
                            <div class="card-poster-marco">
                                <img class="card-poster" src="<?= $poster ?>" alt="<?= $title ?>" loading="lazy" draggable="false">
                            </div>
                            <div class="card-info">
                                <p class="card-titulo"><?= $title ?></p>
                                <p class="card-anio"><?= $year ?></p>
                            </div>
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
