<?php
/**
 * Marco visual del hero: imagen de fondo (backdrop) + degradado.
 * Sin texto, sin carrusel, sin botones: eso lo agrega cada vista que
 * lo incluye, como contenido extra dentro de .hero-viewport.
 *
 * Variables esperadas:
 * @var string|null $heroFondoUrl   URL ya resuelta del backdrop (o null)
 * @var string      $heroFondoClase clases extra para .hero (ej. altura distinta)
 */

$heroFondoClase = $heroFondoClase ?? '';
?>
<div class="hero-slide hero-slide--activo" <?= $heroFondoUrl ? 'style="background-image: url(' . htmlspecialchars($heroFondoUrl, ENT_QUOTES, 'UTF-8') . ')"' : '' ?>>
</div>
<div class="hero-veladura"></div>
