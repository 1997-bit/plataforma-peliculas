<?php

declare(strict_types=1);

namespace App\Controllers\Catalogo;

use App\Core\Session;
use App\Helpers\GenerosTmdb;
use App\Helpers\Normalizador;
use App\Models\AdminContenidoRepo;

final class CatalogoController
{
  public function __construct(private AdminContenidoRepo $repo)
  {
  }

  public function index(): void
  {
    $tipo = ($_GET['tipo'] ?? 'movie') === 'series' ? 'series' : 'movie';
    $generoId = isset($_GET['genero']) && $_GET['genero'] !== '' ? (int) $_GET['genero'] : null;
    $busqueda = trim((string) ($_GET['q'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($page - 1) * 20;

    $generosInternos = $generoId !== null
      ? $this->repo->idsInternosPorTmdbId([$generoId])
      : [];

    $items = Normalizador::lista(
        $this->repo->contenidoParaCatalogo($tipo, $generosInternos, 20, $offset, $busqueda)
    );

    $generos = GenerosTmdb::LISTA;
    $totalPaginas = 1; // paginacion simple: si devolvio 20 probablemente hay ma  hs
    $csrf = Session::generarCsrf();

    require ROOT . '/views/catalogo/index.php';
  }
}
