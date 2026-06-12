<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Helpers\CryptoHelper;
use PDO;
use Ramsey\Uuid\Uuid;

/**
 * Modelo de usuarios.
 *
 * Los campos email y username se guardan cifrados en BD (AES-256-GCM).
 * Los metodos de busqueda siempre retornan los datos ya descifrados.
 */
class User
{
  /**
   * Hash argon2id válido usado cuando el usuario no existe, para garantizar
   * tiempo constante en la verificación y evitar timing attacks (CWE-208).
   */
  public const DUMMY_HASH = '$argon2id$v=19$m=65536,t=4,p=1$AAAAAAAAAAAAAAAAAAAAAA$AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';

  public static function uuidABinario(string $uuid): string
  {
    return hex2bin(str_replace('-', '', $uuid));
  }

  public static function binarioAUuid(string $bin): string
  {
    $hex = bin2hex($bin);
    return sprintf('%s-%s-%s-%s-%s',
      substr($hex, 0, 8),
      substr($hex, 8, 4),
      substr($hex, 12, 4),
      substr($hex, 16, 4),
      substr($hex, 20)
    );
  }

  /**
   * Busca un usuario por email usando el blind index (email_hash).
   * La búsqueda es O(1) por índice; nunca trae toda la tabla a memoria.
   */
  public static function buscarPorEmail(string $email): ?array
  {
    $pdo  = Database::obtenerInstancia();
    $stmt = $pdo->prepare(
      'SELECT id, email, password_hash, username, role, preferences, is_active
      FROM users WHERE email_hash = :hash LIMIT 1'
        );
        $stmt->execute([':hash' => self::hashEmail($email)]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            return null;
        }

        $fila['id'] = self::binarioAUuid($fila['id']);
        $fila['email'] = CryptoHelper::descifrar($fila['email']);
        $fila['username'] = CryptoHelper::descifrar($fila['username']);
        return $fila;
    }

    /**
     * Busca un usuario por su UUID.
     * Retorna el usuario con datos descifrados, o null si no existe.
     */
    public static function buscarPorId(string $uuid): ?array
    {
        $pdo  = Database::obtenerInstancia();
        $stmt = $pdo->prepare(
            'SELECT id, email, username, role, preferences, is_active, created_at
             FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => self::uuidABinario($uuid)]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) return null;

        $fila['id'] = self::binarioAUuid($fila['id']);
        $fila['email'] = CryptoHelper::descifrar($fila['email']);
        $fila['username'] = CryptoHelper::descifrar($fila['username']);
        return $fila;
    }

    /**
     * Crea un usuario nuevo en BD.
     * El email y username se cifran, la password se hashea con Argon2id.
     * Retorna el UUID del usuario creado.
     */
    public static function crear(string $email, string $password, string $username): string
    {
        $pdo  = Database::obtenerInstancia();
        $uuid = Uuid::uuid4()->toString();

        $stmt = $pdo->prepare(
            'INSERT INTO users (id, email, email_hash, password_hash, username)
             VALUES (:id, :email, :email_hash, :password_hash, :username)'
        );
        $stmt->execute([
            ':id' => self::uuidABinario($uuid),
            ':email' => CryptoHelper::cifrar($email),
            ':email_hash' => self::hashEmail($email),
            ':password_hash' => password_hash($password, PASSWORD_ARGON2ID),
            ':username' => CryptoHelper::cifrar($username),
        ]);

        return $uuid;
    }

    /**
     * Verifica si una password coincide con el hash guardado (Argon2id).
     */
    public static function verificarPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Verifica si un email ya está registrado usando el blind index.
     */
    public static function emailExiste(string $email): bool
    {
        $pdo  = Database::obtenerInstancia();
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email_hash = :hash LIMIT 1');
        $stmt->execute([':hash' => self::hashEmail($email)]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * HMAC-SHA256 del email normalizado — blind index determinista.
     * Usa APP_HMAC_KEY para que el hash no sea reproducible sin la clave.
     */
    private static function hashEmail(string $email): string
    {
        return hash_hmac('sha256', strtolower(trim($email)), $_ENV['APP_HMAC_KEY']);
    }
}
