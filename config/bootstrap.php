<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

define('ROOT', dirname(__DIR__));

use App\Core\Session;
use App\Services\RememberMeService;

$dotenv = Dotenv\Dotenv::createImmutable(ROOT);
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'APP_KEY', 'APP_HMAC_KEY'])->notEmpty();

date_default_timezone_set('UTC');

$isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
ini_set('display_errors', $isDebug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' https://image.tmdb.org data:; style-src 'self' 'unsafe-inline'");
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

Session::iniciar();

if (!Session::existe('user_id')) {
  RememberMeService::intentarRestaurar();
}
