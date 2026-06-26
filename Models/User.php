<?php

declare(strict_types=1);

namespace App\Models;

class User
{
    public const DUMMY_HASH = '$argon2id$v=19$m=65536,t=4,p=1$AAAAAAAAAAAAAAAAAAAAAA$AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';

    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $username,
        public readonly string $role,
        public readonly bool $isActive,
        public readonly string $passwordHash,
        /** @var array{generos?: list<int>, tema?: string} */
        public readonly array $preferences = [],
    ) {
    }

    public function esAdmin(): bool
    {
        return $this->role === 'admin';
    }
    public function puedeIniciarSesion(): bool
    {
        return $this->isActive;
    }

    public function verificarPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }
}
