<?php
declare(strict_types=1);

namespace App\Services;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

class CryptoHelper
{
  private static ?Key $clave = null;

  private static function obtenerClave(): Key
  {
    if (self::$clave === null) {
      self::$clave = Key::loadFromAsciiSafeString($_ENV['APP_KEY']);
    }
    return self::$clave;
  }

  public static function cifrar(string $dato): string
  {
    return Crypto::encrypt($dato, self::obtenerClave());
  }

  public static function descifrar(string $dato): string
  {
    return Crypto::decrypt($dato, self::obtenerClave());
  }
}

