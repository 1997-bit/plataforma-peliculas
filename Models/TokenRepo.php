<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\CryptoServicio;
use App\Services\CookieManejador;
use App\Helpers\UuidHelper;
use PDO;

class TokenRepo
{
    private const NOMBRE_COOKIE = 'remember_token';
    private const DIAS_VIDA = 30;

    public function __construct(
        private PDO $pdo,
        private CryptoServicio $crypto,
        private CookieManejador $cookie,
    ) {
    }

    public function crear(string $uuidUsuario): void
    {
        $token = bin2hex(random_bytes(32));
        $expiraEn = date('Y-m-d H:i:s', time() + (self::DIAS_VIDA * 24 * 3600));

        $stmt = $this->pdo->prepare(
            'INSERT INTO remember_tokens (user_id, token_hash, expires_at)
      VALUES (:user_id, :token_hash, :expires_at)'
        );
        $stmt->execute([
            ':user_id' => UuidHelper::uuidABinario($uuidUsuario),
            ':token_hash' => hash('sha256', $token),
            ':expires_at' => $expiraEn,
        ]);

        $this->cookie->establecer(self::NOMBRE_COOKIE, $this->crypto->cifrar($token), self::DIAS_VIDA);
    }

    /** @return array<string, mixed>|null */
    public function buscarPorToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT user_id FROM remember_tokens
             WHERE token_hash = :token_hash AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([':token_hash' => hash('sha256', $token)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function rotar(string $hashViejo, string $uuidUsuario): void
    {
        $nuevoToken = bin2hex(random_bytes(32));
        $nuevoHash = hash('sha256', $nuevoToken);
        $expiraEn = date('Y-m-d H:i:s', time() + (self::DIAS_VIDA * 24 * 3600));

        $stmt = $this->pdo->prepare(
            'UPDATE remember_tokens
             SET token_hash = :nuevo_hash, expires_at = :expires_at
             WHERE token_hash = :hash_viejo'
        );
        $stmt->execute([
            ':nuevo_hash' => $nuevoHash,
            ':expires_at' => $expiraEn,
            ':hash_viejo' => $hashViejo,
        ]);

        if ($stmt->rowCount() > 0) {
            $this->cookie->establecer(self::NOMBRE_COOKIE, $this->crypto->cifrar($nuevoToken), self::DIAS_VIDA);
        }
    }

    public function eliminar(string $token): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM remember_tokens WHERE token_hash = :token_hash'
        );
        $stmt->execute([':token_hash' => hash('sha256', $token)]);
        $this->cookie->eliminar(self::NOMBRE_COOKIE);
    }
}
