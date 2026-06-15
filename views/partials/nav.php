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
			<button type="submit" class="btn-nav">Cerrar sesión</button>
		</form>
		<?php else: ?>
		<a href="/login">Iniciar sesión</a>
		<a href="/register" class="btn-nav">Registrarse</a>
		<?php endif; ?>
	</nav>
</header>
