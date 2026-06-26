<?php
$usuarioLogueado = \App\Core\Session::existe('user_id');
$nombreUsuario = \App\Core\Session::obtener('username');
?>
<header class="nav">
	<a href="/" class="nav-logo">PANCONQUESO</a>
	<nav aria-label="Navegación principal">
		<?php if ($usuarioLogueado): ?>
		<a href="/home">Inicio</a>
		<a href="/catalogo">Catálogo</a>
		<a href="/recomendaciones">Para ti</a>
		<button type="button" popovertarget="menu-cuenta" class="nav-cuenta-boton">
			<?= $nombreUsuario ? htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') : 'Mi cuenta' ?>
			<svg width="12" height="12" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true" class="nav-cuenta-flecha">
				<path d="M213.66,101.66l-80,80a8,8,0,0,1-11.32,0l-80-80A8,8,0,0,1,53.66,90.34L128,164.69l74.34-74.35a8,8,0,0,1,11.32,11.32Z"></path>
			</svg>
		</button>

		<menu id="menu-cuenta" popover class="nav-dropdown">
			<li>
				<a href="/profile" class="nav-dropdown-item">Mi perfil</a>
			</li>
			<li>
				<a href="/settings" class="nav-dropdown-item">Configuración</a>
			</li>
			<li class="nav-dropdown-separador"></li>
			<li>
				<button type="button" commandfor="dialog-logout" command="show-modal" class="nav-dropdown-item nav-dropdown-item-peligro">
					Cerrar sesión
				</button>
			</li>
		</menu>

		<dialog id="dialog-logout" class="dialogo-confirmar">
			<p class="dialogo-confirmar-texto">¿Seguro que querés cerrar sesión?</p>
			<div class="dialogo-confirmar-acciones">
				<button type="button" commandfor="dialog-logout" command="close" class="dialogo-confirmar-cancelar">
					Cancelar
				</button>
				<form method="POST" action="/logout">
					<input
						type="hidden"
						name="_csrf"
						value="<?= \App\Core\Session::generarCsrf() ?>"
					/>
					<button type="submit" class="dialogo-confirmar-aceptar">Cerrar sesión</button>
				</form>
			</div>
		</dialog>
		<?php else: ?>
		<a href="/login">Iniciar sesión</a>
		<a href="/register" class="btn-nav">Registrarse</a>
		<?php endif; ?>
	</nav>
</header>
