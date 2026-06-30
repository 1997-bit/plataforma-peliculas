<?php

declare(strict_types=1);

namespace App\Services;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use RuntimeException;

final class CryptoServicio
{
    private readonly Key $clave;

    public function __construct()
    {
        $appKey = $_ENV['APP_KEY'] ?? null;

        if (!is_string($appKey) || $appKey === '') {
            throw new RuntimeException('APP_KEY no está configurada o es inválida.');
        }

        try {
            $this->clave = Key::loadFromAsciiSafeString($appKey);
        } catch (BadFormatException|EnvironmentIsBrokenException $e) {
            throw new RuntimeException('APP_KEY tiene un formato inválido.', previous: $e);
        }
    }

    public function cifrar(string $dato): string
    {
        return Crypto::encrypt($dato, $this->clave);
    }

    public function descifrar(string $dato): string
    {
        try {
            return Crypto::decrypt($dato, $this->clave);
        } catch (WrongKeyOrModifiedCiphertextException $e) {
            throw new RuntimeException('No se pudo descifrar: dato corrupto o clave incorrecta.', previous: $e);
        }
    }
}
