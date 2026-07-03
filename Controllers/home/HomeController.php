<?php

declare(strict_types=1);

namespace App\Controllers\Home;

use App\Core\Session;
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
        $generosInternos = $generosFavoritos !== []
            ? $this->adminContenidoRepo->idsInternosPorTmdbId($generosFavoritos)
            : [];

        $populares = array_map(
            fn ($f) => $this->normalizar($f),
            $this->adminContenidoRepo->contenidoParaCatalogo('movie', $generosInternos, 20)
        );
        $series = array_map(
            fn ($f) => $this->normalizar($f),
            $this->adminContenidoRepo->contenidoParaCatalogo('series', $generosInternos, 20)
        );

        $recomendaciones = $generosInternos !== [] ? array_merge($populares, $series) : [];
        $hero = array_slice($recomendaciones !== [] ? $recomendaciones : $populares, 0, 8);
        $vistoReciente = $this->contenidoRepo->historialReciente($idUsuario, 10);

        $username = Session::obtener('username');
        $csrf = Session::generarCsrf();

        require ROOT . '/views/home.php';
    }

    private function normalizar(array $fila): array
    {
        return [
            'id' => $fila['id'],
            'type' => $fila['type'],
            'title' => $fila['titulo'],
            'name' => $fila['titulo'],
            'overview' => $fila['descripcion'] ?? '',
            'poster_path' => $fila['poster_path'] ?? null,
            'backdrop_path' => $fila['backdrop_path'] ?? null,
            'logo_path' => $fila['logo_path'] ?? null,
            'release_date' => isset($fila['anio_lanzamiento']) ? $fila['anio_lanzamiento'] . '-01-01' : null,
            'first_air_date' => isset($fila['anio_lanzamiento']) ? $fila['anio_lanzamiento'] . '-01-01' : null,
            'origen' => 'local',
        ];
    }
}
