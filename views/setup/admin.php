<?php
$error = $error ?? null;
$creado = $creado ?? null;
?>
<!DOCTYPE html>
<html lang="es" data-tema="dark">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup admin</title>
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
  </head>
  <body>
    <main class="auth-pagina">
      <div class="auth-card">
        <h1 class="auth-titulo">Crear admin inicial</h1>
        <p class="auth-subtitulo">Solo funciona si todavia no existe ningun admin.</p>

        <?php if ($creado): ?>
        <p class="auth-error" style="background:#123; color:#8f8;">
          Admin creado. Email: <?= htmlspecialchars($creado['email'], ENT_QUOTES, 'UTF-8') ?>
          Password: <?= htmlspecialchars($creado['password'], ENT_QUOTES, 'UTF-8') ?>
          <br><a href="/login">Ir a login</a>
        </p>
        <?php else: ?>

        <?php if ($error): ?>
        <p class="auth-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="POST" action="/setup/admin" class="auth-form">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

          <div class="auth-campo">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autocomplete="email">
          </div>

          <div class="auth-campo">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required minlength="2" maxlength="50">
          </div>

          <div class="auth-campo">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
          </div>

          <button type="submit" class="auth-boton">Crear admin</button>
        </form>

        <?php endif; ?>
      </div>
    </main>
  </body>
</html>
