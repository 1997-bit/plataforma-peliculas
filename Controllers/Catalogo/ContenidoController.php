<?php
declare(strict_types=1);
namespace App\Controllers\Catalogo;
use App\Core\Session;
use App\Helpers\Http;
use App\Models\AdminContenidoRepo;
use App\Models\ContenidoRepo;
final class ContenidoController
{
    public function __construct(
        private ContenidoRepo $contenidoRepo,
        private AdminContenidoRepo $adminContenidoRepo,
    ) {
    }
    public function index(): void
    {
        $contentId = (string) ($_GET['id'] ?? '');
        if ($contentId === '') {
            Http::error404();
        }
        $detalle = $this->adminContenidoRepo->buscarPorId($contentId);
        if ($detalle === null) {
            Http::error404();
        }
        $idUsuario = (string) Session::obtener('user_id');
        $this->contenidoRepo->registrarVistaConThrottle($idUsuario, $contentId);
        $miCalificacion = $this->contenidoRepo->obtenerCalificacionUsuario($idUsuario, $contentId);
        $infoLocal = [
            'rating_avg' => (float) $detalle['rating_avg'],
            'rating_count' => (int) $detalle['rating_count'],
        ];
        $detalle = [
            'title' => $detalle['titulo'],
            'overview' => $detalle['descripcion'],
            'genres' => array_map(static fn (string $n): array => ['name' => $n], $detalle['generos']),
            'poster_path' => $detalle['poster_path'],
            'backdrop_path' => $detalle['backdrop_path'] ?? null,
            'logo_path' => $detalle['logo_path'] ?? null,
            'release_date' => isset($detalle['anio_lanzamiento']) ? $detalle['anio_lanzamiento'] . '-01-01' : null,
        ];
        $cast = [];
        $backdropPath = $detalle['backdrop_path'];
        $recomendados = $this->adminContenidoRepo->similaresPorGeneros($contentId, 5);
        $csrf = Session::generarCsrf();
        require ROOT . '/views/catalogo/detalle.php';
    }
    public function calificar(): void
    {
        header('Content-Type: application/json');

        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            $this->json(403, ['error' => 'CSRF invalido']);
            return;
        }

        if (!is_scalar($_POST['content_id'] ?? null) || !is_scalar($_POST['estrellas'] ?? null)) {
            $this->json(400, ['error' => 'Datos invalidos']);
            return;
        }

        $contentId = (string) $_POST['content_id'];
        $estrellas = (int) $_POST['estrellas'];
        if ($contentId === '' || $estrellas < 1 || $estrellas > 5) {
            $this->json(400, ['error' => 'Datos invalidos']);
            return;
        }

        $idUsuario = (string) Session::obtener('user_id', '');
        if ($idUsuario === '') {
            $this->json(401, ['error' => 'No autenticado']);
            return;
        }

        if (!$this->contenidoRepo->existeContenido($contentId)) {
            $this->json(404, ['error' => 'Contenido no encontrado']);
            return;
        }

        try {
            $this->contenidoRepo->calificar($idUsuario, $contentId, $estrellas);
            $this->json(200, ['ok' => true]);
        } catch (\Throwable $e) {
            error_log('ContenidoController::calificar - ' . $e->getMessage());
            $this->json(500, ['error' => 'Error interno']);
        }
    }

    private function json(int $codigo, array $datos): void
    {
        http_response_code($codigo);
        echo json_encode($datos);
    }
}
