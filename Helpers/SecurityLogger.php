<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;
use App\Models\User;

class SecurityLogger
{
  public static function registrarIntento(string $ip, string $email, bool $exito): void
  {
    $pdo  = Database::obtenerInstancia();
    $stmt = $pdo->prepare(
      'INSERT INTO login_attempts (ip, email, success) VALUES (:ip, :email, :success)'
    );
    $stmt->execute([
      ':ip' => $ip,
      ':email' => $email,
      ':success' => $exito ? 1 : 0,
    ]);
  }

  public static function intentosFallidosRecientes(string $ip, int $ventanaSegundos = 900): int
  {
    $pdo = Database::obtenerInstancia();
    $desde = date('Y-m-d H:i:s', time() - $ventanaSegundos);
    $stmt = $pdo->prepare(
      'SELECT COUNT(*) FROM login_attempts
      WHERE ip = :ip AND success = 0
      AND attempted_at >= :desde'
        );
        $stmt->execute([':ip' => $ip, ':desde' => $desde]);
        return (int)$stmt->fetchColumn();
    }

    public static function registrarEvento(string $tipoEvento, string $ip, ?string $uuidUsuario = null, array $contexto = []): void
    {
        $pdo   = Database::obtenerInstancia();
        $binId = $uuidUsuario ? User::uuidABinario($uuidUsuario) : null;

        $stmt = $pdo->prepare(
            'INSERT INTO security_events (event_type, user_id, ip, payload)
             VALUES (:event_type, :user_id, :ip, :payload)'
        );
        $stmt->execute([
            ':event_type' => $tipoEvento,
            ':user_id' => $binId,
            ':ip' => $ip,
            ':payload' => $contexto ? json_encode($contexto) : null,
        ]);
    }
}
