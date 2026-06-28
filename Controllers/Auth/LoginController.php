<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Session;
use App\Services\Auth\CerrarSesion;
use App\Services\Auth\ProcesarLogin;
use voku\helper\AntiXSS;

class LoginController
{
  public function __construct(
    private ProcesarLogin $procesarLogin,
    private CerrarSesion $cerrarSesion,
  ) {}

  public function mostrarFormulario(): void
  {
    if (Session::existe('user_id')) {
      $this->redirigir(Session::obtener('user_role') === 'admin' ? '/admin' : '/home');
      return;
    }

    $csrf = Session::generarCsrf();
    require ROOT . '/views/auth/login.php';
  }

  public function login(): void
  {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
      $this->redirigir('/login');
      return;
    }

    $antixss = new AntiXSS();
    $email = $antixss->xss_clean(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']) && $_POST['remember'] === '1';

    $resultado = $this->procesarLogin->procesar($email, $password, $ip, $remember);

    if (!$resultado->success) {
      $error = $resultado->errorMsg;
      $csrf  = Session::generarCsrf();
      if ($resultado->rateLimited) http_response_code(429);
      require ROOT . '/views/auth/login.php';
      return;
    }

    $this->redirigir($resultado->redirectUrl);
  }

  public function logout(): void
  {
    if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
      $this->redirigir('/');
      return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userId = Session::obtener('user_id');
    $this->cerrarSesion->cerrarSesion($ip, $userId);
    $this->redirigir('/');
  }

  private function redirigir(string $ruta): void
  {
    header('Location: ' . $ruta);
    exit;
  }
}
