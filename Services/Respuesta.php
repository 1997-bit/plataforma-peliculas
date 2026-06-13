<?php
declare(strict_types=1);

namespace App\Services;

class Respuesta
{
  public function redirigir(string $url): never
  {
    header('Location: ' . $url);
    exit;
  }

  public function mostrarVista(string $view, array $data = []): void
  {
    extract($data, EXTR_SKIP);
    require ROOT . '/views/' . $view;
  }

  public function abortar(int $code, string $view): never
  {
    http_response_code($code);
    require ROOT . '/views/' . $view;
    exit;
  }
}
