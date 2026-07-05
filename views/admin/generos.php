<?php
/** @var string $csrf */
/** @var string|null $okMsg */
/** @var list<array{id:int,nombre:string,tmdb_id:int|null}> $generos */
$okMsg = $okMsg ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin · Géneros</title>
    <link rel="stylesheet" href="/assets/css/base.css" />
    <link rel="stylesheet" href="/assets/css/tokens.css" />
    <link rel="stylesheet" href="/assets/css/componentes.css" />
    <link rel="stylesheet" href="/assets/css/admin.css" />
</head>
<body>
<?php require ROOT . '/views/partials/admin-nav.php'; ?>
<main class="admin-main">

    <?php if ($okMsg): ?>
        <p class="perfil-aviso perfil-aviso-ok" role="status"><?= htmlspecialchars($okMsg, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <section aria-label="agregar genero">
        <h2 class="admin-seccion-titulo">Agregar género</h2>
        <form method="POST" action="/admin/generos/crear" class="admin-tmdb-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="nombre" placeholder="Nombre del género" required maxlength="50">
            <button type="submit" class="boton boton--primario">Crear</button>
        </form>
    </section>

    <section aria-label="lista generos">
        <h2 class="admin-seccion-titulo">Géneros (<?= count($generos) ?>)</h2>
        <?php if ($generos === []): ?>
            <p class="perfil-vacio">Sin géneros todavía.</p>
        <?php else: ?>
        <div class="admin-lista">
        <?php foreach ($generos as $g): ?>
            <article class="admin-item">
                <div class="admin-item-info">
                    <span class="admin-item-titulo"><?= htmlspecialchars((string) $g['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($g['tmdb_id'] !== null): ?>
                        <div class="admin-item-meta">
                            <span class="chip chip--simple">TMDB #<?= (int) $g['tmdb_id'] ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="admin-item-acciones">
                    <form method="POST" action="/admin/generos/eliminar" onsubmit="return confirm('Eliminar género y quitarlo de todo el contenido asociado?');">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                        <button type="submit" class="boton boton--secundario">eliminar</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

</main>
</body>
</html>
