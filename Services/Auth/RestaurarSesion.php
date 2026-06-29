<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\TokenRepo;
use App\Models\UserRepo;
use App\Core\Session;
use App\Services\CookieManejador;
use App\Services\CryptoServicio;
use App\Helpers\UuidHelper;

class RestaurarSesion
{
    private const NOMBRE_COOKIE = 'remember_token';

    public function __construct(
        private TokenRepo $tokens,
        private UserRepo $usuarios,
        private CryptoServicio $crypto,
    ) {
    }

    private function fallar(): bool
    {
        CookieManejador::eliminar(self::NOMBRE_COOKIE);
        return false;
    }

    public function restaurarSesion(): bool
    {
        if (!CookieManejador::existe(self::NOMBRE_COOKIE)) {
            return false;
        }

        try {
            $token = $this->crypto->descifrar(CookieManejador::obtener(self::NOMBRE_COOKIE));
        } catch (\Exception $e) {
            return $this->fallar();
        }

        $user = $this->buscarUsuarioPorToken($token);
        if ($user === null) {
            return $this->fallar();
        }

        if (!$user->puedeIniciarSesion()) {
            return $this->fallar();
        }

        $this->iniciarSesion($user);
        $this->tokens->rotar(hash('sha256', $token), $user->id);
        return true;
    }

    private function buscarUsuarioPorToken(string $token): ?\App\Models\User
    {
        $fila = $this->tokens->buscarPorToken($token);
        if (!$fila) {
            return null;
        }

        return $this->usuarios->buscarPorId(UuidHelper::binarioAUuid($fila['user_id']));
    }

    private function iniciarSesion(\App\Models\User $user): void
    {
        Session::regenerar();
        Session::establecer('user_id', $user->id);
        Session::establecer('user_role', $user->role);
        Session::establecer('username', $user->username);
    }
}
