<?php
declare(strict_types=1);
namespace App\Controllers\Catalogo;
use App\Core\Session;
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
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            return;
        }
        $detalle = $this->adminContenidoRepo->buscarPorId($contentId);
        if ($detalle === null) {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            return;
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
        $csrf = Session::generarCsrf();
        require ROOT . '/views/catalogo/detalle.php';
    }
    public function calificar(): void
    {
        header('Content-Type: application/json');
        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'CSRF invalido']);
            return;
        }
        $contentId = (string) ($_POST['content_id'] ?? '');
        $estrellas = (int) ($_POST['estrellas'] ?? 0);
        if ($contentId === '' || $estrellas < 1 || $estrellas > 5) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos invalidos']);
            return;
        }
        $idUsuario = (string) Session::obtener('user_id', '');
        if ($idUsuario === '') {
            http_response_code(401);
            echo json_encode(['error' => 'No autenticado']);
            return;
        }
        try {
            if (!$this->contenidoRepo->existeContenido($contentId)) {
                http_response_code(404);
                echo json_encode(['error' => 'Contenido no encontrado']);
                return;
            }
            $this->contenidoRepo->calificar($idUsuario, $contentId, $estrellas * 2);
            echo json_encode(['ok' => true]);
        } catch (\Throwable $e) {
            error_log('ContenidoController::calificar - ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error interno']);
        }
    }
}
