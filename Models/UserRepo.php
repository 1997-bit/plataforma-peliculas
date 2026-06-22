<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\CryptoServicio;
use App\Helpers\UuidHelper;
use PDO;
use Ramsey\Uuid\Uuid;

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
        'SELECT id, email, password_hash, username, role, is_active
  FROM users WHERE email_hash = :hash LIMIT 1'
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
            'SELECT id, email, password_hash, username, role, is_active
             FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => UuidHelper::uuidABinario($id)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        return $this->hidratar($row);
    }

    public function guardar(User $user): string
    {
        $uuid = Uuid::uuid4()->toString();
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (id, email, email_hash, password_hash, username)
             VALUES (:id, :email, :email_hash, :password_hash, :username)'
        );
        $stmt->execute([
            ':id' => UuidHelper::uuidABinario($uuid),
            ':email' => $this->crypto->cifrar($user->email),
            ':email_hash' => $this->hmacEmail($user->email),
            ':password_hash' => $user->passwordHash,
            ':username' => $this->crypto->cifrar($user->username),
        ]);
        return $uuid;
    }

    public function correoExiste(string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM users WHERE email_hash = :hash LIMIT 1'
        );
        $stmt->execute([':hash' => $this->hmacEmail($email)]);
        return (bool) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $row */
    private function hidratar(array $row): User
    {
        return new User(
            id: UuidHelper::binarioAUuid($row['id']),
            email: $this->crypto->descifrar($row['email']),
            username: $this->crypto->descifrar($row['username']),
            role: $row['role'],
            isActive: (bool) $row['is_active'],
            passwordHash: $row['password_hash'],
        );
    }

    private function hmacEmail(string $email): string
    {
        return hash_hmac('sha256', strtolower(trim($email)), (string)($_ENV['APP_HMAC_KEY'] ?? ''));
    }
}
