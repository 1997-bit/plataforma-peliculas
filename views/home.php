<?php
use App\Helpers\IconoEstrella;
use App\Helpers\TmdbImagen;
/** @var string $username */
/** @var string $csrf */
/** @var list<array<string,mixed>> $hero */
/** @var list<array<string,mixed>> $populares */
/** @var list<array<string,mixed>> $recomendaciones */
/** @var list<array<string,mixed>> $vistoReciente */
$tema = $_COOKIE['tema'] ?? null;

/** Badge de rating reusado en las 3 estanterias (poster y backdrop). Vacio si nadie califico todavia. */
$renderRating = static function (mixed $avg, mixed $count): string {
    $count = (int) $count;
    if ($count <= 0) {
        return '';
    }

    return '<span class="card-rating">'
        . IconoEstrella::svg('card-rating-icono')
        . number_format((float) $avg, 1)
        . '</span>';
};
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

    <?php if ($hero !== []): ?>
        <section class="home-hero" data-hero>
            <div class="home-hero-viewport" data-hero-viewport>
                <?php foreach ($hero as $i => $item): ?>
                    <?php
                        $tituloHero = htmlspecialchars($item['title'] ?? 'Sin título', ENT_QUOTES, 'UTF-8');
                        $overviewHero = htmlspecialchars($item['overview'] ?? '', ENT_QUOTES, 'UTF-8');
                        $backdropHero = TmdbImagen::backdrop($item['backdrop_path'] ?? null);
                        $tipoUrlHero = $item['type'] === 'series' ? 'series' : 'movie';
                    ?>
                    <article class="hero-slide home-hero-slide <?= $i === 0 ? 'home-hero-slide--activo' : '' ?>" data-hero-slide <?= $backdropHero ? 'style="background-image: url(' . htmlspecialchars($backdropHero, ENT_QUOTES, 'UTF-8') . ')"' : '' ?>>
                        <div class="home-hero-veladura"></div>
                        <div class="home-hero-contenido">
                            <h1 class="home-hero-titulo"><?= $tituloHero ?></h1>
                            <p class="home-hero-descripcion"><?= $overviewHero ?></p>
                            <a href="/contenido?id=<?= urlencode((string) ($item['id'] ?? '')) ?>&tipo=<?= $tipoUrlHero ?>" class="boton boton--claro home-hero-boton">
                                Ver más
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if (count($hero) > 1): ?>
                <div class="home-hero-dots" data-hero-dots>
                    <?php foreach ($hero as $i => $item): ?>
                        <button type="button" class="home-hero-dot <?= $i === 0 ? 'home-hero-dot--activo' : '' ?>" data-hero-dot="<?= $i ?>" aria-label="Ir a slide <?= $i + 1 ?>"></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <main class="home">
        <?php if ($vistoReciente !== []): ?>
            <section class="shelf">
                <div class="shelf-header">
                    <h2 class="shelf-titulo">Visto recientemente</h2>
                    <a href="/perfil" class="shelf-ver-todo">Ver todo</a>
                </div>
                <div class="shelf-viewport">
                    <div class="shelf-fila" role="list" data-carousel>
                        <?php foreach ($vistoReciente as $i => $item): ?>
                            <?php
                                $title = htmlspecialchars($item['title'] ?? 'Sin título', ENT_QUOTES, 'UTF-8');
                                $tipoUrl = $item['type'] === 'series' ? 'series' : 'movie';
                                $poster = TmdbImagen::poster($item['poster_path'] ?? null, 'sm');
                            ?>
                            <article class="card" role="listitem" style="--card-i: <?= min($i, 12) ?>">
                                <a href="/contenido?id=<?= urlencode((string) $item['id']) ?>&tipo=<?= $tipoUrl ?>" class="card-link">
                                    <div class="poster-marco poster-marco--md">
                                        <img class="poster-img" src="<?= $poster ?>" alt="<?= $title ?>" loading="lazy" draggable="false">
                                        <?= $renderRating($item['rating_avg'] ?? 0, $item['rating_count'] ?? 0) ?>
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
            <div class="shelf-header">
                <h2 class="shelf-titulo">Populares</h2>
                <a href="/catalogo?tipo=movie" class="shelf-ver-todo">Ver todo</a>
            </div>
            <div class="shelf-viewport">
                <div class="shelf-fila" role="list" data-carousel>
                    <?php foreach ($populares as $i => $item): ?>
                        <?php
                            $title = htmlspecialchars($item['title'] ?? 'Sin título', ENT_QUOTES, 'UTF-8');
                            $fecha = $item['release_date'] ?? '';
                            $year = substr($fecha, 0, 4);
                            $poster = TmdbImagen::poster($item['poster_path'] ?? null, 'sm');
                            $tipoUrlItem = $item['type'] === 'series' ? 'series' : 'movie';
                        ?>
                        <article class="card" role="listitem" style="--card-i: <?= min($i, 12) ?>">
                            <a href="/contenido?id=<?= urlencode((string) $item['id']) ?>&tipo=<?= $tipoUrlItem ?>" class="card-link">
                                <div class="poster-marco poster-marco--md">
                                    <img class="poster-img" src="<?= $poster ?>" alt="<?= $title ?>" loading="lazy" draggable="false">
                                    <?= $renderRating($item['rating_avg'] ?? 0, $item['rating_count'] ?? 0) ?>
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

        <?php if ($recomendaciones !== []): ?>
            <section class="shelf">
                <div class="shelf-header">
                    <h2 class="shelf-titulo">Recomendado para ti</h2>
                    <a href="/recomendaciones" class="shelf-ver-todo">Ver todo</a>
                </div>
                <div class="shelf-viewport">
                    <div class="shelf-fila shelf-fila--backdrop" role="list" data-carousel>
                        <?php foreach ($recomendaciones as $i => $item): ?>
                            <?php
                                $title = htmlspecialchars($item['title'] ?? 'Sin título', ENT_QUOTES, 'UTF-8');
                                $fecha = $item['release_date'] ?? '';
                                $year = substr($fecha, 0, 4);
                                $backdrop = TmdbImagen::backdrop($item['backdrop_path'] ?? null);
                                $logo = TmdbImagen::logo($item['logo_path'] ?? null);
                                $tipoUrlItem = $item['type'] === 'series' ? 'series' : 'movie';
                            ?>
                            <article class="card card--backdrop" role="listitem" style="--card-i: <?= min($i, 12) ?>">
                                <a href="/contenido?id=<?= urlencode((string) $item['id']) ?>&tipo=<?= $tipoUrlItem ?>" class="card-link">
                                    <div class="backdrop-marco">
                                        <?php if ($backdrop): ?>
                                            <img class="backdrop-img" src="<?= $backdrop ?>" alt="<?= $logo ? '' : $title ?>" loading="lazy" draggable="false">
                                        <?php endif; ?>
                                        <?php if ($logo): ?>
                                            <img class="backdrop-logo" src="<?= $logo ?>" alt="<?= $title ?>" loading="lazy" draggable="false">
                                        <?php else: ?>
                                            <span class="backdrop-titulo-overlay"><?= $title ?></span>
                                        <?php endif; ?>
                                        <?= $renderRating($item['rating_avg'] ?? 0, $item['rating_count'] ?? 0) ?>
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
        <?php endif; ?>
    </main>

    <script src="/assets/js/carousel.js" defer></script>
    <script src="/assets/js/hero.js" defer></script>
    <?php require ROOT . '/views/partials/footer.php'; ?>
</body>
</html>
