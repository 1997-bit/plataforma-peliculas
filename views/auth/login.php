<?php
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="es" data-tema="dark">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
  </head>
  <body>
    <main class="auth-pagina">
      <div class="auth-card">
        <h1 class="auth-titulo">Iniciar sesion</h1>
        <p class="auth-subtitulo">Que ver, resuelto.</p>

        <?php if ($error): ?>
        <p class="auth-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="POST" action="/login" class="auth-form">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

          <div class="auth-campo">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autocomplete="email">
          </div>

          <div class="auth-campo">
            <label for="password">Contrasena</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
          </div>

          <label class="auth-checkbox">
            <input type="checkbox" name="remember" value="1">
            Recordarme
          </label>

          <button type="submit" class="auth-boton">Entrar</button>
        </form>

        <p class="auth-pie">No tenes cuenta? <a href="/register">Registrate</a></p>
      </div>
    </main>
  </body>
</html>
