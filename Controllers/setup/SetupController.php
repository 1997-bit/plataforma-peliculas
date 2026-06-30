<?php

declare(strict_types=1);

namespace App\Controllers\Setup;

use App\Core\Session;
use App\Models\User;
use App\Models\UserRepo;

/**
 * Crea el primer usuario admin desde el navegador, sin tocar SQL a
 * mano (correo/username van cifrados con APP_KEY y correo_hash es
 * HMAC, asi que no se pueden escribir en un INSERT plano).
 *
 * Guard: en cuanto existe CUALQUIER admin, mostrar()/crear() devuelven
 * 403 directo.
 */
final class SetupController
{
    public function __construct(private UserRepo $usuarios)
    {
    }

    public function mostrar(): void
    {
        if ($this->bloquearSiYaHayAdmin()) {
            return;
        }

        $csrf = Session::generarCsrf();
        require ROOT . '/views/setup/admin.php';
    }

    public function crear(): void
    {
        if ($this->bloquearSiYaHayAdmin()) {
            return;
        }

        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            require ROOT . '/views/errors/403.php';
            return;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $csrf = Session::generarCsrf();

        $error = $this->validarDatos($email, $username, $password);
        if ($error !== null) {
            require ROOT . '/views/setup/admin.php';
            return;
        }

        $usuario = new User(
            id: '',
            email: $email,
            username: $username,
            role: 'user',
            isActive: true,
            passwordHash: password_hash($password, PASSWORD_ARGON2ID),
        );

        $uuid = $this->usuarios->guardar($usuario);
        $this->usuarios->asignarRol($uuid, 'admin');

        $creado = ['email' => $email, 'password' => $password];
        require ROOT . '/views/setup/admin.php';
    }

    /** Junta las validaciones de crear() en un solo lugar. Devuelve el primer error, o null si todo esta bien. */
    private function validarDatos(string $email, string $username, string $password): ?string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Email inválido.';
        }
        if (strlen($username) < 2 || strlen($username) > 50) {
            return 'El nombre debe tener entre 2 y 50 caracteres.';
        }
        if (strlen($password) < 8) {
            return 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($this->usuarios->correoExiste($email)) {
            return 'Ese correo ya está registrado.';
        }

        return null;
    }

    /** true + responde 403 si ya existe un admin (mostrar()/crear() deben cortar ahi). */
    private function bloquearSiYaHayAdmin(): bool
    {
        if (!$this->usuarios->existeAlgunAdmin()) {
            return false;
        }

        http_response_code(403);
        require ROOT . '/views/errors/403.php';
        return true;
    }
}
