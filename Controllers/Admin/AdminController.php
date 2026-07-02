<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Session;
use App\Models\AdminContenidoRepo;
use App\Middleware\AuthMiddleware;

/**
 * Maneja el panel de administracion.
 * Solo accesible con rol admin.
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
        $okMsg = isset($_GET['xml_importado']) ? 'Archivo XML importado correctamente.' : null;
        $errorMsg = isset($_GET['xml_error']) ? (string) $_GET['xml_error'] : null;
        $contenidoLocal = $this->adminContenidoRepo->listarContenidoLocal(10);

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
}
