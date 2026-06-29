<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    private static bool $started = false;

    public static function iniciar(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        self::configurarCookie();

        session_name('CINESESSID');
        session_start();
        self::$started = true;

        if (self::haExpirado()) {
            self::destruir();
            return;
        }

        $_SESSION['_last_activity'] = time();
    }

    public static function regenerar(): void
    {
        session_regenerate_id(true);
    }

    public static function destruir(): void
    {
        $_SESSION = [];
        self::eliminarCookieSesion();
        session_destroy();
        self::$started = false;
    }

    public static function establecer(string $clave, mixed $valor): void
    {
        $_SESSION[$clave] = $valor;
    }

    public static function obtener(string $clave, mixed $defecto = null): mixed
    {
        return $_SESSION[$clave] ?? $defecto;
    }

    public static function existe(string $clave): bool
    {
        return isset($_SESSION[$clave]);
    }

    public static function eliminar(string $clave): void
    {
        unset($_SESSION[$clave]);
    }

    public static function generarCsrf(): string
    {
        return (new \App\Services\ManejadorCsrf())->generarTokenCsrf();
    }

    public static function validarCsrf(string $token): bool
    {
        return (new \App\Services\ManejadorCsrf())->validarTokenCsrf($token);
    }

    private static function configurarCookie(): void
    {
        $esSeguro = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? null) == 443);

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $esSeguro,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    private static function haExpirado(): bool
    {
        $lifetime = (int) ($_ENV['SESSION_LIFETIME'] ?? 3600);
        $ultimaActividad = $_SESSION['_last_activity'] ?? null;

        return is_int($ultimaActividad) && (time() - $ultimaActividad > $lifetime);
    }

    private static function eliminarCookieSesion(): void
    {
        if (!ini_get('session.use_cookies')) {
            return;
        }

        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $params['path']);
    }
}
