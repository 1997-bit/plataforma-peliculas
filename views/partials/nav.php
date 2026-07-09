<?php
$usuarioLogueado = \App\Core\Session::existe('user_id');
$nombreUsuario = \App\Core\Session::obtener('username');
$rutaActual = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$esActiva = static fn(string $ruta): string => $rutaActual === $ruta ? ' aria-current="page"' : '';
$rutaLogo = $usuarioLogueado
	? (\App\Core\Session::obtener('user_role') === 'admin' ? '/admin' : '/home')
	: '/';
?>
<header class="nav">
	<a href="<?= $rutaLogo ?>" class="nav-logo">CINEAPP</a>
	<nav aria-label="Navegación principal">
		<?php if ($usuarioLogueado): ?>
		<div class="nav-links">
			<a href="/home" class="nav-link"<?= $esActiva('/home') ?>>
				<svg width="20" height="20" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true"><path d="M224,120v96a8,8,0,0,1-8,8H160a8,8,0,0,1-8-8V164a4,4,0,0,0-4-4H108a4,4,0,0,0-4,4v52a8,8,0,0,1-8,8H40a8,8,0,0,1-8-8V120a16,16,0,0,1,4.69-11.31l80-80a16,16,0,0,1,22.62,0l80,80A16,16,0,0,1,224,120Z"></path></svg>
				<span>Inicio</span>
			</a>
			<a href="/catalogo" class="nav-link"<?= $esActiva('/catalogo') ?>>
				<svg width="20" height="20" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true"><path d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40Zm0,16V184H40V56ZM88,72H56V56H88Zm16-16h32V184H104Zm48,0h32V184H152ZM40,184V72H72V184Zm160,0H168V72h32Z"></path></svg>
				<span>Catálogo</span>
			</a>
			<a href="/recomendaciones" class="nav-link"<?= $esActiva('/recomendaciones') ?>>
				<svg width="20" height="20" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true"><path d="M208,144a15.84,15.84,0,0,1-10.6,15.06l-51.75,18.41-18.41,51.75a15.86,15.86,0,0,1-30.08,0L78.75,177.47,27,159.06a15.86,15.86,0,0,1,0-30.12l51.75-18.41,18.41-51.75a15.86,15.86,0,0,1,30.08,0l18.41,51.75,51.75,18.41A15.84,15.84,0,0,1,208,144ZM152,48h16V64a8,8,0,0,0,16,0V48h16a8,8,0,0,0,0-16H184V16a8,8,0,0,0-16,0V32H152a8,8,0,0,0,0,16Zm88,32H224V64a8,8,0,0,0-16,0V80H192a8,8,0,0,0,0,16h16v16a8,8,0,0,0,16,0V96h16a8,8,0,0,0,0-16Z"></path></svg>
				<span>Para ti</span>
			</a>
		</div>
		<button type="button" popovertarget="menu-cuenta" class="nav-cuenta-boton">
			<svg width="20" height="20" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true"><path d="M172,120a44,44,0,1,1-44-44A44.05,44.05,0,0,1,172,120Zm60,8A104,104,0,1,1,128,24,104.11,104.11,0,0,1,232,128Zm-16,0a88.09,88.09,0,0,0-91.47-87.93C77.43,41.89,39.87,81.12,40,128.25a87.65,87.65,0,0,0,22.24,58.16A79.71,79.71,0,0,1,84,165.1a4,4,0,0,1,4.83.32,59.83,59.83,0,0,0,78.28,0,4,4,0,0,1,4.83-.32,79.71,79.71,0,0,1,21.79,21.31A87.62,87.62,0,0,0,216,128Z"></path></svg>
			<span><?= $nombreUsuario ? htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') : 'Mi cuenta' ?></span>
			<svg width="12" height="12" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true" class="nav-cuenta-flecha">
				<path d="M213.66,101.66l-80,80a8,8,0,0,1-11.32,0l-80-80A8,8,0,0,1,53.66,90.34L128,164.69l74.34-74.35a8,8,0,0,1,11.32,11.32Z"></path>
			</svg>
		</button>

		<menu id="menu-cuenta" popover class="nav-dropdown">
			<li>
				<a href="/perfil" class="nav-dropdown-item">Mi perfil</a>
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
			<p class="dialogo-confirmar-texto">¿Seguro que quieres cerrar sesión?</p>
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