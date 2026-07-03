<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Session;
use App\Models\AdminContenidoRepo;
use App\Services\ContenidoValidator;
use App\Services\PosterUploader;

/**
 * Maneja el panel de administracion.
 * Solo accesible con rol admin (chequeo server-side via
 * AuthMiddleware::requerirRol('admin') en public/index.php, ANTES de que
 * cualquier metodo de esta clase se ejecute).
 */
final class AdminController
{
    public function __construct(private AdminContenidoRepo $adminContenidoRepo)
    {
    }

    public function index(): void
    {
        // Se conecta el panel de admin con el XML del catálogo, para que todo
        // quede en un solo lugar y no tenga que abrir otro flujo aparte.
        $csrf = Session::generarCsrf();
        $okMsg = $this->mensajeOk();
        $errorMsg = isset($_GET['xml_error']) ? (string) $_GET['xml_error'] : null;
        $contenidoLocal = $this->adminContenidoRepo->listarContenidoLocal(50);

        require ROOT . '/views/admin/index.php';
    }

    public function exportarXml(): void
    {
        $xml = $this->adminContenidoRepo->exportarContenidoXml();

        header('Content-Type: application/xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="catalogo-local.xml"');
        echo $xml;
    }

    public function importarXml(): void
    {
        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            require ROOT . '/views/errors/403.php';
            exit;
        }

        // Aquí se toma el archivo subido y se manda al repositorio, para mantener la
        // lógica de importación en la misma capa de datos que ya usa el proyecto.
        $archivo = $_FILES['xml_catalogo'] ?? null;
        if (!is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_string($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
            header('Location: /admin?xml_error=' . urlencode('Debes seleccionar un archivo XML válido.'));
            exit;
        }

        $contenidoXml = file_get_contents($archivo['tmp_name']);
        if ($contenidoXml === false || trim($contenidoXml) === '') {
            header('Location: /admin?xml_error=' . urlencode('El archivo XML está vacío.'));
            exit;
        }

        $resultado = $this->adminContenidoRepo->importarContenidoXml($contenidoXml, (string) Session::obtener('user_id'));
        if (!$resultado['success']) {
            header('Location: /admin?xml_error=' . urlencode(implode(' ', $resultado['errors'])));
            exit;
        }

        header('Location: /admin?xml_importado=1');
        exit;
    }

    /**
     * GET /admin/contenido/nuevo — muestra el form vacio.
     */
    public function nuevo(): void
    {
        $csrf = Session::generarCsrf();
        $generos = $this->adminContenidoRepo->listarGeneros();
        $modo = 'crear';
        $item = null;
        $generoIdsSeleccionados = [];
        $errorMsg = null;

        require ROOT . '/views/admin/form.php';
    }

    /**
     * POST /admin/contenido/nuevo — valida, sube poster y crea el contenido + generos.
     */
    public function guardar(): void
    {
        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            require ROOT . '/views/errors/403.php';
            exit;
        }

        $datos = ContenidoValidator::validar($_POST, $this->adminContenidoRepo->listarGeneros());

        // poster es obligatorio al crear.
        $poster = PosterUploader::subir($_FILES['poster'] ?? null, obligatorio: true);
        if ($poster['error'] !== null) {
            $datos['errores'][] = $poster['error'];
        }

        if ($datos['errores'] !== []) {
            $csrf = Session::generarCsrf();
            $generos = $this->adminContenidoRepo->listarGeneros();
            $modo = 'crear';
            $item = $_POST;
            $generoIdsSeleccionados = $datos['generoIds'];
            $errorMsg = implode(' ', $datos['errores']);

            require ROOT . '/views/admin/form.php';
            return;
        }

        $this->adminContenidoRepo->crearContenidoLocal(
            $datos['tipo'],
            $datos['titulo'],
            $datos['descripcion'],
            $poster['path'],
            $datos['anio'],
            $datos['generoIds'],
            (string) Session::obtener('user_id')
        );

        header('Location: /admin?contenido_creado=1');
        exit;
    }

    /**
     * GET /admin/contenido/editar?id=... — muestra el form pre-poblado.
     */
    public function editar(): void
    {
        $id = (string) ($_GET['id'] ?? '');
        $item = $this->cargarItemOFallar($id);

        $csrf = Session::generarCsrf();
        $generos = $this->adminContenidoRepo->listarGeneros();
        $modo = 'editar';
        $generoIdsSeleccionados = $item['genero_ids'];
        $errorMsg = null;

        require ROOT . '/views/admin/form.php';
    }

    /**
     * POST /admin/contenido/editar?id=... — valida, opcionalmente reemplaza
     * el poster y actualiza contenido + generos (transaccion, ver repo).
     */
    public function actualizar(): void
    {
        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            require ROOT . '/views/errors/403.php';
            exit;
        }

        $id = (string) ($_GET['id'] ?? $_POST['id'] ?? '');
        $itemActual = $this->cargarItemOFallar($id);

        $datos = ContenidoValidator::validar($_POST, $this->adminContenidoRepo->listarGeneros());

        // poster es OPCIONAL al editar: si no suben uno nuevo, se mantiene el que ya tenia.
        $poster = PosterUploader::subir($_FILES['poster'] ?? null, obligatorio: false);
        if ($poster['error'] !== null) {
            $datos['errores'][] = $poster['error'];
        }

        if ($datos['errores'] !== []) {
            $csrf = Session::generarCsrf();
            $generos = $this->adminContenidoRepo->listarGeneros();
            $modo = 'editar';
            $item = array_merge($itemActual, $_POST, ['id' => $itemActual['id']]);
            $generoIdsSeleccionados = $datos['generoIds'];
            $errorMsg = implode(' ', $datos['errores']);

            require ROOT . '/views/admin/form.php';
            return;
        }

        $posterFinal = $poster['path'] ?? $itemActual['poster_path'];

        $this->adminContenidoRepo->actualizarContenidoLocal(
            $itemActual['id'],
            $datos['titulo'],
            $datos['descripcion'],
            $posterFinal,
            $datos['anio'],
            $datos['generoIds']
        );

        header('Location: /admin?contenido_actualizado=1');
        exit;
    }

    /**
     * POST /admin/contenido/eliminar — soft delete (is_active=0). Nunca DELETE fisico.
     */
    public function eliminar(): void
    {
        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            require ROOT . '/views/errors/403.php';
            exit;
        }

        $id = (string) ($_POST['id'] ?? '');
        // valida que exista y sea local antes de tocar nada; si no existe
        // o el id viene manipulado, 404 en vez de fallar silencioso.
        $this->cargarItemOFallar($id);

        $this->adminContenidoRepo->eliminarContenidoLocal($id);

        header('Location: /admin?contenido_eliminado=1');
        exit;
    }

    /**
     * Trae un item local por id o corta con 404. Centraliza el chequeo
     * para no repetirlo en editar/actualizar/eliminar.
     *
     * @return array<string,mixed>
     */
    private function cargarItemOFallar(string $id): array
    {
        if ($id === '') {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            exit;
        }

        try {
            $item = $this->adminContenidoRepo->obtenerLocalPorIdParaEditar($id);
        } catch (\InvalidArgumentException) {
            // uuid con formato invalido (alguien tocando la url a mano) -> 404, no 500.
            $item = null;
        }

        if ($item === null) {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            exit;
        }

        return $item;
    }

    private function mensajeOk(): ?string
    {
        if (isset($_GET['xml_importado'])) {
            return 'Archivo XML importado correctamente.';
        }
        if (isset($_GET['contenido_creado'])) {
            return 'Contenido creado correctamente.';
        }
        if (isset($_GET['contenido_actualizado'])) {
            return 'Contenido actualizado correctamente.';
        }
        if (isset($_GET['contenido_eliminado'])) {
            return 'Contenido eliminado (soft delete).';
        }

        return null;
    }
}
