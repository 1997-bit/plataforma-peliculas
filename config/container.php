<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\UserRepo;
use App\Models\TokenRepo;
use App\Models\IntentosLoginRepo;
use App\Models\ContenidoRepo;
use App\Models\AdminContenidoRepo;
use App\Services\TmdbClient;
use App\Services\CryptoServicio;
use App\Services\Auth\ProcesarLogin;
use App\Services\Auth\RegistrarUsuario;
use App\Services\Auth\CerrarSesion;
use App\Services\Auth\RestaurarSesion;
use App\Services\User\PerfilService;

$pdo = Database::obtenerInstancia();

$crypto = new CryptoServicio();

$usuarioRepo = new UserRepo($pdo, $crypto);
$tokenRepo = new TokenRepo($pdo, $crypto);
$intentosRepo = new IntentosLoginRepo($pdo);
$contenidoRepo = new ContenidoRepo($pdo);
$adminContenidoRepo = new AdminContenidoRepo($pdo);
$tmdbClient = new TmdbClient(); // solo lo usan controllers de admin

$procesarLogin = new ProcesarLogin($usuarioRepo, $intentosRepo, $tokenRepo);
$registrarUsuario = new RegistrarUsuario($usuarioRepo, $intentosRepo);
$cerrarSesion = new CerrarSesion($tokenRepo, $intentosRepo);
$perfilService = new PerfilService($usuarioRepo);
