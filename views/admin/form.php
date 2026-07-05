<?php
/** @var string $csrf */
/** @var string $modo 'crear' | 'editar' */
/** @var array<string,mixed>|null $item */
/** @var list<array{id:int,nombre:string,tmdb_id:?int}> $generos */
/** @var list<int> $generoIdsSeleccionados */
/** @var string|null $errorMsg */

$item = $item ?? [];
$esEditar = $modo === 'editar';
$accion = $esEditar
    ? '/admin/contenido/editar?id=' . urlencode((string) ($item['id'] ?? ''))
    : '/admin/contenido/nuevo';

$titulo = htmlspecialchars((string) ($item['titulo'] ?? ''), ENT_QUOTES, 'UTF-8');
$descripcion = htmlspecialchars((string) ($item['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8');
$tipoActual = (string) ($item['tipo'] ?? $item['type'] ?? 'movie');
$anio = htmlspecialchars((string) ($item['anio'] ?? $item['anio_lanzamiento'] ?? ''), ENT_QUOTES, 'UTF-8');
$posterActual = (string) ($item['poster_path'] ?? '');
$pageTitle = $esEditar ? 'Editar · ' . ($item['titulo'] ?? 'contenido') : 'Nuevo contenido';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin · <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/tokens.css">
    <link rel="stylesheet" href="/assets/css/componentes.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<?php require ROOT . '/views/partials/admin-nav.php'; ?>

<main class="admin-main">

    <h1 class="admin-seccion-titulo"><?= $esEditar ? 'Editar contenido' : 'Nuevo contenido' ?></h1>

    <div class="admin-panel">

        <?php if ($errorMsg): ?>
            <p class="admin-aviso admin-aviso--error" role="alert"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars($accion, ENT_QUOTES, 'UTF-8') ?>"
              enctype="multipart/form-data" class="admin-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

            <div class="admin-campo">
                <label for="titulo">Título</label>
                <input type="text" id="titulo" name="titulo" value="<?= $titulo ?>" maxlength="255" required>
            </div>

            <div class="admin-form-fila">
                <div class="admin-campo">
                    <label for="tipo">Tipo</label>
                    <select id="tipo" name="tipo">
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
                        <label class="chip">
                            <input type="checkbox" class="chip-input" name="generos[]" value="<?= $gid ?>"
                                   <?= in_array($gid, $generoIdsSeleccionados, true) ? 'checked' : '' ?>>
                            <?= htmlspecialchars((string) $genero['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="admin-campo">
                <label for="poster">Poster</label>
                <?php if ($posterActual !== ''): ?>
                    <div class="admin-img-preview-wrap">
                        <img src="<?= htmlspecialchars($posterActual, ENT_QUOTES, 'UTF-8') ?>"
                             alt="" class="admin-poster-preview">
                    </div>
                <?php endif; ?>
                <input type="file" id="poster" name="poster"
                       accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <?php if ($esEditar): ?>
                    <small class="admin-campo-hint">Deja vacío para conservar el poster actual.</small>
                <?php else: ?>
                    <small class="admin-campo-hint">Requerido.</small>
                <?php endif; ?>
            </div>

            <div class="admin-form-acciones">
                <button type="submit" class="boton boton--primario">
                    <?= $esEditar ? 'Guardar cambios' : 'Crear contenido' ?>
                </button>
                <a href="/admin" class="boton boton--secundario">Cancelar</a>
            </div>
        </form>
    </div>

</main>
</body>
</html>
