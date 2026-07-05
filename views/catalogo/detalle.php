<?php

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
                                <svg class="detalle-estrella-icono detalle-estrella-icono-relleno" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" aria-hidden="true">
                                    <rect width="256" height="256" fill="none"/>
                                    <path d="M234.29,114.85l-45,38.83L203,211.75a16.4,16.4,0,0,1-24.5,17.82L128,198.49,77.47,229.57A16.4,16.4,0,0,1,53,211.75l13.76-58.07-45-38.83A16.46,16.46,0,0,1,31.08,86l59-4.76,22.76-55.08a16.36,16.36,0,0,1,30.27,0l22.75,55.08,59,4.76a16.46,16.46,0,0,1,9.37,28.86Z"/>
                                </svg>
                                <svg class="detalle-estrella-icono detalle-estrella-icono-contorno" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" aria-hidden="true">
                                    <rect width="256" height="256" fill="none"/>
                                    <path d="M239.18,97.26A16.38,16.38,0,0,0,224.92,86l-59-4.76L143.14,26.15a16.36,16.36,0,0,0-30.27,0L90.11,81.23,31.08,86a16.46,16.46,0,0,0-9.37,28.86l45,38.83L53,211.75a16.4,16.4,0,0,0,24.5,17.82L128,198.49l50.53,31.08A16.4,16.4,0,0,0,203,211.75l-13.76-58.07,45-38.83A16.43,16.43,0,0,0,239.18,97.26Zm-15.34,5.47-48.7,42a8,8,0,0,0-2.56,7.91l14.88,62.8a.37.37,0,0,1-.17.48c-.18.14-.23.11-.38,0l-54.72-33.65A8,8,0,0,0,128,181.1V32c.24,0,.27.08.35.26L153,91.86a8,8,0,0,0,6.75,4.92l63.91,5.16c.16,0,.25,0,.34.29S224,102.63,223.84,102.73Z"/>
                                </svg>
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
                                        <svg class="detalle-estrella-icono detalle-estrella-icono-contorno" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                            <rect width="256" height="256" fill="none"/>
                                            <path d="M239.18,97.26A16.38,16.38,0,0,0,224.92,86l-59-4.76L143.14,26.15a16.36,16.36,0,0,0-30.27,0L90.11,81.23,31.08,86a16.46,16.46,0,0,0-9.37,28.86l45,38.83L53,211.75a16.4,16.4,0,0,0,24.5,17.82L128,198.49l50.53,31.08A16.4,16.4,0,0,0,203,211.75l-13.76-58.07,45-38.83A16.43,16.43,0,0,0,239.18,97.26Zm-15.34,5.47-48.7,42a8,8,0,0,0-2.56,7.91l14.88,62.8a.37.37,0,0,1-.17.48c-.18.14-.23.11-.38,0l-54.72-33.65A8,8,0,0,0,128,181.1V32c.24,0,.27.08.35.26L153,91.86a8,8,0,0,0,6.75,4.92l63.91,5.16c.16,0,.25,0,.34.29S224,102.63,223.84,102.73Z"/>
                                        </svg>
                                        <span class="detalle-estrella-icono detalle-estrella-icono-relleno-parcial" style="width: <?= $porcentaje ?>%">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                                <rect width="256" height="256" fill="none"/>
                                                <path d="M234.29,114.85l-45,38.83L203,211.75a16.4,16.4,0,0,1-24.5,17.82L128,198.49,77.47,229.57A16.4,16.4,0,0,1,53,211.75l13.76-58.07-45-38.83A16.46,16.46,0,0,1,31.08,86l59-4.76,22.76-55.08a16.36,16.36,0,0,1,30.27,0l22.75,55.08,59,4.76a16.46,16.46,0,0,1,9.37,28.86Z"/>
                                            </svg>
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
