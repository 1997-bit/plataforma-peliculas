<?php
/** @var string $csrf */
/** @var string|null $okMsg */
/** @var string|null $errorMsg */
/** @var list<array<string,mixed>> $contenidoLocal */

$okMsg = $okMsg ?? null;
$errorMsg = $errorMsg ?? null;

$total = count($contenidoLocal);
$activos = count(array_filter($contenidoLocal, fn ($c) => (int) ($c['is_active'] ?? 0) === 1));
$peliculas = count(array_filter($contenidoLocal, fn ($c) => ($c['type'] ?? '') === 'movie'));
$series = count(array_filter($contenidoLocal, fn ($c) => ($c['type'] ?? '') === 'series'));
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
</head>
<body>
<header class="admin-header">
	<h1>Admin</h1>
	<nav>
		<a href="/admin">Contenido</a>
		<a href="/admin/contenido/nuevo">+ Nuevo</a>
	</nav>
</header>

<main>

<?php if ($okMsg): ?>
	<div role="status"><?= htmlspecialchars($okMsg, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($errorMsg): ?>
	<div role="alert"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section aria-label="resumen">
	<dl>
		<div><dt>Total</dt><dd><?= $total ?></dd></div>
		<div><dt>Activos</dt><dd><?= $activos ?></dd></div>
		<div><dt>Inactivos</dt><dd><?= $total - $activos ?></dd></div>
		<div><dt>Películas</dt><dd><?= $peliculas ?></dd></div>
		<div><dt>Series</dt><dd><?= $series ?></dd></div>
	</dl>
</section>

<section aria-label="listado contenido">

<?php if ($contenidoLocal === []): ?>

	<p>No hay contenido cargado todavía. <a href="/admin/contenido/nuevo">Crear el primero</a>.</p>

<?php else: ?>

	<?php foreach ($contenidoLocal as $item): ?>
		<?php
		$id = (string) ($item['id'] ?? '');
		$activo = (int) ($item['is_active'] ?? 0) === 1;
		?>
		<article>
			<?php if (!empty($item['poster_path'])): ?>
				<img src="<?= htmlspecialchars((string) $item['poster_path'], ENT_QUOTES, 'UTF-8') ?>" alt="" width="60">
			<?php endif; ?>

			<div>
				<strong><?= htmlspecialchars((string) ($item['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
				<span><?= htmlspecialchars((string) ($item['type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
				<span><?= htmlspecialchars((string) ($item['anio_lanzamiento'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span>
				<span><?= $activo ? 'activo' : 'inactivo' ?></span>
			</div>

			<div>
				<a href="/admin/contenido/editar?id=<?= urlencode($id) ?>">editar</a>

				<form method="POST" action="/admin/contenido/eliminar" onsubmit="return confirm('eliminar (soft delete)?');" style="display:inline">
					<input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
					<input type="hidden" name="id" value="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
					<button type="submit">eliminar</button>
				</form>
			</div>
		</article>
	<?php endforeach; ?>

<?php endif; ?>

</section>

<section aria-label="import/export xml">
	<h2>XML</h2>
	<a href="/admin/xml/exportar">exportar</a>

	<form method="POST" action="/admin/xml/importar" enctype="multipart/form-data">
		<input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
		<input type="file" name="xml_catalogo" accept=".xml,application/xml,text/xml" required>
		<button type="submit">importar</button>
	</form>
</section>

</main>
</body>
</html>
