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
		<link
			rel="icon"
			href="/assets/images/favicon.svg"
			type="image/svg+xml"
		/>

		<link rel="stylesheet" href="/assets/css/base.css" />
		<link rel="stylesheet" href="/assets/css/footer.css" />
		<link rel="stylesheet" href="/assets/css/navbar.css" />
		<link rel="stylesheet" href="/assets/css/landing.css" />
	</head>
	<body>
		<?php $navClass = 'nav'; require ROOT . '/views/partials/nav.php'; ?>

		<main>
			<section class="hero" aria-label="Presentación">
				<img
					rel="preload"
					src="/assets/images/hero.webp"
					alt=""
					role="presentation"
					class="hero-imagen"
					fetchpriority="high"
					decoding="async"
					width="1920"
					height="1080"
					fetchpriority="high"
				/>
				<div class="hero-contenido">
					<h1>Qué ver,<br />Resuelto.</h1>
					<p>Recomendaciones de películas y series hechas para ti</p>
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
							src="/assets/images/posters/DOMINGOYLANIEBLA.webp"
							alt="Domingo y la Niebla"
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
							src="/assets/images/posters/ELBRUJO.webp"
							alt="El Brujo"
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

					<li class="poster-card">
						<img
							src="/assets/images/posters/VOCACION.webp"
							alt="Vocación"
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
		<?php require ROOT . '/views/partials/footer.php'; ?>
	</body>
</html>
