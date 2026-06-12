<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Session;
use App\Helpers\SecurityLogger;
use App\Models\User;
use voku\helper\AntiXSS;

/**
 * Maneja el registro de nuevos usuarios.
 *
 * Seguridad aplicada:
 * - CSRF en cada submit
 * - Sanitizacion XSS en todos los inputs
 * - Validacion server-side antes de tocar la BD
 * - Email y username se guardan cifrados en BD
 * - Password hasheada con Argon2id
 * - Regeneracion de sesion post-registro
 */
class RegistroController
{
  /**
   * Muestra el formulario de registro.
   * Si ya hay sesion activa, redirige al home.
   */
  public function mostrarFormulario(): void
  {
    if (Session::existe('user_id')) {
      $this->redirigir('/home');
    }

    $csrf = Session::generarCsrf();
    require ROOT . '/views/auth/register.php';
  }

  /**
   * Procesa el submit del formulario de registro.
   * Orden: CSRF -> validacion -> verificar email unico -> crear usuario -> sesion.
   */
  public function procesarRegistro(): void
  {
    $ip      = $this->obtenerIp();
    $antixss = new AntiXSS();

    $tokenCsrf = $_POST['_csrf'] ?? '';
    if (!Session::validarCsrf($tokenCsrf)) {
      $this->redirigir('/register');
      return;
    }

    $email                = $antixss->xss_clean(trim($_POST['email'] ?? ''));
    $password             = trim($_POST['password'] ?? '');
    $passwordConfirmacion = trim($_POST['password_confirm'] ?? '');
    $username             = $antixss->xss_clean(trim($_POST['username'] ?? ''));

    $errores = $this->validar($email, $password, $passwordConfirmacion, $username);
    if (!empty($errores)) {
      $error = implode(' ', $errores);
      $csrf  = Session::generarCsrf();
      require ROOT . '/views/auth/register.php';
      return;
    }

    if (User::emailExiste($email)) {
      $error = 'El correo ya esta registrado.';
      $csrf  = Session::generarCsrf();
      require ROOT . '/views/auth/register.php';
      return;
    }

    $uuid = User::crear($email, $password, $username);

    SecurityLogger::registrarEvento('REGISTRO', $ip, $uuid, ['email' => $email]);

    Session::regenerar();
    Session::establecer('user_id',   $uuid);
    Session::establecer('user_role', 'user');
    Session::establecer('username',  $username);

    $this->redirigir('/home');
  }

  /**
   * Valida los campos del formulario de registro.
   * Retorna array de errores, vacio si todo esta bien.
   */
  private function validar(string $email, string $password, string $confirmacion, string $username): array
  {
    $errores = [];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errores[] = 'Email invalido.';
    }

    if (strlen($password) < 8) {
      $errores[] = 'La contrasena debe tener al menos 8 caracteres.';
    }

    if ($password !== $confirmacion) {
      $errores[] = 'Las contrasenas no coinciden.';
    }

    if (strlen($username) < 2 || strlen($username) > 50) {
      $errores[] = 'El nombre debe tener entre 2 y 50 caracteres.';
    }

    return $errores;
  }

  private function obtenerIp(): string
  {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  }

  private function redirigir(string $ruta): void
  {
    header('Location: ' . $ruta);
    exit;
  }
}
