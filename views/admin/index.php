<?php
/** @var string $csrf */
/** @var string|null $okMsg */
/** @var string|null $errorMsg */
/** @var list<array<string,mixed>> $contenidoLocal */
/** @var list<array{nombre:string, vistas:int}> $generosMasVistos */
/** @var array{movie:int, series:int} $conteoPorTipo */
$generosMasVistos = $generosMasVistos ?? [];
$conteoPorTipo = $conteoPorTipo ?? ['movie' => 0, 'series' => 0];
$okMsg = $okMsg ?? null;
$errorMsg = $errorMsg ?? null;
$peliculas = $conteoPorTipo['movie'];
$series = $conteoPorTipo['series'];
$total = $peliculas + $series;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin · Contenido</title>
    <link rel="stylesheet" href="/assets/css/base.css" />
    <link rel="stylesheet" href="/assets/css/tokens.css" />
    <link rel="stylesheet" href="/assets/css/componentes.css" />
    <link rel="stylesheet" href="/assets/css/admin/admin-layout.css" />
    <link rel="stylesheet" href="/assets/css/admin/admin-dashboard.css" />
    <link rel="stylesheet" href="/assets/css/admin/admin-utils.css" />
</head>
<body>
<?php require ROOT . '/views/partials/admin-nav.php'; ?>
<main class="admin-main">

<?php if ($okMsg): ?>
    <p class="admin-aviso admin-aviso--ok" role="status"><?= htmlspecialchars($okMsg, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <p class="admin-aviso admin-aviso--error" role="alert"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<section aria-label="resumen">
    <h2 class="admin-seccion-titulo">Resumen</h2>
    <dl class="admin-stats">
        <div class="admin-stat-card">
            <svg class="admin-stat-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true"><g transform="scale(1.33333)"><path d="M2.251,13.306c-.26,0-.517-.081-.732-.236-.325-.234-.519-.613-.519-1.014V5.944c0-.401,.194-.78,.519-1.014,.325-.234,.747-.299,1.126-.172l.842,.281c.393,.131,.605,.556,.474,.949-.131,.392-.556,.604-.949,.474l-.513-.171v5.419l.513-.171c.394-.131,.818,.081,.949,.474,.131,.393-.081,.818-.474,.949l-.842,.281c-.128,.043-.262,.064-.394,.064Z" fill="currentColor"></path><path d="M6.25,15.25c-.243,0-.484-.071-.693-.21-.348-.232-.556-.621-.556-1.04V4c0-.419,.208-.808,.556-1.04,.35-.232,.789-.273,1.174-.114l.808,.336c.382,.159,.563,.599,.404,.981s-.598,.562-.981,.404l-.461-.192V13.625l.461-.192c.384-.159,.822,.022,.981,.404,.159,.382-.021,.822-.404,.981l-.808,.336c-.155,.064-.319,.096-.481,.096Z" fill="currentColor"></path><path d="M15.983,3.551L10.774,1.146c-.389-.179-.836-.148-1.198,.082-.361,.231-.576,.625-.576,1.053V15.719c0,.428,.215,.822,.576,1.053,.205,.131,.438,.198,.673,.198,.178,0,.357-.039,.525-.116l5.209-2.404c.618-.285,1.017-.909,1.017-1.589V5.14c0-.68-.399-1.304-1.017-1.589Z" fill="currentColor"></path></g></svg>
            <dt>Total</dt>
            <dd><?= $total ?></dd>
        </div>
        <div class="admin-stat-card">
            <svg class="admin-stat-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.25 1.25C6.44395 1.25057 5.78458 1.25569 5.24013 1.30018C4.61012 1.35165 4.06824 1.45963 3.57054 1.71322C2.77085 2.12068 2.12068 2.77085 1.71322 3.57054C1.45963 4.06824 1.35165 4.61012 1.30017 5.24013C1.26092 5.72062 1.25237 6.29062 1.25051 6.97196C1.25017 6.98126 1.25 6.99061 1.25 7C1.25 7.00812 1.25013 7.01621 1.25039 7.02427C1.25 7.19702 1.25 7.37685 1.25 7.5641V15.99L1.25002 15.995L1.25 16V16.4359C1.25 16.6231 1.25 16.803 1.25039 16.9757C1.25013 16.9838 1.25 16.9919 1.25 17C1.25 17.0094 1.25017 17.0187 1.25051 17.028C1.25237 17.7094 1.26092 18.2794 1.30017 18.7599C1.35165 19.3899 1.45963 19.9318 1.71322 20.4295C2.12068 21.2291 2.77085 21.8793 3.57054 22.2868C4.06824 22.5404 4.61012 22.6483 5.24013 22.6998C5.78458 22.7443 6.44395 22.7494 7.25 22.7499L16.75 22.75C17.556 22.7494 18.2154 22.7443 18.7599 22.6998C19.3899 22.6483 19.9318 22.5404 20.4295 22.2868C21.2291 21.8793 21.8793 21.2291 22.2868 20.4295C22.5404 19.9318 22.6483 19.3899 22.6998 18.7599C22.7391 18.2794 22.7476 17.7094 22.7495 17.028C22.7498 17.0187 22.75 17.0094 22.75 17C22.75 16.9919 22.7499 16.9838 22.7496 16.9757C22.75 16.802 22.75 16.6211 22.75 16.4327V7.56457C22.75 7.37715 22.75 7.19716 22.7496 7.02427C22.7499 7.01621 22.75 7.00812 22.75 7C22.75 6.99061 22.7498 6.98126 22.7495 6.97195C22.7476 6.29062 22.7391 5.72062 22.6998 5.24013C22.6483 4.61012 22.5404 4.06824 22.2868 3.57054C21.8793 2.77085 21.2291 2.12068 20.4295 1.71322C19.9318 1.45963 19.3899 1.35165 18.7599 1.30018C18.2154 1.25569 17.5561 1.25064 16.75 1.25007L7.25 1.25ZM21.25 16.25V12.75H16.75V16.25H21.25ZM16.75 17.75V21.2499C17.5606 21.2492 18.1582 21.244 18.6377 21.2048C19.175 21.1609 19.4975 21.0782 19.7485 20.9503C20.2659 20.6866 20.6866 20.2659 20.9503 19.7485C21.0782 19.4975 21.1609 19.175 21.2048 18.6377C21.2258 18.3807 21.2371 18.0896 21.2431 17.75H16.75ZM2.79519 18.6377C2.77419 18.3807 2.76295 18.0896 2.75693 17.75H7.25V21.2499C6.4394 21.2492 5.84175 21.244 5.36228 21.2048C4.82503 21.1609 4.50252 21.0782 4.25153 20.9503C3.73408 20.6866 3.31338 20.2659 3.04973 19.7485C2.92184 19.4975 2.83909 19.175 2.79519 18.6377ZM2.75 16.25H7.25V12.75H2.75V15.99L2.74998 15.995L2.75 16V16.25ZM21.25 11.25V7.75H16.75V11.25H21.25ZM2.75 11.25H7.25V7.75H2.75V11.25ZM21.2431 6.25C21.2371 5.91039 21.2258 5.61935 21.2048 5.36228C21.1609 4.82503 21.0782 4.50252 20.9503 4.25153C20.6866 3.73408 20.2659 3.31338 19.7485 3.04973C19.4975 2.92184 19.175 2.83909 18.6377 2.79519C18.1582 2.75602 17.5606 2.7508 16.75 2.7501V6.25H21.2431ZM2.75693 6.25H7.25V2.7501C6.4394 2.7508 5.84176 2.75602 5.36228 2.79519C4.82503 2.83909 4.50252 2.92184 4.25153 3.04973C3.73408 3.31338 3.31338 3.73408 3.04973 4.25153C2.92184 4.50252 2.83909 4.82503 2.79519 5.36228C2.77419 5.61935 2.76295 5.91039 2.75693 6.25Z" fill="currentColor"></path></svg>
            <dt>Películas</dt>
            <dd><?= $peliculas ?></dd>
        </div>
        <div class="admin-stat-card">
            <svg class="admin-stat-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M22 16.0001V12.0001C22 9.17169 22 7.75747 21.1213 6.87879C20.296 6.05349 18.9983 6.00336 16.5 6.00031V21.9999C18.9983 21.9969 20.296 21.9467 21.1213 21.1214C22 20.2428 22 18.8285 22 16.0001ZM19 11C19.5523 11 20 11.4477 20 12C20 12.5523 19.5523 13 19 13C18.4477 13 18 12.5523 18 12C18 11.4477 18.4477 11 19 11ZM19 15C19.5523 15 20 15.4477 20 16C20 16.5523 19.5523 17 19 17C18.4477 17 18 16.5523 18 16C18 15.4477 18.4477 15 19 15Z" fill="currentColor"></path><path d="M15.5694 3.48811L13.4163 6.00011H15V22.0001L8 22.0001C5.17157 22.0001 3.75736 22.0001 2.87868 21.1214C2 20.2428 2 18.8285 2 16.0001V12.0001C2 9.17169 2 7.75747 2.87868 6.87879C3.75736 6.00011 5.17157 6.00011 8 6.00011H10.5837L8.43054 3.48811C8.16098 3.17361 8.1974 2.70014 8.51189 2.43057C8.82639 2.16101 9.29986 2.19743 9.56943 2.51192L12 5.34757L14.4305 2.51192C14.7001 2.19743 15.1736 2.161 15.4881 2.43057C15.8026 2.70014 15.839 3.17361 15.5694 3.48811Z" fill="currentColor"></path></svg>
            <dt>Series</dt>
            <dd><?= $series ?></dd>
        </div>
    </dl>
</section>

<section aria-label="generos mas vistos">
    <h2 class="admin-seccion-titulo">Géneros más vistos</h2>
    <?php if ($generosMasVistos === []): ?>
        <p class="perfil-vacio">Sin datos de vistas todavía.</p>
    <?php else: ?>
        <ol class="admin-ranking">
        <?php foreach ($generosMasVistos as $g): ?>
            <li>
                <span><?= htmlspecialchars((string) $g['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="admin-ranking-vistas"><?= (int) $g['vistas'] ?> vistas</span>
            </li>
        <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>

<section aria-label="listado contenido">
    <h2 class="admin-seccion-titulo">Contenido</h2>
    <?php if ($contenidoLocal === []): ?>
        <p class="perfil-vacio">Sin contenido. <a href="/admin/contenido/nuevo">Crear manual</a> o <a href="/admin/contenido/nuevo?modo=tmdb">importar de TMDB</a>.</p>
    <?php else: ?>
    <div class="admin-tabla-wrap">
        <table class="admin-tabla">
            <thead>
                <tr>
                    <th scope="col"></th>
                    <th scope="col">Título</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Año</th>
                    <th scope="col">Calificación</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($contenidoLocal as $item): ?>
                <?php $id = (string) ($item['id'] ?? ''); ?>
                <tr>
                    <td>
                        <?php if (!empty($item['poster_path'])): ?>
                            <div class="poster-marco poster-marco--xs">
                                <img class="poster-img" src="<?= htmlspecialchars((string) $item['poster_path'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="admin-tabla-titulo"><?= htmlspecialchars((string) ($item['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="chip chip--simple"><?= ($item['type'] ?? '') === 'series' ? 'Serie' : 'Película' ?></span></td>
                    <td><?= htmlspecialchars((string) ($item['anio_lanzamiento'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <span class="admin-item-rating">
                            <svg class="admin-item-rating-icono" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" aria-hidden="true">
                                <rect width="256" height="256" fill="none"/>
                                <path d="M234.29,114.85l-45,38.83L203,211.75a16.4,16.4,0,0,1-24.5,17.82L128,198.49,77.47,229.57A16.4,16.4,0,0,1,53,211.75l13.76-58.07-45-38.83A16.46,16.46,0,0,1,31.08,86l59-4.76,22.76-55.08a16.36,16.36,0,0,1,30.27,0l22.75,55.08,59,4.76a16.46,16.46,0,0,1,9.37,28.86Z"/>
                            </svg>
                            <?= ((int) ($item['rating_count'] ?? 0)) > 0
                                ? number_format((float) $item['rating_avg'], 1) . ' (' . (int) $item['rating_count'] . ')'
                                : 'Sin calificaciones' ?>
                        </span>
                    </td>
                    <td>
                        <div class="admin-tabla-acciones">
                            <a href="/admin/contenido/editar?id=<?= urlencode($id) ?>" class="boton boton--secundario boton--sm">editar</a>
                            <form method="POST" action="/admin/contenido/eliminar" class="admin-tabla-acciones-form" onsubmit="return confirm('eliminar?');">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="boton boton--secundario boton--sm">eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

</main>
</body>
</html>
