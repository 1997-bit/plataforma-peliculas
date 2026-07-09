<?php

declare(strict_types=1);

namespace App\Services\User;

final class ResultadoPerfil
{
    public function __construct(
        public readonly bool $success,
        public readonly string $username = '',
        /** @var array{generos?: list<int>, tema?: string} */
        public readonly array $preferences = [],
        /** @var list<string> */
        public readonly array $errores = [],
    ) {
    }

    public function primerError(): string
    {
        return $this->errores[0] ?? '';
    }
}
