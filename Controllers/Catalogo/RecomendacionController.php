<?php

declare(strict_types=1);

namespace App\Controllers\Catalogo;

use App\Core\Session;
use App\Helpers\Normalizador;
use App\Models\AdminContenidoRepo;
use App\Models\UserRepo;

final class RecomendacionController
{
    public function __construct(
        private UserRepo $userRepo,
        private AdminContenidoRepo $repo,
    ) {
    }

    public function index(): void
    {
        $idUsuario = (string) Session::obtener('user_id', '');
        $usuario = $idUsuario !== '' ? $this->userRepo->buscarPorId($idUsuario) : null;

        if ($usuario === null) {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';

            return;
        }

        $generosFavoritos = $usuario->preferences['generos'] ?? [];
        $sinGenerosElegidos = $generosFavoritos === [];

        $tipo = ($_GET['tipo'] ?? 'movie') === 'series' ? 'series' : 'movie';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * 20;

        if ($sinGenerosElegidos) {
            $items = [];
            $totalPaginas = 1;
        } else {
            $generosInternos = $this->repo->idsInternosPorTmdbId($generosFavoritos);
            $items = Normalizador::lista(
                $this->repo->contenidoParaCatalogo($tipo, $generosInternos, 20, $offset)
            );
            $totalPaginas = 1;
        }

        $tipoActual = $tipo;
        $csrf = Session::generarCsrf();

        require ROOT . '/views/catalogo/recomendaciones.php';
    }
}
