<?php
$usuarioLogueado = \App\Core\Session::existe('user_id');
?>
<header class="nav">
	<a href="/" class="nav-logo">PANCONQUESO</a>
	<nav aria-label="Navegación principal">
		<?php if ($usuarioLogueado): ?>
		<a href="/home">Inicio</a>
		<form method="POST" action="/logout" style="display: inline">
			<input
				type="hidden"
				name="_csrf"
				value="<?= \App\Core\Session::generarCsrf() ?>"
			/>
			<button type="submit" class="btn-nav btn-nav-con-icono">
				<svg width="16" height="16" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true">
					<path d="M120,216a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V40a8,8,0,0,1,8-8h64a8,8,0,0,1,0,16H56V208h56A8,8,0,0,1,120,216Zm109.66-93.66-40-40A8,8,0,0,0,176,88v32H112a8,8,0,0,0,0,16h64v32a8,8,0,0,0,13.66,5.66l40-40A8,8,0,0,0,229.66,122.34Z"></path>
				</svg>
				Cerrar sesión
			</button>
		</form>
		<?php else: ?>
		<a href="/login">Iniciar sesión</a>
		<a href="/register" class="btn-nav">Registrarse</a>
		<?php endif; ?>
	</nav>
</header>
