<?php
declare(strict_types=1);

namespace App\Services;

class ManejadorCsrf
{
  public function generarTokenCsrf(): string
  {
    $token = bin2hex(random_bytes(32));
    $_SESSION['_csrf_token'] = $token;
    return $token;
  }

  public function validarTokenCsrf(string $token): bool
  {
    $guardado = (string)($_SESSION['_csrf_token'] ?? '');
    return hash_equals($guardado, $token);
  }
}
