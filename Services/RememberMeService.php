<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Helpers\Cookie;
use App\Helpers\CryptoHelper;
use App\Models\User;
use PDO;

/**
 * Maneja el flujo completo de "recordarme".
 *
 * Flujo:
 *   1. Al login con checkbox marcado -> crear() genera token y lo guarda.
 *   2. En cada peticion sin sesion -> intentarRestaurar() intenta restaurar la sesion.
 *   3. Al logout -> limpiar() borra el token de BD y la cookie.
 *
 * El token raw NUNCA se guarda en BD, solo su SHA-256.
 * La cookie guarda el token cifrado con AES-256-GCM (FIPS 197).
 */
class RememberMeService
{
  private const NOMBRE_COOKIE  = 'remember_token';
  private const DIAS_VIDA = 30;

  /**
   * Crea un token nuevo y lo asocia al usuario.
   * Llamar justo despues de login exitoso si el usuario marco "recordarme".
   */
  public static function crear(string $uuidUsuario): void
  {
    $token = bin2hex(random_bytes(32));
    $hashToken = hash('sha256', $token);
    $expiraEn = date('Y-m-d H:i:s', time() + (self::DIAS_VIDA * 24 * 3600));

    $pdo  = Database::obtenerInstancia();
    $stmt = $pdo->prepare(
      'INSERT INTO remember_tokens (user_id, token_hash, expires_at)
      VALUES (:user_id, :token_hash, :expires_at)'
        );
        $stmt->execute([
            ':user_id' => User::uuidABinario($uuidUsuario),
            ':token_hash' => $hashToken,
            ':expires_at' => $expiraEn,
        ]);

        Cookie::establecer(self::NOMBRE_COOKIE, CryptoHelper::cifrar($token), self::DIAS_VIDA);
    }

    /**
     * Intenta restaurar la sesion a partir de la cookie.
     * Retorna true si logro autenticar, false si no habia cookie o era invalida.
     * Si tiene exito, rota el token para evitar reutilizacion.
     */
    public static function intentarRestaurar(): bool
    {
        if (!Cookie::existe(self::NOMBRE_COOKIE)) {
            return false;
        }

        try {
            $token = CryptoHelper::descifrar(Cookie::obtener(self::NOMBRE_COOKIE));
        } catch (\Exception $e) {
            self::limpiar();
            return false;
        }

        $hashToken = hash('sha256', $token);

        $pdo  = Database::obtenerInstancia();
        $stmt = $pdo->prepare(
            'SELECT user_id FROM remember_tokens
             WHERE token_hash = :token_hash
             AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([':token_hash' => $hashToken]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            self::limpiar();
            return false;
        }

        $uuid    = User::binarioAUuid($fila['user_id']);
        $usuario = User::buscarPorId($uuid);

        if (!$usuario || (int)$usuario['is_active'] !== 1) {
            self::limpiar();
            return false;
        }

        Session::regenerar();
        Session::establecer('user_id', $usuario['id']);
        Session::establecer('user_role', $usuario['role']);
        Session::establecer('username', $usuario['username']);

        self::rotar($hashToken, $uuid);

        return true;
    }

    /**
     * Elimina el token de BD y borra la cookie.
     * Llamar siempre en logout.
     */
    public static function limpiar(): void
    {
        if (!Cookie::existe(self::NOMBRE_COOKIE)) {
            return;
        }

        try {
            $token = CryptoHelper::descifrar(Cookie::obtener(self::NOMBRE_COOKIE));
            $hashToken = hash('sha256', $token);

            $pdo = Database::obtenerInstancia();
            $stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE token_hash = :token_hash');
            $stmt->execute([':token_hash' => $hashToken]);
        } catch (\Exception $e) {
            // Token corrupto, no hay nada que borrar en BD
        }

        Cookie::eliminar(self::NOMBRE_COOKIE);
    }

    /**
     * Reemplaza el token actual por uno nuevo en BD y en la cookie.
     * Se llama automaticamente despues de cada intentarRestaurar() exitoso.
     */
    private static function rotar(string $hashViejo, string $uuidUsuario): void
    {
        $nuevoToken = bin2hex(random_bytes(32));
        $nuevoHash = hash('sha256', $nuevoToken);
        $expiraEn = date('Y-m-d H:i:s', time() + (self::DIAS_VIDA * 24 * 3600));

        $pdo  = Database::obtenerInstancia();
        $stmt = $pdo->prepare(
            'UPDATE remember_tokens
             SET token_hash = :nuevo_hash, expires_at = :expires_at
             WHERE token_hash = :hash_viejo'
        );
        $stmt->execute([
            ':nuevo_hash' => $nuevoHash,
            ':expires_at' => $expiraEn,
            ':hash_viejo' => $hashViejo,
        ]);

        Cookie::establecer(self::NOMBRE_COOKIE, CryptoHelper::cifrar($nuevoToken), self::DIAS_VIDA);
    }
}
