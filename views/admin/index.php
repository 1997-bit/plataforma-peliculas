<?php
/** @var string $csrf */
/** @var string|null $okMsg */
/** @var string|null $errorMsg */
/** @var list<array<string,mixed>> $contenidoLocal */

$okMsg = $okMsg ?? null;
$errorMsg = $errorMsg ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Panel de administrador</title>
	<link rel="stylesheet" href="/assets/css/base.css" />
	<link rel="stylesheet" href="/assets/css/tokens.css" />
	<link rel="stylesheet" href="/assets/css/componentes.css" />
</head>
<body>
	<main class="perfil">
		<h1 class="perfil-titulo">Panel de administrador</h1>

		<!-- Dejé este bloque para probar el XML del catálogo desde aquí mismo. -->

		<?php if ($okMsg): ?>
			<p class="perfil-aviso perfil-aviso-ok"><?= htmlspecialchars($okMsg, ENT_QUOTES, 'UTF-8') ?></p>
		<?php endif; ?>

		<?php if ($errorMsg): ?>
			<p class="perfil-aviso perfil-aviso-error"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></p>
		<?php endif; ?>

		<section class="perfil-seccion">
			<h2 class="perfil-subtitulo">XML del catálogo</h2>
			<p class="perfil-vacio">Importa un archivo XML con contenido local o exporta el catálogo cargado actualmente.</p>

			<div class="perfil-export-acciones">
				<a href="/admin/xml/exportar" class="boton boton--secundario">Exportar catálogo (XML)</a>
			</div>

			<form method="POST" action="/admin/xml/importar" enctype="multipart/form-data" class="perfil-form perfil-form--importar">
				<input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

				<div class="perfil-campo">
					<label for="xml_catalogo">Importar catálogo XML</label>
					<input type="file" id="xml_catalogo" name="xml_catalogo" accept=".xml,application/xml,text/xml" required>
				</div>

				<button type="submit" class="boton boton--primario">Importar XML</button>
			</form>
		</section>

		<section class="perfil-seccion">
			<h2 class="perfil-subtitulo">Contenido local reciente</h2>
			<?php if ($contenidoLocal === []): ?>
				<p class="perfil-vacio">Todavía no hay contenido local cargado.</p>
			<?php else: ?>
				<ul>
					<?php foreach ($contenidoLocal as $item): ?>
						<li><?= htmlspecialchars((string) ($item['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) ($item['type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	</main>
</body>
</html>
