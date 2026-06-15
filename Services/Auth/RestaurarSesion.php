<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\TokenRepo;
use App\Models\UserRepo;
use App\Services\SessionManager;
use App\Services\CookieManejador;
use App\Services\CryptoServicio;
use App\Helpers\UuidHelper;

class RestaurarSesion
{
    private const NOMBRE_COOKIE = 'remember_token';

    public function __construct(
        private TokenRepo $tokens,
        private UserRepo $usuarios,
        private SessionManager $session,
        private CookieManejador $cookie,
        private CryptoServicio  $crypto,
    ) {
    }

    private function fallar(): bool
    {
        $this->cookie->eliminar(self::NOMBRE_COOKIE);
        return false;
    }

    public function restaurarSesion(): bool
    {
        if (!$this->cookie->existe(self::NOMBRE_COOKIE)) {
            return false;
        }

        try {
            $token = $this->crypto->descifrar($this->cookie->obtener(self::NOMBRE_COOKIE));
        } catch (\Exception $e) {
            return $this->fallar();
        }

        $fila = $this->tokens->buscarPorToken($token);
        if (!$fila) {
            return $this->fallar();
        }

        $user = $this->usuarios->buscarPorId(UuidHelper::binarioAUuid($fila['user_id']));
        if (!$user || !$user->puedeLogin()) {
            return $this->fallar();
        }

        $this->session->regenerar();
        $this->session->establecer('user_id', $user->id);
        $this->session->establecer('user_role', $user->role);
        $this->session->establecer('username', $user->username);

        $this->tokens->rotar(hash('sha256', $token), $user->id);
        return true;
    }
}
