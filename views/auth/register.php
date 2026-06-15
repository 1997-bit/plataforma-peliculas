<?php $error = $error ?? null; ?>
<!doctype html>
<html lang="es">
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<title>Registro</title>
	</head>
	<body>
		<h1>Crear cuenta</h1>

		<?php if ($error): ?>
		<p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
		<?php endif; ?>

		<form method="POST" action="/register">
			<input
				type="hidden"
				name="_csrf"
				value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"
			/>

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

			<label for="email">Email</label>
			<input
				type="email"
				id="email"
				name="email"
				required
				autocomplete="email"
			/>

			<label for="password">Contraseña</label>
			<input
				type="password"
				id="password"
				name="password"
				autocomplete="new-password"
				required
			/>

			<label for="password_confirm">Confirmar contraseña</label>
			<input
				type="password"
				id="password_confirm"
				name="password_confirm"
				required
			/>
			
			<input type="checkbox" required name="acepta_privacidad" required>
			<button type="submit">Registrarse</button>

			<label
				>Acepto la
				<a href="/privacidad">Política de Privacidad</a></label
			>
		</form>

		<a href="/login">Ya tengo cuenta</a>
	</body>
</html>