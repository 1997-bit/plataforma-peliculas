<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Session;
use App\Services\Auth\RegistrarUsuario;
use voku\helper\AntiXSS;

class RegistroController
{
  public function __construct(
    private RegistrarUsuario $registrarUsuario,
  ) {}

  public function mostrarFormulario(): void
  {
    if (Session::existe('user_id')) {
      $this->redirigir('/home');
    }

    $csrf = Session::generarCsrf();
    require ROOT . '/views/auth/register.php';
  }

  public function procesarRegistro(): void
  {
    if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
      $this->redirigir('/register');
      return;
    }

    $antixss  = new AntiXSS();
    $email = $antixss->xss_clean(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['password_confirm'] ?? '');
    $username = $antixss->xss_clean(trim($_POST['username'] ?? ''));
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $resultado = $this->registrarUsuario->registrar($email, $password, $confirm, $username, $ip);

    if (!$resultado->success) {
      $error = $resultado->errorMsg;
      $csrf = Session::generarCsrf();
      require ROOT . '/views/auth/register.php';
      return;
    }

    $this->redirigir($resultado->redirectUrl);
  }

  private function redirigir(string $ruta): void
  {
    header('Location: ' . $ruta);
    exit;
  }
}
