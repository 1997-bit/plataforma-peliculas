<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
  private static ?PDO $instancia = null;

  private function __construct() {}
    private function __clone() {}

      public static function obtenerInstancia(): PDO
      {
        if (self::$instancia === null) {
          $host = $_ENV['DB_HOST'] ?? 'localhost';
          $port = $_ENV['DB_PORT'] ?? '3306';
          $name = $_ENV['DB_NAME'] ?? 'cineapp';
          $user = $_ENV['DB_USER'] ?? 'root';
          $pass = $_ENV['DB_PASS'] ?? '';

          $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

          try {
            self::$instancia = new PDO($dsn, $user, $pass, [
              PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
              PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
              PDO::ATTR_EMULATE_PREPARES => false,
            ]);
          } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit(json_encode(['error' => 'Database unavailable']));
          }
        }

        return self::$instancia;
      }
}
