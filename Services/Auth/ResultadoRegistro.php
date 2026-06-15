<?php

declare(strict_types=1);

namespace App\Services\Auth;

class ResultadoRegistro
{
    public function __construct(
        public readonly bool $success,
        public readonly string $redirectUrl = '',
        public readonly string $errorMsg = '',
    ) {
    }
}
