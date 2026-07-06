<?php
/** @var string $csrf */
/** @var string $q */
/** @var string $tipoBusqueda */
/** @var list<array<string,mixed>> $resultados */
/** @var list<array{id:int,nombre:string}> $generos */
/** @var list<int> $generoIdsSeleccionados */
/** @var array<string,mixed>|null $item */
/** @var string|null $errorMsg */
/** @var string|null $okMsg */
/** @var string $tmdbPosterId */
/** @var string $tmdbBackdropId */
/** @var string $tmdbId */

$okMsg = $okMsg ?? null;
$q = $q ?? '';
$tipoBusqueda = $tipoBusqueda ?? 'movie';
$resultados = $resultados ?? [];
$generos = $generos ?? [];
$generoIdsSeleccionados = $generoIdsSeleccionados ?? [];
$item = $item ?? null;
$errorMsg = $errorMsg ?? null;
$tmdbPosterId = $tmdbPosterId ?? '';
$tmdbBackdropId = $tmdbBackdropId ?? '';
$tmdbId = $tmdbId ?? '';

$titulo = htmlspecialchars((string) ($item['titulo'] ?? ''), ENT_QUOTES, 'UTF-8');
$descripcion = htmlspecialchars((string) ($item['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8');
$tipoActual = (string) ($item['tipo'] ?? $item['type'] ?? 'movie');
$anio = htmlspecialchars((string) ($item['anio'] ?? $item['anio_lanzamiento'] ?? ''), ENT_QUOTES, 'UTF-8');
$hasPoster = $tmdbPosterId !== '';
$hasBackdrop = $tmdbBackdropId !== '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin · Agregar contenido</title>
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/tokens.css">
    <link rel="stylesheet" href="/assets/css/componentes.css">
    <link rel="stylesheet" href="/assets/css/admin/admin-layout.css">
    <link rel="stylesheet" href="/assets/css/admin/admin-forms.css">
    <link rel="stylesheet" href="/assets/css/admin/admin-media.css">
    <link rel="stylesheet" href="/assets/css/admin/admin-tmdb-search.css">
    <link rel="stylesheet" href="/assets/css/admin/admin-utils.css">
    <link rel="stylesheet" href="/assets/css/admin/admin-custom-select.css">
</head>
<body>
<?php require ROOT . '/views/partials/admin-nav.php'; ?>

<main class="admin-main admin-main--wide">

<p id="form-aviso-ok" class="admin-aviso admin-aviso--ok" role="status" <?= $okMsg ? '' : 'hidden' ?>><?= htmlspecialchars((string) $okMsg, ENT_QUOTES, 'UTF-8') ?></p>

<div class="admin-agregar">

    <!-- PANEL IZQUIERDO: búsqueda TMDB -->
    <aside class="admin-agregar-buscador admin-panel">
        <p class="admin-panel-titulo">Buscar en TMDB</p>

        <form method="GET" action="/admin/contenido/nuevo" class="admin-tmdb-form">
            <input type="text" name="q"
                   value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="Título..."
                   <?= $q === '' ? 'autofocus' : '' ?>>
            <select name="tipo" data-custom-select>
                <option value="movie" <?= $tipoBusqueda === 'movie' ? 'selected' : '' ?>>Película</option>
                <option value="series" <?= $tipoBusqueda === 'series' ? 'selected' : '' ?>>Serie</option>
            </select>
            <button type="submit" class="boton boton--primario">Buscar</button>
        </form>

        <div class="admin-tmdb-lista">
            <?php if ($q === ''): ?>
                <p class="admin-tmdb-ayuda">Busca un título para autocompletar el formulario, o rellénalo a mano.</p>

            <?php elseif ($resultados === []): ?>
                <p class="admin-vacio">Sin resultados para "<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>".</p>

            <?php else: ?>
                <?php foreach ($resultados as $res): ?>
                    <?php
                    $resId = (int) ($res['id'] ?? 0);
                    $resTitulo = (string) ($res['title'] ?? $res['name'] ?? '');
                    $resAnio = substr((string) ($res['release_date'] ?? $res['first_air_date'] ?? ''), 0, 4);
                    $resPoster = (string) ($res['poster_path'] ?? '');
                    $resBackdrop = (string) ($res['backdrop_path'] ?? '');
                    $resOverview = (string) ($res['overview'] ?? '');
                    $resGeneros = $res['_generos'] ?? [];
                    $resGenreIds = array_map('intval', (array) ($res['genre_ids'] ?? []));
                    $resExiste = (bool) ($res['_existe'] ?? false);
                    ?>
                    <article class="admin-tmdb-card"
                             data-titulo="<?= htmlspecialchars($resTitulo, ENT_QUOTES, 'UTF-8') ?>"
                             data-tipo="<?= htmlspecialchars($tipoBusqueda, ENT_QUOTES, 'UTF-8') ?>"
                             data-anio="<?= htmlspecialchars($resAnio, ENT_QUOTES, 'UTF-8') ?>"
                             data-descripcion="<?= htmlspecialchars($resOverview, ENT_QUOTES, 'UTF-8') ?>"
                             data-poster="<?= htmlspecialchars($resPoster, ENT_QUOTES, 'UTF-8') ?>"
                             data-backdrop="<?= htmlspecialchars($resBackdrop, ENT_QUOTES, 'UTF-8') ?>"
                             data-tmdb-id="<?= $resId ?>"
                             data-generos="<?= htmlspecialchars(json_encode($resGeneros, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>"
                             data-genre-ids="<?= htmlspecialchars(json_encode($resGenreIds), ENT_QUOTES, 'UTF-8') ?>"
                             data-existe="<?= $resExiste ? '1' : '' ?>">

                        <?php if ($resPoster !== ''): ?>
                            <div class="poster-marco poster-marco--xs">
                                <img class="poster-img"
                                     src="https://image.tmdb.org/t/p/w92<?= htmlspecialchars($resPoster, ENT_QUOTES, 'UTF-8') ?>"
                                     alt="" loading="lazy">
                            </div>
                        <?php else: ?>
                            <div class="poster-marco poster-marco--xs admin-tmdb-card-noposter"></div>
                        <?php endif; ?>

                        <div class="admin-tmdb-card-info">
                            <span class="admin-tmdb-card-titulo"><?= htmlspecialchars($resTitulo, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($resAnio !== ''): ?>
                                <span class="admin-tmdb-card-anio"><?= htmlspecialchars($resAnio, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if ($resOverview !== ''): ?>
                                <p class="admin-tmdb-card-overview"><?= htmlspecialchars(mb_substr($resOverview, 0, 100), ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <div class="admin-tmdb-card-generos">
                                <?php foreach (array_slice($resGeneros, 0, 3) as $g): ?>
                                    <span class="chip chip--simple"><?= htmlspecialchars($g, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endforeach; ?>
                                <?php if ($resExiste): ?>
                                    <span class="chip chip--simple admin-tmdb-card-existe">Ya en catálogo</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>

    <!-- PANEL DERECHO: formulario -->
    <section id="admin-agregar-form" class="admin-panel">
        <p class="admin-panel-titulo">Nuevo contenido</p>

        <p id="form-aviso-error" class="admin-aviso admin-aviso--error" role="alert" <?= $errorMsg !== null ? '' : 'hidden' ?>><?= htmlspecialchars((string) $errorMsg, ENT_QUOTES, 'UTF-8') ?></p>

        <div id="existe-aviso" class="admin-aviso admin-aviso--warning" hidden>
            Este título ya está en el catálogo. Si guardas se creará una entrada duplicada.
        </div>

        <form method="POST" action="/admin/contenido/nuevo" enctype="multipart/form-data" class="admin-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" id="field-tmdb-id" name="tmdb_id" value="<?= htmlspecialchars($tmdbId, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" id="field-tmdb-poster" name="tmdb_poster_path" value="<?= htmlspecialchars($tmdbPosterId, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" id="field-tmdb-backdrop" name="tmdb_backdrop_path" value="<?= htmlspecialchars($tmdbBackdropId, ENT_QUOTES, 'UTF-8') ?>">

            <div class="admin-campo">
                <label for="titulo">Título</label>
                <input type="text" id="titulo" name="titulo" value="<?= $titulo ?>" maxlength="255" required>
            </div>

            <div class="admin-form-fila">
                <div class="admin-campo">
                    <label for="tipo">Tipo</label>
                    <select id="tipo" name="tipo" data-custom-select>
                        <option value="movie" <?= $tipoActual === 'movie' ? 'selected' : '' ?>>Película</option>
                        <option value="series" <?= $tipoActual === 'series' ? 'selected' : '' ?>>Serie</option>
                    </select>
                </div>
                <div class="admin-campo">
                    <label for="anio">Año</label>
                    <input type="number" id="anio" name="anio" value="<?= $anio ?>"
                           min="1888" max="<?= (int) date('Y') + 1 ?>">
                </div>
            </div>

            <div class="admin-campo">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" maxlength="2000" rows="4"><?= $descripcion ?></textarea>
            </div>

            <div class="admin-campo">
                <label>Géneros</label>
                <div class="admin-generos-grid">
                    <?php foreach ($generos as $genero): ?>
                        <?php $gid = (int) $genero['id']; ?>
                        <label class="chip" data-tmdb-id="<?= (int) ($genero['tmdb_id'] ?? 0) ?>">
                            <input type="checkbox" class="chip-input" name="generos[]" value="<?= $gid ?>"
                                   <?= in_array($gid, $generoIdsSeleccionados, true) ? 'checked' : '' ?>>
                            <?= htmlspecialchars((string) $genero['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="admin-campo">
                <label for="poster">Poster</label>
                <div id="poster-preview-wrap" class="admin-img-preview-wrap">
                    <img id="poster-preview" data-fallback-skip
                         src="<?= $hasPoster ? 'https://image.tmdb.org/t/p/w300' . htmlspecialchars($tmdbPosterId, ENT_QUOTES, 'UTF-8') : '' ?>"
                         alt="" class="admin-poster-preview" <?= $hasPoster ? '' : 'hidden' ?>>
                    <span id="poster-placeholder" class="admin-img-placeholder" aria-hidden="true" <?= $hasPoster ? 'hidden' : '' ?>>
                        <?php require ROOT . '/views/partials/admin-img-placeholder.php'; ?>
                    </span>
                    <button type="button" id="btn-quitar-poster" class="boton boton--secundario boton--sm" <?= $hasPoster ? '' : 'hidden' ?>>Quitar</button>
                </div>
                <input type="file" id="poster" name="poster"
                       accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <small id="poster-hint" class="admin-campo-hint">
                    <?= $hasPoster
                        ? 'Sube un archivo para reemplazar el poster de TMDB.'
                        : 'Requerido. Sube un archivo o selecciona un resultado de TMDB.' ?>
                </small>
            </div>

            <div id="backdrop-campo" class="admin-campo" <?= $hasBackdrop ? '' : 'hidden' ?>>
                <label>Backdrop <small class="admin-campo-hint">desde TMDB</small></label>
                <div class="admin-img-preview-wrap admin-img-preview-wrap--backdrop">
                    <img id="backdrop-preview" data-fallback-skip
                         src="<?= $hasBackdrop ? 'https://image.tmdb.org/t/p/w780' . htmlspecialchars($tmdbBackdropId, ENT_QUOTES, 'UTF-8') : '' ?>"
                         alt="" class="backdrop-preview" <?= $hasBackdrop ? '' : 'hidden' ?>>
                    <span id="backdrop-placeholder" class="admin-img-placeholder admin-img-placeholder--backdrop" aria-hidden="true" <?= $hasBackdrop ? 'hidden' : '' ?>>
                        <?php require ROOT . '/views/partials/admin-img-placeholder.php'; ?>
                    </span>
                </div>
            </div>

            <div class="admin-form-acciones">
                <button type="submit" class="boton boton--primario">Crear contenido</button>
                <a href="/admin" class="boton boton--secundario">Cancelar</a>
            </div>
        </form>
    </section>

</div>
</main>

<script src="/assets/js/admin-poster-field.js"></script>
<script src="/assets/js/admin-agregar.js"></script>
</body>
</html>
