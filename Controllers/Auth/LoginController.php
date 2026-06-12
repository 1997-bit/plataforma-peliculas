<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Session;
use App\Helpers\SecurityLogger;
use App\Models\User;
use App\Services\RememberMeService;
use voku\helper\AntiXSS;

/**
 * Maneja el inicio y cierre de sesion.
 *
 * Seguridad aplicada:
 * - CSRF en cada submit (OWASP ASVS V4.2)
 * - Rate limiting por IP antes de tocar la BD (OWASP API4:2023)
 * - Mensaje de error generico, no revela si el email existe (CWE-200)
 * - Regeneracion de ID de sesion post-login (OWASP ASVS V3.3)
 * - Rol guardado en sesion server-side, nunca en cookie (ISO 27001 A.9.4.2)
 */
class LoginController
{
  private const MAX_INTENTOS      = 5;
  private const SEGUNDOS_BLOQUEO  = 900;

  /**
   * Muestra el formulario de inicio de sesion.
   * Si ya hay sesion activa, redirige directo al home.
   */
  public function mostrarFormulario(): void
  {
    if (Session::existe('user_id')) {
      $this->redirigir('/home');
    }

    $csrf = Session::generarCsrf();
    require ROOT . '/views/auth/login.php';
  }

  /**
   * Procesa el submit del formulario de login.
   * Orden: CSRF -> rate limit -> validacion -> buscar usuario -> verificar -> sesion.
   */
  public function procesarLogin(): void
  {
    $ip      = $this->obtenerIp();
    $antixss = new AntiXSS();

    $tokenCsrf = $_POST['_csrf'] ?? '';
    if (!Session::validarCsrf($tokenCsrf)) {
      $this->redirigir('/login');
      return;
    }

    $intentos = SecurityLogger::intentosFallidosRecientes($ip, self::SEGUNDOS_BLOQUEO);
    if ($intentos >= self::MAX_INTENTOS) {
      http_response_code(429);
      header('Retry-After: ' . self::SEGUNDOS_BLOQUEO);
      $error = 'Demasiados intentos. Espera 15 minutos.';
      $csrf  = Session::generarCsrf();
      require ROOT . '/views/auth/login.php';
      return;
    }

    $email    = $antixss->xss_clean(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
      $error = 'Credenciales invalidas.';
      $csrf  = Session::generarCsrf();
      require ROOT . '/views/auth/login.php';
      return;
    }

    $usuario = User::buscarPorEmail($email);

    // Siempre llamamos verificarPassword para tiempo constante (CWE-208).
    // Si el usuario no existe usamos DUMMY_HASH para que los tiempos sean identicos.
    $hashVerificar   = $usuario ? $usuario['password_hash'] : User::DUMMY_HASH;
    $passwordCorrecta = User::verificarPassword($password, $hashVerificar);
    $credencialesValidas = $usuario !== null
      && (int)$usuario['is_active'] === 1
      && $passwordCorrecta;

    SecurityLogger::registrarIntento($ip, $email, $credencialesValidas);

    if (!$credencialesValidas) {
      SecurityLogger::registrarEvento('LOGIN_FALLIDO', $ip, null, ['email' => $email]);
      $error = 'Credenciales invalidas.';
      $csrf  = Session::generarCsrf();
      require ROOT . '/views/auth/login.php';
      return;
    }

    // Regeneramos el ID de sesion para prevenir session fixation (OWASP ASVS V3.3)
    Session::regenerar();
    Session::establecer('user_id',   $usuario['id']);
    Session::establecer('user_role', $usuario['role']);
    Session::establecer('username',  $usuario['username']);

    if (isset($_POST['remember']) && $_POST['remember'] === '1') {
      RememberMeService::crear($usuario['id']);
    }

    SecurityLogger::registrarEvento('LOGIN_EXITOSO', $ip, $usuario['id']);

    if ($usuario['role'] === 'admin') {
      $this->redirigir('/admin');
    } else {
      $this->redirigir('/home');
    }
  }

  /**
   * Cierra la sesion, borra el token de recordarme si existe,
   * y redirige al landing.
   */
  public function cerrarSesion(): void
  {
    $ip      = $this->obtenerIp();
    $userId  = Session::obtener('user_id');

    SecurityLogger::registrarEvento('LOGOUT', $ip, $userId);
    RememberMeService::limpiar();
    Session::destruir();

    $this->redirigir('/');
  }

  private function obtenerIp(): string
  {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  }

  private function redirigir(string $ruta): void
  {
    header('Location: ' . $ruta);
    exit;
  }
}
