<?php
/** @var string $csrf */
/** @var list<array<string,mixed>> $resultados */
/** @var string $q */
/** @var string $tipo */
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Importar desde TMDB</title>
	<link rel="stylesheet" href="/assets/css/base.css" />
	<link rel="stylesheet" href="/assets/css/tokens.css" />
	<link rel="stylesheet" href="/assets/css/componentes.css" />
</head>
<body>
<header class="admin-header">
	<h1>Admin</h1>
	<nav>
		<a href="/admin">Contenido</a>
		<a href="/admin/contenido/nuevo">+ Manual</a>
		<a href="/admin/tmdb/buscar" aria-current="page">Importar TMDB</a>
	</nav>
</header>

<main>
	<h2>Buscar en TMDB e importar</h2>

	<form method="GET" action="/admin/tmdb/buscar">
		<input type="text" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Título..." required autofocus>
		<select name="tipo">
			<option value="movie" <?= $tipo === 'movie'  ? 'selected' : '' ?>>Película</option>
			<option value="series" <?= $tipo === 'series' ? 'selected' : '' ?>>Serie</option>
		</select>
		<button type="submit">Buscar</button>
	</form>

	<?php if ($q !== '' && $resultados === []): ?>
		<p>Sin resultados para "<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>".</p>
	<?php endif; ?>

	<?php foreach ($resultados as $item): ?>
		<?php
		$tmdbId = (int) ($item['id'] ?? 0);
		$titulo = htmlspecialchars((string) ($item['title'] ?? $item['name'] ?? ''), ENT_QUOTES, 'UTF-8');
		$anio = substr((string) ($item['release_date'] ?? $item['first_air_date'] ?? ''), 0, 4);
		$poster = (string) ($item['poster_path'] ?? '');
		$overview = htmlspecialchars((string) ($item['overview'] ?? ''), ENT_QUOTES, 'UTF-8');
		?>
		<article style="display:flex;gap:1rem;align-items:flex-start;margin-bottom:1rem">
			<?php if ($poster !== ''): ?>
				<img src="https://image.tmdb.org/t/p/w92<?= htmlspecialchars($poster, ENT_QUOTES, 'UTF-8') ?>"
				     alt="" width="60" style="flex-shrink:0">
			<?php endif; ?>

			<div style="flex:1">
				<strong><?= $titulo ?></strong>
				<?php if ($anio !== ''): ?>
					<span>(<?= htmlspecialchars($anio, ENT_QUOTES, 'UTF-8') ?>)</span>
				<?php endif; ?>
				<?php if ($overview !== ''): ?>
					<p style="margin:.25rem 0;font-size:.875rem"><?= mb_substr($overview, 0, 120) ?>...</p>
				<?php endif; ?>
			</div>

			<form method="POST" action="/admin/tmdb/importar" style="flex-shrink:0">
				<input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
				<input type="hidden" name="tmdb_id" value="<?= $tmdbId ?>">
				<input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?>">
				<button type="submit" class="boton boton--primario">Importar</button>
			</form>
		</article>
	<?php endforeach; ?>

	<p style="margin-top:2rem"><a href="/admin">Volver al panel</a></p>
</main>
</body>
</html>
