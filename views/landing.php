<?php $tema = $_COOKIE['tema'] ?? null; ?>
<!doctype html>
<html lang="es" data-tema="dark">
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<meta
			name="description"
			content="Descubre las películas que definen quién eres."
		/>
		<title>CineApp</title>
		<link rel="stylesheet" href="/assets/css/base.css" />
		<link rel="stylesheet" href="/assets/css/landing.css" />
	</head>
	<body>
		<header class="nav">
			<span class="nav-logo">PANCONQUESO</span>
			<nav>
				<a href="/login">Iniciar sesión</a>
				<a href="/register" class="btn-nav">Registrarse</a>
			</nav>
		</header>

		<main>
			<section class="hero" aria-label="Presentación">
				<img
					src="/assets/images/hero.webp"
					alt=""
					role="presentation"
					class="hero-imagen"
					fetchpriority="high"
					decoding="async"
					width="1920"
					height="1080"
				/>
				<div class="hero-contenido">
					<h1>Qué ver,<br />Resuelto.</h1>
					<p>
						Recomendaciones de películas y 
						series hechas para ti
					</p>
					<a href="/register" class="btn-primario">Empieza gratis</a>
				</div>
			</section>

			<section class="posters-seccion" aria-label="Películas destacadas">
				<ul class="posters-grid" role="list">
					<li class="poster-card">
						<img
							src="/assets/images/posters/BIG-LEBOWSKI.webp"
							alt="The Big Lebowski"
							width="300"
							height="450"
							loading="lazy"
							decoding="async"
						/>
					</li>
					<li class="poster-card">
						<img
							src="/assets/images/posters/PREDATOR.webp"
							alt="Predator"
							width="300"
							height="450"
							loading="lazy"
							decoding="async"
						/>
					</li>
					<li class="poster-card">
						<img
							src="/assets/images/posters/DISCLOSUREDAY.webp"
							alt="Disclosure Day"
							width="300"
							height="450"
							loading="lazy"
							decoding="async"
						/>
					</li>
					<li class="poster-card">
						<img
							src="/assets/images/posters/DANDADAN.webp"
							alt="Dandadan"
							width="300"
							height="450"
							loading="lazy"
							decoding="async"
						/>
					</li>
					<li class="poster-card">
						<img
							src="/assets/images/posters/EVANGELION.webp"
							alt="Evangelion"
							width="300"
							height="450"
							loading="lazy"
							decoding="async"
						/>
					</li>
					<li class="poster-card">
						<img
							src="/assets/images/posters/THE-WARRIORS.webp"
							alt="The Warriors"
							width="300"
							height="450"
							loading="lazy"
							decoding="async"
						/>
					</li>
				</ul>
			</section>

			<section class="trusted-seccion" aria-label="Usado por">
				<p class="trusted-label">Usado por</p>
				<ul class="trusted-grid" role="list">
					<li>
						<a
							href="https://www.analog-kidz.com"
							target="_blank"
							rel="noopener noreferrer"
							aria-label="Visitar Analog Kidz (abre en nueva pestaña)"
						>
							<img
								src="/assets/images/logos/ANALOGKIDZ.webp"
								alt="Analog Kidz"
								width="120"
								height="48"
								loading="lazy"
								decoding="async"
							/>
						</a>
					</li>
					<li>
						<a
							href="https://dicine.micultura.gob.pa"
							target="_blank"
							rel="noopener noreferrer"
							aria-label="Visitar DiCine (abre en nueva pestaña)"
						>
							<img
								src="/assets/images/logos/DICINE.webp"
								alt="DiCine"
								width="120"
								height="48"
								loading="lazy"
								decoding="async"
							/>
						</a>
					</li>
					<li>
						<a
							href="https://www.gecupanama.org"
							target="_blank"
							rel="noopener noreferrer"
							aria-label="Visitar GECU (abre en nueva pestaña)"
						>
							<img
								src="/assets/images/logos/GECU.webp"
								alt="GECU"
								width="120"
								height="48"
								loading="lazy"
								decoding="async"
							/>
						</a>
					</li>
					<li>
						<a
							href="https://iffpanama.ong"
							target="_blank"
							rel="noopener noreferrer"
							aria-label="Visitar IFF Panama (abre en nueva pestaña)"
						>
							<img
								src="/assets/images/logos/Iff.webp"
								alt="IFF Panama"
								width="120"
								height="48"
								loading="lazy"
								decoding="async"
							/>
						</a>
					</li>
					<li>
						<a
							href="https://www.lucky13filmsservices.com/"
							target="_blank"
							rel="noopener noreferrer"
							aria-label="Visitar Lucky 13 (abre en nueva pestaña)"
						>
							<img
								src="/assets/images/logos/LUCKY13.webp"
								alt="Lucky 13"
								width="120"
								height="48"
								loading="lazy"
								decoding="async"
							/>
						</a>
					</li>
					<li>
						<a
							href="https://mentepublica.org/"
							target="_blank"
							rel="noopener noreferrer"
							aria-label="Visitar Mente Pública (abre en nueva pestaña)"
						>
							<img
								src="/assets/images/logos/MENTEPUBLICA.webp"
								alt="Mente Pública"
								width="120"
								height="48"
								loading="lazy"
								decoding="async"
							/>
						</a>
					</li>
				</ul>
			</section>
		</main>

		<footer class="footer">
			<div class="footer-grid">
				<div class="footer-marca">
					<span class="footer-logo">CineApp</span>
					<p>Tu cine, tu historia.</p>
				</div>
				<nav class="footer-col" aria-label="Producto">
					<p class="footer-col-titulo">Producto</p>
					<ul>
						<li><a href="#">Características</a></li>
						<li><a href="#">Precios</a></li>
						<li><a href="#">Novedades</a></li>
					</ul>
				</nav>
				<nav class="footer-col" aria-label="Legal">
					<p class="footer-col-titulo">Legal</p>
					<ul>
						<li><a href="#">Privacidad</a></li>
						<li><a href="#">Términos</a></li>
						<li><a href="#">Cookies</a></li>
					</ul>
				</nav>
				<div class="footer-social">
					<a
						href="https://github.com/1997-bit/plataforma-peliculas"
						target="_blank"
						rel="noopener noreferrer"
						aria-label="GitHub (abre en nueva pestaña)"
					>
						<svg
							width="20"
							height="20"
							viewBox="0 0 24 24"
							fill="currentColor"
							aria-hidden="true"
						>
							<path
								d="M12 2C6.477 2 2 6.477 2 12c0 4.418 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.009-.868-.013-1.703-2.782.604-3.369-1.342-3.369-1.342-.454-1.155-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0 1 12 6.836a9.59 9.59 0 0 1 2.504.337c1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.741 0 .267.18.578.688.48C19.138 20.163 22 16.418 22 12c0-5.523-4.477-10-10-10z"
							/>
						</svg>
					</a>
				</div>
			</div>
			<div class="footer-bottom">
				<p>&copy; <?= date('Y') ?> CineApp</p>
			</div>
		</footer>
	</body>
</html>
