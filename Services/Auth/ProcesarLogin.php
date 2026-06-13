<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserRepo;
use App\Models\IntentosLoginRepo;
use App\Models\TokenRepo;
use App\Services\SessionManager;

class ProcesarLogin
{
  private const MAX_INTENTOS = 4;
  private const SEGUNDOS_BLOQUEO = 900;

  public function __construct(
    private UserRepo $usuarios,
    private IntentosLoginRepo $intentos,
    private SessionManager $session,
    private TokenRepo $tokens,
  ) {}

  public function procesar(string $email, string $password, string $ip, bool $remember): ResultadoLogin
  {
    // 1. Rate limit
    $intentos = $this->intentos->contarFallidosRecientes($ip, self::SEGUNDOS_BLOQUEO);
    if ($intentos >= self::MAX_INTENTOS) {
      return new ResultadoLogin(success: false, errorMsg: 'Demasiados intentos. Espera 15 minutos.', rateLimited: true);
    }

    // 2. Buscar usuario
    $user = $this->usuarios->buscarPorCorreo($email);

    // 3. Verificar password en tiempo constante (CWE-208)
    $hashVerificar = $user ? $user->passwordHash : User::DUMMY_HASH;
    $passwordOk = password_verify($password, $hashVerificar);
    $credencialesValidas = $user !== null && $user->puedeLogin() && $passwordOk;

    // 4. Registrar intento
    $this->intentos->registrarIntento($ip, $email, $credencialesValidas);

    if (!$credencialesValidas) {
      $this->intentos->registrarEvento('LOGIN_FALLIDO', $ip, null, ['email' => $email]);
      return new ResultadoLogin(success: false, errorMsg: 'Credenciales inválidas.');
    }

    // 5. Sesión
    $this->session->regenerar();
    $this->session->establecer('user_id', $user->id);
    $this->session->establecer('user_role', $user->role);
    $this->session->establecer('username', $user->username);

    // 6. Remember me
    if ($remember) {
      $this->tokens->crear($user->id);
    }

    // 7. Log + redirect
    $this->intentos->registrarEvento('LOGIN_EXITOSO', $ip, $user->id, []);
    $redirectUrl = $user->esAdmin() ? '/admin' : '/home';

    return new ResultadoLogin(success: true, redirectUrl: $redirectUrl);
  }
}

