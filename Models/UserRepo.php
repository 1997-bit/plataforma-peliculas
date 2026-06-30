<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\CryptoServicio;
use App\Helpers\UuidHelper;
use PDO;

class UserRepo
{
    public function __construct(
        private PDO $pdo,
        private CryptoServicio $crypto,
    ) {
    }

    public function buscarPorCorreo(string $email): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, correo, password_hash, nombre_usuario, rol, is_active, preferencias
             FROM usuarios WHERE correo_hash = :hash LIMIT 1'
        );
        $stmt->execute([':hash' => $this->hmacEmail($email)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        return $this->hidratar($row);
    }

    public function buscarPorId(string $id): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, correo, password_hash, nombre_usuario, rol, is_active, preferencias
             FROM usuarios WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => UuidHelper::uuidABinario($id)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        return $this->hidratar($row);
    }

    /**
     * Actualiza username (cifrado) y preferences (JSON nativo MySQL) de un usuario.
     *
     * @param array{generos?: list<int>, tema?: string} $preferences
     */
    public function actualizarPerfil(string $id, string $username, array $preferences): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuarios SET nombre_usuario = :nombre_usuario, preferencias = :preferencias WHERE id = :id'
        );
        $stmt->execute([
            ':id' => UuidHelper::uuidABinario($id),
            ':nombre_usuario' => $this->crypto->cifrar($username),
            ':preferencias' => json_encode($preferences, JSON_THROW_ON_ERROR),
        ]);
    }

    public function guardar(User $user): string
    {
        $idBinario = UuidHelper::v7();
        $uuid = UuidHelper::binarioAUuid($idBinario);

        $stmt = $this->pdo->prepare(
            'INSERT INTO usuarios (id, correo, correo_hash, password_hash, nombre_usuario)
             VALUES (:id, :correo, :correo_hash, :password_hash, :nombre_usuario)'
        );
        $stmt->execute([
            ':id' => $idBinario,
            ':correo' => $this->crypto->cifrar($user->email),
            ':correo_hash' => $this->hmacEmail($user->email),
            ':password_hash' => $user->passwordHash,
            ':nombre_usuario' => $this->crypto->cifrar($user->username),
        ]);
        return $uuid;
    }

    public function correoExiste(string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM usuarios WHERE correo_hash = :hash LIMIT 1'
        );
        $stmt->execute([':hash' => $this->hmacEmail($email)]);
        return (bool) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $row */
    private function hidratar(array $row): User
    {
        $preferences = [];
        if (!empty($row['preferencias'])) {
            $decoded = json_decode((string) $row['preferencias'], true);
            $preferences = is_array($decoded) ? $decoded : [];
        }

        return new User(
            id: UuidHelper::binarioAUuid($row['id']),
            email: $this->crypto->descifrar($row['correo']),
            username: $this->crypto->descifrar($row['nombre_usuario']),
            role: $row['rol'],
            isActive: (bool) $row['is_active'],
            passwordHash: $row['password_hash'],
            preferences: $preferences,
        );
    }

    private function hmacEmail(string $email): string
    {
        return hash_hmac('sha256', strtolower(trim($email)), (string)($_ENV['APP_HMAC_KEY'] ?? ''));
    }

    public function existeAlgunAdmin(): bool
    {
        $stmt = $this->pdo->query("SELECT 1 FROM usuarios WHERE rol = 'admin' LIMIT 1");
        return (bool) $stmt->fetchColumn();
    }

    public function asignarRol(string $idUsuario, string $rol): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET rol = :rol WHERE id = :id');
        $stmt->execute([
            ':rol' => $rol,
            ':id' => UuidHelper::uuidABinario($idUsuario),
        ]);
    }
}
