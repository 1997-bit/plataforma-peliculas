<?php
declare(strict_types=1);

namespace App\Services;

class CookieManejador
{
  /** @return array<string, mixed> */
  private static function opcionesBase(int $expira): array
  {
    return [
      'expires' => $expira,
      'path' => '/',
      'domain' => '',
      'secure' => true,
      'httponly' => true,
      'samesite' => 'Strict',
    ];
  }

  public static function establecer(string $nombre, string $valor, int $diasVida = 30): void
  {
    $expira = time() + ($diasVida * 24 * 3600);
    setcookie($nombre, $valor, self::opcionesBase($expira));
  }

  public static function obtener(string $nombre, string $defecto = ''): string
  {
    return (string)($_COOKIE[$nombre] ?? $defecto);
  }

  public static function existe(string $nombre): bool
  {
    return isset($_COOKIE[$nombre]);
  }

  public static function eliminar(string $nombre): void
  {
    setcookie($nombre, '', self::opcionesBase(time() - 3600));
    unset($_COOKIE[$nombre]);
  }
}
