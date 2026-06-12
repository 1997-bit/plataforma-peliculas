<?php
declare(strict_types=1);

namespace App\Services;

class Response
{
  public function redirect(string $url): never
  {
    header('Location: ' . $url);
    exit;
  }

  public function render(string $view, array $data = []): void
  {
    extract($data, EXTR_SKIP);
    require ROOT . '/views/' . $view;
  }

  public function abort(int $code, string $view): never
  {
    http_response_code($code);
    require ROOT . '/views/' . $view;
    exit;
  }
}
