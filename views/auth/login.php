<?php
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
  </head>
  <body>
    <h1>Iniciar sesion</h1>

    <?php if ($error): ?>
    <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="POST" action="/login">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

      <label for="email">Email</label>
      <input type="email" id="email" name="email" required autocomplete="email">

      <label for="password">Contraseña</label>
      <input type="password" id="password" name="password" required autocomplete="current-password">
      <label>
        <input type="checkbox" name="remember" value="1">
        Recordarme
      </label>
      <button type="submit">Entrar</button>
    </form>
  </body>
</html>
