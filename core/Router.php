<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
  private array $rutas = [];

  public function registrarGet(string $ruta, callable|array $manejador): void
  {
    $this->rutas['GET'][$ruta] = $manejador;
  }

  public function registrarPost(string $ruta, callable|array $manejador): void
  {
    $this->rutas['POST'][$ruta] = $manejador;
  }

  public function despachar(): void
  {
    $metodo = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = rtrim($uri, '/') ?: '/';

    $manejador = $this->rutas[$metodo][$uri] ?? null;

    if ($manejador === null) {
      http_response_code(404);
      require ROOT . '/views/errors/404.php';
      return;
    }

    if (is_array($manejador)) {
      [$clase, $accion] = $manejador;
      (new $clase())->$accion();
    } else {
      $manejador();
    }
  }
}
