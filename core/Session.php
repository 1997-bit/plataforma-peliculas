<?php
declare(strict_types=1);

namespace App\Core;

class Session
{
  private static bool $started = false;

  public static function iniciar(): void
  {
    if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
      self::$started = true;
      return;
    }

    $lifetime = (int)($_ENV['SESSION_LIFETIME'] ?? 3600);
    $isSeguro = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (($_SERVER['SERVER_PORT'] ?? null) == 443);

    session_set_cookie_params([
      'lifetime' => 0,
      'path' => '/',
      'domain' => '',
      'secure' => $isSeguro,
      'httponly' => true,
      'samesite' => 'Strict',
    ]);

    session_name('CINESESSID');
    session_start();
    self::$started = true;

    if (isset($_SESSION['_last_activity'])) {
      if (time() - $_SESSION['_last_activity'] > $lifetime) {
        self::destruir();
        return;
      }
    }

    $_SESSION['_last_activity'] = time();
  }

  public static function regenerar(): void
  {
    session_regenerate_id(true);
  }

  public static function destruir(): void
  {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 3600, $params['path']);
    }
    session_destroy();
    self::$started = false;
  }

  public static function establecer(string $clave, mixed $valor): void
  {
    $_SESSION[$clave] = $valor;
  }

  public static function obtener(string $clave, mixed $defecto = null): mixed
  {
    return $_SESSION[$clave] ?? $defecto;
  }

  public static function existe(string $clave): bool
  {
    return isset($_SESSION[$clave]);
  }

  public static function eliminar(string $clave): void
  {
    unset($_SESSION[$clave]);
  }

  public static function generarCsrf(): string
  {
    $token = bin2hex(random_bytes(32));
    $_SESSION['_csrf_token'] = $token;
    return $token;
  }

  public static function validarCsrf(string $token): bool
  {
        $guardado = $_SESSION['_csrf_token'] ?? '';
        return hash_equals($guardado, $token);
    }
}
