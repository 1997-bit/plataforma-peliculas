<?php
declare(strict_types=1);

namespace App\Models;

use App\Helpers\UuidHelper;
use PDO;

class IntentosLoginRepo
{
  public function __construct(
    private PDO $pdo,
  ) {}

  public function registrarIntento(string $ip, string $email, bool $exito): void
  {
    $stmt = $this->pdo->prepare(
      'INSERT INTO login_attempts (ip, email, success)
      VALUES (:ip, :email, :success)'
        );
        $stmt->execute([
            ':ip' => $ip,
            ':email' => $email,
            ':success' => $exito ? 1 : 0,
        ]);
    }

    public function contarFallidosRecientes(string $ip, int $ventanaSegundos = 900): int
    {
        $desde = date('Y-m-d H:i:s', time() - $ventanaSegundos);
        $stmt  = $this->pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE ip = :ip AND success = 0 AND attempted_at >= :desde'
        );
        $stmt->execute([':ip' => $ip, ':desde' => $desde]);
        return (int) $stmt->fetchColumn();
    }

    public function registrarEvento(string $tipo, string $ip, ?string $uuid, array $ctx = []): void
    {
        $binId = $uuid ? UuidHelper::uuidABinario($uuid) : null;
        $stmt = $this->pdo->prepare(
            'INSERT INTO security_events (event_type, user_id, ip, payload)
             VALUES (:event_type, :user_id, :ip, :payload)'
        );
        $stmt->execute([
            ':event_type' => $tipo,
            ':user_id' => $binId,
            ':ip' => $ip,
            ':payload' => $ctx ? json_encode($ctx) : null,
        ]);
    }
}

