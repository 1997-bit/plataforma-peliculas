<?php $error = $error ?? null; ?>
<!doctype html>
<html lang="es" data-tema="dark">
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<title>Registro</title>
		<link rel="stylesheet" href="/assets/css/base.css" />
		<link rel="stylesheet" href="/assets/css/auth.css" />
	</head>
	<body>
		<main class="auth-pagina">
			<div class="auth-card">
				<h1 class="auth-titulo">Crear cuenta</h1>

				<?php if ($error): ?>
				<p class="auth-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
				<?php endif; ?>

				<form method="POST" action="/register" class="auth-form">
					<input
						type="hidden"
						name="_csrf"
						value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"
					/>

					<div class="auth-campo">
						<label for="username">Nombre</label>
						<input
							type="text"
							id="username"
							name="username"
							autocomplete="username"
							minlength="3"
							maxlength="32"
							pattern="[a-zA-Z0-9_]+"
							required
						/>
					</div>

					<div class="auth-campo">
						<label for="email">Email</label>
						<input
							type="email"
							id="email"
							name="email"
							required
							autocomplete="email"
						/>
					</div>

					<div class="auth-campo">
						<label for="password">Contrasena</label>
						<input
							type="password"
							id="password"
							name="password"
							autocomplete="new-password"
							required
						/>
					</div>

					<div class="auth-campo">
						<label for="password_confirm">Confirmar contrasena</label>
						<input
							type="password"
							id="password_confirm"
							name="password_confirm"
							required
						/>
					</div>

					<label class="auth-checkbox">
						<input type="checkbox" required name="acepta_privacidad" />
						Acepto la <a href="/privacidad">Politica de Privacidad</a>
					</label>

					<button type="submit" class="auth-boton">Registrarse</button>
				</form>

				<p class="auth-pie">Ya tenes cuenta? <a href="/login">Inicia sesion</a></p>
			</div>
		</main>
	</body>
</html>
