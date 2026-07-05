<?php

declare(strict_types=1);

namespace App\Controllers\Catalogo;

use App\Core\Session;
use App\Helpers\Http;
use App\Helpers\Normalizador;
use App\Helpers\TmdbTipo;
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
            Http::error404();
        }

        $generosFavoritos = $usuario->preferences['generos'] ?? [];
        $sinGenerosElegidos = $generosFavoritos === [];

        $tipo = TmdbTipo::normalizar($_GET['tipo'] ?? null);
        $page = TmdbTipo::pagina($_GET['page'] ?? null);
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
