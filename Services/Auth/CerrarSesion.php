<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\TokenRepo;
use App\Models\IntentosLoginRepo;
use App\Core\Session;
use App\Services\CookieManejador;
use App\Services\CryptoServicio;

class CerrarSesion
{
    public function __construct(
        private TokenRepo $tokens,
        private IntentosLoginRepo $intentos,
    ) {
    }

    public function cerrarSesion(string $ip, ?string $idUsuario): void
    {
        // 1. Log
        $this->intentos->registrarEvento('LOGOUT', $ip, $idUsuario, []);

        // 2. Limpiar remember token si existe
        if (CookieManejador::existe('remember_token')) {
            try {
                $tokenCifrado = CookieManejador::obtener('remember_token');
                $token = (new CryptoServicio())->descifrar($tokenCifrado);
                $this->tokens->eliminar($token);
            } catch (\Exception $e) {
                CookieManejador::eliminar('remember_token');
            }
        }

        // 3. Destruir sesión
        Session::destruir();
    }
}
