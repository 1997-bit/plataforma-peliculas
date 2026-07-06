<?php
/**
 * Partial de una tarjeta del feed de recomendaciones (100vh, poster centrado).
 * Se usa tanto en el render inicial (recomendaciones.php) como en el HTML
 * generado por JS para las tarjetas cargadas via scroll infinito
 * (misma estructura de clases, ver assets/js/recomendaciones.js).
 *
 * @var array<string,mixed> $item shape de RecomendacionController::prepararItem()
 * @var callable $renderEstrella
 */

$titulo = htmlspecialchars((string) $item['titulo'], ENT_QUOTES, 'UTF-8');
$anio = (string) $item['anio'];
$overview = htmlspecialchars((string) $item['overview'], ENT_QUOTES, 'UTF-8');
$backdrop = $item['backdrop'] ?? null;
$poster = htmlspecialchars((string) $item['poster'], ENT_QUOTES, 'UTF-8');
$ratingAvg = (float) $item['rating_avg'];
$ratingCount = (int) $item['rating_count'];
$tipoUrlItem = $item['tipo'] === 'series' ? 'series' : 'movie';
?>
<section class="feed-slide" data-feed-slide data-id="<?= htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8') ?>">
    <div class="feed-fondo" <?= $backdrop ? 'style="background-image: url(' . htmlspecialchars((string) $backdrop, ENT_QUOTES, 'UTF-8') . ')"' : '' ?>></div>
    <div class="feed-veladura"></div>
    <div class="feed-info" data-feed-info>
        <div class="poster-marco feed-poster">
            <img class="poster-img" src="<?= $poster ?>" alt="" loading="lazy" draggable="false">
        </div>
        <div class="feed-texto">
            <a href="/contenido?id=<?= urlencode((string) $item['id']) ?>&tipo=<?= $tipoUrlItem ?>" class="feed-titulo-link">
                <h2 class="feed-titulo">
                    <?= $titulo ?>
                    <?php if ($anio !== ''): ?><span class="feed-anio"><?= htmlspecialchars($anio, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                </h2>
            </a>
            <p class="feed-sinopsis"><?= $overview ?></p>
            <div class="feed-rating">
                <?php if ($ratingCount > 0): ?>
                    <?= $renderEstrella() ?>
                    <span><?= number_format($ratingAvg, 1) ?> · <?= $ratingCount ?> calificación<?= $ratingCount === 1 ? '' : 'es' ?></span>
                <?php else: ?>
                    <span>Sin calificaciones</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
