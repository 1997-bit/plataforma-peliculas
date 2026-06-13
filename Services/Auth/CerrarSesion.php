<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\TokenRepo;
use App\Models\IntentosLoginRepo;
use App\Services\SessionManager;
use App\Services\CookieManejador;
use App\Services\CryptoServicio;

class CerrarSesion
{
  public function __construct(
    private SessionManager $session,
    private TokenRepo $tokens,
    private CookieManejador $cookie,
    private IntentosLoginRepo $intentos,
  ) {}

  public function cerrarSesion(string $ip, ?string $userId): void
  {
    // 1. Log
    $this->intentos->registrarEvento('LOGOUT', $ip, $userId, []);

    // 2. Limpiar remember token si existe
    if ($this->cookie->existe('remember_token')) {
      try {
        $tokenCifrado = $this->cookie->obtener('remember_token');
        $token = (new CryptoServicio())->descifrar($tokenCifrado);
        $this->tokens->eliminar($token);
      } catch (\Exception $e) {
        $this->cookie->eliminar('remember_token');
      }
    }

    // 3. Destruir sesión
    $this->session->destruir();
  }
}
