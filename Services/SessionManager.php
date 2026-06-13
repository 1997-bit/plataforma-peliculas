<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;

class SessionManager
{
    public function regenerar(): void { Session::regenerar(); }
    public function establecer(string $clave, mixed $valor): void { Session::establecer($clave, $valor); }
    public function obtener(string $clave, mixed $defecto = null): mixed { return Session::obtener($clave, $defecto); }
    public function existe(string $clave): bool { return Session::existe($clave); }
    public function destruir(): void { Session::destruir(); }
}
