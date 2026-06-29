<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserRepo;
use App\Models\IntentosLoginRepo;
use App\Models\TokenRepo;
use App\Core\Session;

class ProcesarLogin
{
    private const MAX_INTENTOS = 4;
    private const SEGUNDOS_BLOQUEO = 900;

    public function __construct(
        private UserRepo $usuarios,
        private IntentosLoginRepo $intentos,
        private TokenRepo $tokens,
    ) {
    }

    public function procesar(string $email, string $password, string $ip, bool $remember): ResultadoLogin
    {
        if ($this->estaBloqueado($ip)) {
            return new ResultadoLogin(success: false, errorMsg: 'Demasiados intentos. Espera 15 minutos.', rateLimited: true);
        }

        $user = $this->usuarios->buscarPorCorreo($email);
        $credencialesValidas = $this->credencialesValidas($user, $password);
        $this->intentos->registrarIntento($ip, $email, $credencialesValidas);

        if (!$credencialesValidas) {
            $this->intentos->registrarEvento('LOGIN_FALLIDO', $ip, null, ['email' => $email]);
            return new ResultadoLogin(success: false, errorMsg: 'Credenciales inválidas.');
        }

        Session::regenerar();
        Session::establecer('user_id', $user->id);
        Session::establecer('user_role', $user->role);
        Session::establecer('username', $user->username);

        if ($remember) {
            $this->tokens->crear($user->id);
        }

        $this->intentos->registrarEvento('LOGIN_EXITOSO', $ip, $user->id, []);
        return new ResultadoLogin(success: true, redirectUrl: $this->rutaDestino($user));
    }

    private function estaBloqueado(string $ip): bool
    {
        return $this->intentos->contarFallidosRecientes($ip, self::SEGUNDOS_BLOQUEO) >= self::MAX_INTENTOS;
    }

    private function credencialesValidas(?User $user, string $password): bool
    {
        $hash = $user?->passwordHash ?? User::DUMMY_HASH;

        return $user !== null
            && $user->puedeIniciarSesion()
            && password_verify($password, $hash);
    }

    private function rutaDestino(User $user): string
    {
        return $user->esAdmin() ? '/admin' : '/home';
    }
}
