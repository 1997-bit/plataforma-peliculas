<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instancia = null;

    private function __construct()
    {
    }
    private function __clone()
    {
    }

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
                  PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'",
                ]);
            } catch (PDOException $e) {
                error_log('DB connection failed: ' . $e->getMessage());
                http_response_code(500);

                $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
                if (str_starts_with($uri, '/api/')) {
                    header('Content-Type: application/json');
                    exit(json_encode(['error' => 'Base de datos no disponible.']));
                }

                require ROOT . '/views/errors/500.php';
                exit;
            }
        }

        return self::$instancia;
    }
}
