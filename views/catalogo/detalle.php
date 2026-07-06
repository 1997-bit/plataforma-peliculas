<?php

use App\Helpers\IconoEstrella;
use App\Helpers\TmdbImagen;

/** @var array<string,mixed> $detalle */
/** @var string $contentId */
/** @var array{rating_avg:float,rating_count:int}|null $infoLocal */
/** @var int|null $miCalificacion */
/** @var list<array<string,mixed>> $cast */
/** @var string|null $backdropPath */
/** @var string $csrf */
/** @var list<array<string,mixed>> $recomendados */

$tema = $_COOKIE['tema'] ?? null;

$titulo = $detalle['title'] ?? $detalle['name'] ?? 'Sin título';
$tipoQuery = ($_GET['tipo'] ?? 'movie') === 'series' ? 'series' : 'movie';
$fecha = $detalle['release_date'] ?? $detalle['first_air_date'] ?? '';
$anio = $fecha !== '' ? substr($fecha, 0, 4) : '';
$sinopsis = $detalle['overview'] ?? 'Sin descripción disponible.';
$generos = array_map(static fn (array $g): string => $g['name'], $detalle['genres'] ?? []);

$backdrop = TmdbImagen::backdrop($backdropPath ?? $detalle['backdrop_path'] ?? null);
$poster = TmdbImagen::poster($detalle['poster_path'] ?? null, 'md');

$ratingAvg = $infoLocal['rating_avg'] ?? 0.0;
$ratingCount = $infoLocal['rating_count'] ?? 0;
$estrellasPromedio = $ratingAvg > 0 ? round($ratingAvg, 1) : 0;
$miEstrellas = $miCalificacion ?? 0;
?>
<!DOCTYPE html>
<html lang="es" <?= $tema ? 'data-tema="' . htmlspecialchars($tema, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/navbar.css" />
    <link rel="stylesheet" href="/assets/css/footer.css" />
    <link rel="stylesheet" href="/assets/css/base.css"/>
    <link rel="stylesheet" href="/assets/css/tokens.css"/>
    <link rel="stylesheet" href="/assets/css/componentes.css"/>
    <link rel="stylesheet" href="/assets/css/detalle.css"/>
</head>
<body>
    <?php require ROOT . '/views/partials/nav.php'; ?>

    <main class="detalle">
        <section class="hero">
            <?php $heroFondoUrl = $backdrop; ?>
            <?php require ROOT . '/views/partials/hero-fondo.php'; ?>
        </section>

        <div class="detalle-contenido">
            <div class="poster-marco poster-marco--lg detalle-poster-marco">
                <img class="poster-img" src="<?= $poster ?>" alt="<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="detalle-info">
                <h1 class="detalle-titulo">
                    <?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>
                    <?php if ($anio !== ''): ?>
                        <span class="detalle-anio"><?= $anio ?></span>
                    <?php endif; ?>
                </h1>

                <?php if ($generos !== []): ?>
                    <div class="detalle-generos">
                        <?php foreach ($generos as $genero): ?>
                            <span class="chip"><?= htmlspecialchars($genero, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <p class="detalle-sinopsis"><?= htmlspecialchars($sinopsis, ENT_QUOTES, 'UTF-8') ?></p>

                <section class="detalle-calificacion"
                    data-content-id="<?= htmlspecialchars($contentId, ENT_QUOTES, 'UTF-8') ?>"
                    data-mis-estrellas="<?= $miEstrellas ?>"
                    data-csrf="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"
                >
                    <h2 class="detalle-subtitulo">Tu calificación</h2>
                    <div class="detalle-estrellas" role="radiogroup" aria-label="Calificar de 1 a 5 estrellas">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button
                                type="button"
                                class="detalle-estrella <?= $i <= $miEstrellas ? 'detalle-estrella-activa' : '' ?>"
                                data-valor="<?= $i ?>"
                                role="radio"
                                aria-checked="<?= $i === $miEstrellas ? 'true' : 'false' ?>"
                                aria-label="<?= $i ?> estrella<?= $i > 1 ? 's' : '' ?>"
                            >
                                <?= IconoEstrella::svg('detalle-estrella-icono detalle-estrella-icono-relleno') ?>
                                <?= IconoEstrella::svg('detalle-estrella-icono detalle-estrella-icono-contorno') ?>
                            </button>
                        <?php endfor; ?>
                    </div>
                    <p class="detalle-calificacion-mensaje" aria-live="polite"></p>

                    <?php if ($ratingCount > 0): ?>
                        <p class="detalle-promedio-titulo">Calificación general</p>
                        <div class="detalle-promedio">
                            <div class="detalle-estrellas detalle-estrellas-promedio" aria-hidden="true">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php
                                        $fraccion = max(0, min(1, $estrellasPromedio - ($i - 1)));
                                        $porcentaje = round($fraccion * 100);
                                    ?>
                                    <span class="detalle-estrella detalle-estrella-promedio-item">
                                        <?= IconoEstrella::svg('detalle-estrella-icono detalle-estrella-icono-contorno') ?>
                                        <span class="detalle-estrella-icono detalle-estrella-icono-relleno-parcial" style="width: <?= $porcentaje ?>%">
                                            <?= IconoEstrella::svg() ?>
                                        </span>
                                    </span>
                                <?php endfor; ?>
                            </div>
                            <span class="detalle-promedio-texto">
                                <?= number_format($estrellasPromedio, 1) ?> · <?= $ratingCount ?> calificación<?= $ratingCount === 1 ? '' : 'es' ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <p class="detalle-promedio-titulo">Calificación general</p>
                        <p class="detalle-promedio">Aún no hay calificaciones de la comunidad.</p>
                    <?php endif; ?>
                </section>

                <?php if ($cast !== []): ?>
                    <section class="detalle-cast">
                        <h2 class="detalle-subtitulo">Reparto</h2>
                        <div class="detalle-cast-grid">
                            <?php foreach ($cast as $actor): ?>
                                <span class="chip chip--simple"><?= htmlspecialchars($actor['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($recomendados !== []): ?>
                    <section class="detalle-recomendados">
                        <h2 class="detalle-subtitulo">Recomendados para ti</h2>
                        <div class="detalle-cast-grid">
                            <?php foreach ($recomendados as $item): ?>
                                <a class="chip chip--simple" href="/contenido?id=<?= htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8') ?>&tipo=<?= $item['type'] === 'series' ? 'series' : 'movie' ?>">
                                    <?= htmlspecialchars((string) $item['titulo'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="/assets/js/detalle-calificacion.js" defer></script>

    <?php require ROOT . '/views/partials/footer.php'; ?>
</body>
</html>
