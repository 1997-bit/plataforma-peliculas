<?php

declare(strict_types=1);

namespace App\Controllers\Home;

use App\Core\Session;
use App\Helpers\Normalizador;
use App\Models\AdminContenidoRepo;
use App\Models\ContenidoRepo;
use App\Models\UserRepo;

final class HomeController
{
    public function __construct(
        private ContenidoRepo $contenidoRepo,
        private UserRepo $userRepo,
        private AdminContenidoRepo $adminContenidoRepo,
    ) {
    }

    public function index(): void
    {
        $idUsuario = (string) Session::obtener('user_id');
        $usuario = $idUsuario !== '' ? $this->userRepo->buscarPorId($idUsuario) : null;

        $generosFavoritos = $usuario?->preferences['generos'] ?? [];

        $populares = Normalizador::lista(
            $this->adminContenidoRepo->contenidoParaCatalogo('movie', $generosFavoritos, 20)
        );
        $series = Normalizador::lista(
            $this->adminContenidoRepo->contenidoParaCatalogo('series', $generosFavoritos, 20)
        );

        $recomendaciones = $generosFavoritos !== [] ? array_merge($populares, $series) : [];
        $hero = array_slice($recomendaciones !== [] ? $recomendaciones : $populares, 0, 8);
        $vistoReciente = $this->contenidoRepo->historialReciente($idUsuario, 10);

        $username = Session::obtener('username');
        $csrf = Session::generarCsrf();

        require ROOT . '/views/home.php';
    }
}
