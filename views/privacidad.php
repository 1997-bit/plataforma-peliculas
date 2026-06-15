<!doctype html>
<html lang="es" data-tema="dark">
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<meta
			name="description"
			content="Cómo usamos y protegemos tus datos conforme a la Ley 81 de 2019 de Panamá."
		/>
		<title>Privacidad</title>
		<link
			rel="icon"
			href="/assets/images/favicon.svg"
			type="image/svg+xml"
		/>
		<link rel="stylesheet" href="/assets/css/base.css" />
		<link rel="stylesheet" href="/assets/css/footer.css" />
		<link rel="stylesheet" href="/assets/css/navbar.css" />
		<link rel="stylesheet" href="/assets/css/legal.css" />
	</head>
	<body>
		<?php $navClass = 'nav';
		require ROOT . '/views/partials/nav.php'; ?>

		<main class="legal-contenido">
			<div class="legal-header">
				<h1>Política de Privacidad</h1>
				<p class="legal-meta">
					Última actualización: 14 de junio de 2026 · Ley N° 81 de
					2019, República de Panamá.
				</p>
			</div>

			<ol class="legal-lista">
				<li>
					<p>
						<strong>Quiénes somos.</strong> CineApp es una
						plataforma para descubrir, registrar y compartir
						películas, operada desde la República de Panamá. Para
						consultas de privacidad:
						<strong>contacto@cineapp.com.pa</strong>
					</p>
				</li>

				<li>
					<p>
						<strong>Qué datos guardamos.</strong> Solo lo necesario:
					</p>
					<ul>
						<li>
							Correo electrónico y nombre de usuario al
							registrarte.
						</li>
						<li>
							Tu contraseña, almacenada de forma irreversible con
							Argon2id. Nunca tenemos acceso a ella en texto
							plano.
						</li>
						<li>
							Historial de películas vistas, calificaciones y
							preferencias.
						</li>
						<li>
							Dirección IP, registrada temporalmente por
							seguridad.
						</li>
						<li>
							Preferencia de tema visual, guardada en una cookie
							local.
						</li>
					</ul>
					<p>
						No recopilamos datos sensibles: salud, religión, etnia,
						afiliación política ni datos biométricos.
					</p>
				</li>

				<li>
					<p>
						<strong>Para qué usamos tus datos.</strong> Únicamente
						para:
					</p>
					<ul>
						<li>Gestionar tu cuenta y autenticarte.</li>
						<li>
							Ofrecerte recomendaciones basadas en tu historial.
						</li>
						<li>
							Prevenir fraude y proteger la seguridad del
							servicio.
						</li>
					</ul>
					<p>
						No usaremos tus datos para ningún otro fin sin tu
						consentimiento expreso.
					</p>
				</li>

				<li>
					<p>
						<strong>Cuánto tiempo los guardamos.</strong> Mientras
						tengas una cuenta activa. Si eliminas tu cuenta,
						borramos tus datos en máximo 10 días hábiles. Los
						registros de seguridad se eliminan automáticamente a los
						90 días.
					</p>
				</li>

				<li>
					<p>
						<strong>Terceros.</strong> No vendemos ni cedemos tus
						datos. Usamos la API de The Movie Database (TMDB) para
						el catálogo. Las consultas no incluyen ningún dato tuyo.
						Todos tus datos permanecen en servidores dentro de
						Panamá.
					</p>
				</li>

				<li>
					<p>
						<strong>Seguridad.</strong> Aplicamos cifrado para tus
						datos. Si detectamos una brecha que te afecte, te
						notificamos a la brevedad.
					</p>
				</li>

				<li>
					<p>
						<strong>Tus derechos (ARCO).</strong> Conforme al Art.
						15 de la Ley 81, puedes en cualquier momento:
					</p>
					<ul>
						<li>
							<strong>Acceder</strong> a los datos que tenemos
							sobre ti.
						</li>
						<li><strong>Rectificar</strong> datos incorrectos.</li>
						<li>
							<strong>Cancelar</strong> tu cuenta y eliminar tus
							datos.
						</li>
						<li><strong>Oponerte</strong> a determinados usos.</li>
						<li>
							<strong>Portabilidad</strong> obtener una copia de
							tus datos.
						</li>
					</ul>
					<p>
						Escríbenos a <strong>contacto@cineapp.com.pa</strong>,
						respondemos en máximo 10 días hábiles. Si no recibes
						respuesta, puedes acudir a la
						<a
							href="https://www.antai.gob.pa"
							target="_blank"
							rel="noopener noreferrer"
							>ANTAI</a
						>.
					</p>
				</li>

				<li>
					<p><strong>Cookies.</strong> Solo las necesarias:</p>
					<ul>
						<li>
							<strong>PHPSESSID</strong>: sesión técnica, se borra
							al cerrar el navegador.
						</li>
						<li>
							<strong>remember_token</strong>: para mantenerte
							conectado si activas "Recuérdame", vigencia 30 días.
						</li>
						<li>
							<strong>tema</strong>: preferencia visual, vigencia
							1 año, sin datos personales.
						</li>
					</ul>
					<p>Sin cookies de publicidad ni rastreo.</p>
				</li>

				<li>
					<p>
						<strong>Edad mínima.</strong> Debes tener al menos 14
						años. Al registrarte confirmas que cumples este
						requisito.
					</p>
				</li>

				<li>
					<p>
						<strong>Cambios.</strong> Si hacemos cambios
						importantes, te avisamos por correo con al menos 15 días
						de anticipación.
					</p>
				</li>

				<li>
					<p>
						<strong>Contacto.</strong>
						<strong>contacto@cineapp.com.pa</strong>. Respuesta en
						máximo 10 días hábiles.
					</p>
				</li>
			</ol>
		</main>

		<?php require ROOT . '/views/partials/footer.php'; ?>
	</body>
</html>
