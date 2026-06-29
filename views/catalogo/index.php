<?php

use App\Helpers\TmdbImagen;

/** @var string $csrf */
/** @var list<array<string,mixed>> $items */
/** @var int $totalPaginas */
/** @var array<int,string> $generos */

$tema = $_COOKIE['tema'] ?? null;
$tipoActual = $_GET['tipo'] ?? 'movie';
$generoActual = $_GET['genero'] ?? '';
$busquedaActual = $_GET['q'] ?? '';
$paginaActual = max(1, (int) ($_GET['page'] ?? 1));
?>
<!DOCTYPE html>
<html lang="es" <?= $tema ? 'data-tema="' . htmlspecialchars($tema, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo</title>
    <link rel="stylesheet" href="/assets/css/navbar.css" />
    <link rel="stylesheet" href="/assets/css/footer.css" />
    <link rel="stylesheet" href="/assets/css/base.css"/>
    <link rel="stylesheet" href="/assets/css/tokens.css"/>
    <link rel="stylesheet" href="/assets/css/componentes.css"/>
    <link rel="stylesheet" href="/assets/css/home.css"/>
    <link rel="stylesheet" href="/assets/css/catalogo.css"/>
</head>
<body>
    <?php require ROOT . '/views/partials/nav.php'; ?>

    <main class="catalogo">
        <h1 class="catalogo-titulo">Catálogo</h1>

        <form method="get" action="/catalogo" class="catalogo-filtros">
            <input
                type="text"
                name="q"
                placeholder="Buscar por título..."
                value="<?= htmlspecialchars($busquedaActual, ENT_QUOTES, 'UTF-8') ?>"
                class="filtro-busqueda"
            >

            <select name="tipo" class="filtro-select">
                <option value="movie" <?= $tipoActual === 'movie' ? 'selected' : '' ?>>Películas</option>
                <option value="series" <?= $tipoActual === 'series' ? 'selected' : '' ?>>Series</option>
            </select>

            <select name="genero" class="filtro-select">
                <option value="">Todos los géneros</option>
                <?php foreach ($generos as $id => $nombre): ?>
                    <option value="<?= $id ?>" <?= (string) $generoActual === (string) $id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="filtro-boton">Filtrar</button>
        </form>

        <?php if ($items === []): ?>
            <p class="catalogo-vacio">No se encontró contenido con esos filtros.</p>
        <?php else: ?>
            <div class="catalogo-grid" role="list">
                <?php foreach ($items as $item): ?>
                    <?php
                        $tmdbId = $item['id'] ?? null;
                        $titulo = htmlspecialchars($item['title'] ?? $item['name'] ?? 'Sin título', ENT_QUOTES, 'UTF-8');
                        $poster = TmdbImagen::poster($item['poster_path'] ?? null, 'sm');
                        $origenUrl = ($item['origen'] ?? 'tmdb') === 'local' ? '&origen=local' : '';
                    ?>
                    <article class="card catalogo-card" role="listitem">
                        <a href="/contenido?id=<?= urlencode((string) $tmdbId) ?>&tipo=<?= htmlspecialchars($tipoActual, ENT_QUOTES, 'UTF-8') ?><?= $origenUrl ?>" class="catalogo-card-link">
                            <div class="poster-marco poster-marco--md">
                                <img class="poster-img" src="<?= $poster ?>" alt="<?= $titulo ?>" loading="lazy" draggable="false">
                            </div>
                            <div class="card-info">
                                <p class="card-titulo"><?= $titulo ?></p>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="catalogo-paginacion">
                <?php if ($paginaActual > 1): ?>
                    <?php
                        $paramsAnterior = array_filter([
                            'tipo' => $tipoActual, 'genero' => $generoActual,
                            'q' => $busquedaActual, 'page' => $paginaActual - 1,
                        ]);
                    ?>
                    <a href="/catalogo?<?= htmlspecialchars(http_build_query($paramsAnterior), ENT_QUOTES, 'UTF-8') ?>" class="catalogo-siguiente">
                        ← Anterior
                    </a>
                <?php endif; ?>

                <?php if ($paginaActual < $totalPaginas): ?>
                    <?php
                        $paramsSiguiente = array_filter([
                            'tipo' => $tipoActual, 'genero' => $generoActual,
                            'q' => $busquedaActual, 'page' => $paginaActual + 1,
                        ]);
                    ?>
                    <a href="/catalogo?<?= htmlspecialchars(http_build_query($paramsSiguiente), ENT_QUOTES, 'UTF-8') ?>" class="catalogo-siguiente">
                        Siguiente →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php require ROOT . '/views/partials/footer.php'; ?>
</body>
</html>
