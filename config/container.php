<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\UserRepo;
use App\Models\TokenRepo;
use App\Models\IntentosLoginRepo;
use App\Services\CryptoServicio;
use App\Services\SessionManager;
use App\Services\CookieManejador;
use App\Services\Auth\ProcesarLogin;
use App\Services\Auth\RegistrarUsuario;
use App\Services\Auth\CerrarSesion;
use App\Services\Auth\RestaurarSesion;

$pdo = Database::obtenerInstancia();

$crypto = new CryptoServicio();
$session = new SessionManager();
$cookie = new CookieManejador();

$usuarioRepo = new UserRepo($pdo, $crypto);
$tokenRepo = new TokenRepo($pdo, $crypto, $cookie);
$intentosRepo = new IntentosLoginRepo($pdo);

$procesarLogin = new ProcesarLogin($usuarioRepo, $intentosRepo, $session, $tokenRepo);
$registrarUsuario = new RegistrarUsuario($usuarioRepo, $intentosRepo, $session);
$cerrarSesion = new CerrarSesion($session, $tokenRepo, $cookie, $intentosRepo);
