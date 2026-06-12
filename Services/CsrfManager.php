<?php
declare(strict_types=1);

namespace App\Services;

class CsrfManager
{
  public function generar(): string
  {
    $token = bin2hex(random_bytes(32));
    $_SESSION['_csrf_token'] = $token;
    return $token;
  }

  public function validar(string $token): bool
  {
    $guardado = $_SESSION['_csrf_token'] ?? '';
    return hash_equals($guardado, $token);
  }
}
