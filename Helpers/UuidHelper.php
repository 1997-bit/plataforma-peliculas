<?php
declare(strict_types=1);

namespace App\Helpers;

class UuidHelper
{
  public static function uuidABinario(string $uuid): string
  {
    $hex = str_replace('-', '', $uuid);

    if (!ctype_xdigit($hex) || strlen($hex) !== 32) {
      throw new \InvalidArgumentException("UUID invalido: $uuid");
    }

    return hex2bin($hex);
  }

  public static function binarioAUuid(string $bin): string
  {
    $hex = bin2hex($bin);
    return sprintf('%s-%s-%s-%s-%s',
      substr($hex, 0, 8),
      substr($hex, 8, 4),
      substr($hex, 12, 4),
      substr($hex, 16, 4),
      substr($hex, 20)
    );
  }
}
