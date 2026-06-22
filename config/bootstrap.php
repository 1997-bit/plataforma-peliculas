<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

define('ROOT', dirname(__DIR__));

use App\Core\Session;
use App\Core\Database;
use App\Models\TokenRepo;
use App\Models\UserRepo;
use App\Services\Auth\RestaurarSesion;
use App\Services\CookieManejador;
use App\Services\CryptoServicio;
use App\Services\SessionManager;

header_remove('X-Powered-By');

$dotenv = Dotenv\Dotenv::createImmutable(ROOT);
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'APP_KEY', 'APP_HMAC_KEY'])->notEmpty();

date_default_timezone_set('UTC');

$isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
ini_set('display_errors', $isDebug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/security.php';

Session::iniciar();

if (random_int(1, 100) === 1) {
    try {
        Database::obtenerInstancia()->exec('DELETE FROM remember_tokens WHERE expires_at < NOW()');
    } catch (\Throwable) {
    }
}

if (!Session::existe('user_id') && CookieManejador::existe('remember_token')) {
    $pdo = Database::obtenerInstancia();
    $crypto = new CryptoServicio();
    $cookie = new CookieManejador();

    (new RestaurarSesion(
        new TokenRepo($pdo, $crypto, $cookie),
        new UserRepo($pdo, $crypto),
        new SessionManager(),
        $cookie,
        $crypto,
    ))->restaurarSesion();
}
