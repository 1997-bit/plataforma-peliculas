<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

class AuthMiddleware
{
    public static function verificarAutenticacion(): void
    {
        if (!Session::existe('user_id')) {
            header('Location: /login');
            exit;
        }
    }

    public static function requerirRol(string $rol): void
    {
        self::verificarAutenticacion();

        if (Session::obtener('user_role') !== $rol) {
            http_response_code(403);
            require ROOT . '/views/errors/403.php';
            exit;
        }
    }

    /**
     * Bloquea rutas landing, login y register si ya hay
     * sesion activa. Redirige segun el rol para que no pueda volver
     * al landing ni con el nav ni escribiendo la URL.
     */
    public static function redirigirSiAutenticado(): void
    {
        if (Session::existe('user_id')) {
            $destino = Session::obtener('user_role') === 'admin' ? '/admin' : '/home';
            header('Location: ' . $destino);
            exit;
        }
    }

    /**
     * Bloquea el acceso de un rol especifico a una ruta.
     * Ej: un admin no puede entrar a las rutas de usuario normal.
     */
    public static function bloquearRol(string $rolBloqueado): void
    {
        self::verificarAutenticacion();

        if (Session::obtener('user_role') === $rolBloqueado) {
            $destino = $rolBloqueado === 'admin' ? '/home' : '/admin';
            header('Location: ' . $destino);
            exit;
        }
    }
}
