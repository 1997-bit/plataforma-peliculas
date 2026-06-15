<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserRepo;
use App\Models\IntentosLoginRepo;
use App\Services\SessionManager;

class RegistrarUsuario
{
  public function __construct(
    private UserRepo $usuarios,
    private IntentosLoginRepo $intentos,
    private SessionManager $session,
  ) {}

  public function registrar(string $email, string $password, string $confirm, string $username, string $ip): ResultadoRegistro
  {
    $errores = $this->validarDatos($email, $password, $confirm, $username);
    if (!empty($errores)) {
      return new ResultadoRegistro(success: false, errorMsg: implode(' ', $errores));
    }

    if ($this->usuarios->correoExiste($email)) {
      return new ResultadoRegistro(success: false, errorMsg: 'El correo ya está registrado.');
    }

    $user = new User(
      id: '',
      email: $email,
      username: $username,
      role: 'user',
      isActive: true,
      passwordHash: password_hash($password, PASSWORD_ARGON2ID),
    );

    $uuid = $this->usuarios->guardar($user);

    $this->intentos->registrarEvento('REGISTRO', $ip, $uuid, ['email' => $email]);

    $this->session->regenerar();
    $this->session->establecer('user_id', $uuid);
    $this->session->establecer('user_role', 'user');
    $this->session->establecer('username', $username);

    return new ResultadoRegistro(success: true, redirectUrl: '/home');
  }

  /** @return array<int, string> */
  private function validarDatos(string $email, string $password, string $confirm, string $username): array
  {
    $errores = [];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errores[] = 'Email inválido.';
    }
    if (strlen($password) < 8) {
      $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if ($password !== $confirm) {
      $errores[] = 'Las contraseñas no coinciden.';
    }
    if (strlen($username) < 2 || strlen($username) > 50) {
      $errores[] = 'El nombre debe tener entre 2 y 50 caracteres.';
    }

    return $errores;
  }
}
