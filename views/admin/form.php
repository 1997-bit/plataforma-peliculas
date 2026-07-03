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
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= $esEditar ? 'Editar contenido' : 'Nuevo contenido' ?></title>
	<link rel="stylesheet" href="/assets/css/base.css" />
	<link rel="stylesheet" href="/assets/css/tokens.css" />
	<link rel="stylesheet" href="/assets/css/componentes.css" />
</head>
<body>
	<main class="perfil">
		<h1 class="perfil-titulo"><?= $esEditar ? 'Editar contenido' : 'Nuevo contenido' ?></h1>

		<?php if ($errorMsg): ?>
			<p class="perfil-aviso perfil-aviso-error"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></p>
		<?php endif; ?>

		<form method="POST" action="<?= htmlspecialchars($accion, ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" class="perfil-form">
			<input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

			<div class="perfil-campo">
				<label for="titulo">Título</label>
				<input type="text" id="titulo" name="titulo" value="<?= $titulo ?>" maxlength="255" required>
			</div>

			<div class="perfil-campo">
				<label for="tipo">Tipo</label>
				<select id="tipo" name="tipo" required>
					<option value="movie" <?= $tipoActual === 'movie' ? 'selected' : '' ?>>Película</option>
					<option value="series" <?= $tipoActual === 'series' ? 'selected' : '' ?>>Serie</option>
				</select>
			</div>

			<div class="perfil-campo">
				<label for="descripcion">Descripción</label>
				<textarea id="descripcion" name="descripcion" maxlength="2000" rows="5"><?= $descripcion ?></textarea>
			</div>

			<div class="perfil-campo">
				<label for="anio">Año</label>
				<input type="number" id="anio" name="anio" value="<?= $anio ?>" min="1888" max="<?= (int) date('Y') + 1 ?>">
			</div>

			<div class="perfil-campo">
				<label for="generos">Géneros</label>
				<select id="generos" name="generos[]" multiple size="8">
					<?php foreach ($generos as $genero): ?>
						<?php $gid = (int) $genero['id']; ?>
						<option value="<?= $gid ?>" <?= in_array($gid, $generoIdsSeleccionados, true) ? 'selected' : '' ?>>
							<?= htmlspecialchars((string) $genero['nombre'], ENT_QUOTES, 'UTF-8') ?>
						</option>
					<?php endforeach; ?>
				</select>
				<small>Mantené Ctrl (o Cmd) apretado para elegir varios.</small>
			</div>

			<div class="perfil-campo">
				<label for="poster">Poster <?= $esEditar ? '(dejar vacío para no cambiarlo)' : '' ?></label>
				<?php if ($posterActual !== ''): ?>
					<img src="<?= htmlspecialchars($posterActual, ENT_QUOTES, 'UTF-8') ?>" alt="Poster actual" class="poster-preview">
				<?php endif; ?>
				<input type="file" id="poster" name="poster" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" <?= $esEditar ? '' : 'required' ?>>
			</div>

			<button type="submit" class="boton boton--primario"><?= $esEditar ? 'Guardar cambios' : 'Crear contenido' ?></button>
			<a href="/admin" class="boton boton--secundario">Cancelar</a>
		</form>
	</main>
</body>
</html>
