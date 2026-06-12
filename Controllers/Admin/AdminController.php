<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Middleware\AuthMiddleware;

/**
 * Maneja el panel de administracion.
 * Solo accesible con rol admin.
 */
class AdminController
{
  public function mostrarPanel(): void
  {
    AuthMiddleware::requerirRol('admin');
    require ROOT . '/views/admin/index.php';
  }

  public function mostrarFormularioAgregar(): void
  {
    AuthMiddleware::requerirRol('admin');
    require ROOT . '/views/admin/agregar.php';
  }

  public function mostrarEstadisticas(): void
  {
    AuthMiddleware::requerirRol('admin');
    require ROOT . '/views/admin/estadisticas.php';
  }
}
