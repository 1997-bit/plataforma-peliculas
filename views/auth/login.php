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
        <p class="auth-error">
          <svg width="18" height="18" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true" style="flex-shrink: 0;">
            <path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm-8,56a8,8,0,0,1,16,0v56a8,8,0,0,1-16,0Zm8,104a12,12,0,1,1,12-12A12,12,0,0,1,128,184Z"></path>
          </svg>
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
        <?php endif; ?>

        <form method="POST" action="/login" class="auth-form">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

          <div class="auth-campo">
            <label for="email">Email</label>
            <div class="auth-campo-con-icono tiene-icono-izq">
              <svg class="auth-icono-izq" width="18" height="18" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true">
                <path d="M224,48H32a8,8,0,0,0-8,8V192a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A8,8,0,0,0,224,48Zm-8,144H40V74.19l82.59,75.71a8,8,0,0,0,10.82,0L216,74.19V192Z"></path>
              </svg>
              <input type="email" id="email" name="email" required autocomplete="email">
            </div>
          </div>

          <div class="auth-campo">
            <label for="password">Contraseña</label>
            <div class="auth-campo-con-icono tiene-icono-izq tiene-icono-der">
              <svg class="auth-icono-izq" width="18" height="18" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true">
                <path d="M208,80H176V56a48,48,0,0,0-96,0V80H48A16,16,0,0,0,32,96V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V96A16,16,0,0,0,208,80Zm-80,84a12,12,0,1,1,12-12A12,12,0,0,1,128,164Zm32-84H96V56a32,32,0,0,1,64,0Z"></path>
              </svg>
              <input type="password" id="password" name="password" required autocomplete="current-password">
              <button type="button" class="auth-toggle-ojo" aria-label="Mostrar u ocultar contraseña">
                <svg class="icono-ojo-abierto" width="18" height="18" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true">
                  <path d="M247.31,124.76c-.35-.79-8.82-19.58-27.65-38.41C194.57,61.26,162.88,48,128,48S61.43,61.26,36.34,86.35C17.51,105.18,9,124,8.69,124.76a8,8,0,0,0,0,6.5c.35.79,8.82,19.57,27.65,38.4C61.43,194.74,93.12,208,128,208s66.57-13.26,91.66-38.34c18.83-18.83,27.3-37.61,27.65-38.4A8,8,0,0,0,247.31,124.76ZM128,168a40,40,0,1,1,40-40A40,40,0,0,1,128,168Z"></path>
                </svg>
                <svg class="icono-ojo-cerrado" width="18" height="18" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true" style="display: none;">
                  <path d="M96.68,57.87a4,4,0,0,1,2.08-6.6A130.13,130.13,0,0,1,128,48c34.88,0,66.57,13.26,91.66,38.35,18.83,18.83,27.3,37.62,27.65,38.41a8,8,0,0,1,0,6.5c-.35.79-8.82,19.57-27.65,38.4q-4.28,4.26-8.79,8.07a4,4,0,0,1-5.55-.36ZM213.92,210.62a8,8,0,1,1-11.84,10.76L180,197.13A127.21,127.21,0,0,1,128,208c-34.88,0-66.57-13.26-91.66-38.34C17.51,150.83,9,132.05,8.69,131.26a8,8,0,0,1,0-6.5C9,124,17.51,105.18,36.34,86.35a135,135,0,0,1,25-19.78L42.08,45.38A8,8,0,1,1,53.92,34.62Zm-65.49-48.25-52.69-58a40,40,0,0,0,52.69,58Z"></path>
                </svg>
              </button>
            </div>
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

    <script src="/assets/js/toggle-password.js" defer></script>
  </body>
</html>
