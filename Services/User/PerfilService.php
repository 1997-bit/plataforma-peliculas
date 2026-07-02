<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\User;
use App\Models\UserRepo;
use voku\helper\AntiXSS;

/**
 * Logica de negocio del perfil, sin nada de HTTP.
 *
 * Deliberadamente separado del Controller para que mas adelante un endpoint
 * REST de exportacion (u otro canal) pueda reusar obtenerPerfil()/actualizarPerfil()
 * sin duplicar validacion ni reglas de negocio.
 */
final class PerfilService
{
    private const GENEROS_MAX = 10;
    private const TEMAS_VALIDOS = ['light', 'dark'];

    public function __construct(private UserRepo $usuarios)
    {
    }

    public function obtenerPerfil(string $idUsuario): ?User
    {
        return $this->usuarios->buscarPorId($idUsuario);
    }

    /**
     * @param list<int|string> $generosCrudos ids de genero tal cual vienen del formulario
     * @return ResultadoPerfil
     */
    public function actualizarPerfil(string $idUsuario, string $usernameCrudo, array $generosCrudos): ResultadoPerfil
    {
        $username = $this->sanitizarTexto($usernameCrudo);
        $generos = $this->normalizarGeneros($generosCrudos);
        $errores = $this->validarPerfil($username, $generos);
        if ($errores !== []) {
            return new ResultadoPerfil(success: false, errores: $errores);
        }

        $usuarioActual = $this->usuarios->buscarPorId($idUsuario);
        if ($usuarioActual === null) {
            return new ResultadoPerfil(success: false, errores: ['Usuario no encontrado.']);
        }

        // preferences es JSON libre; conservamos otras claves que ya existieran (ej. tema)
        // y solo pisamos "generos" para no perder configuracion ajena a este formulario.
        $preferences = $usuarioActual->preferences;
        $preferences['generos'] = $generos;

        $this->usuarios->actualizarPerfil($idUsuario, $username, $preferences);

        return new ResultadoPerfil(success: true, username: $username, preferences: $preferences);
    }

    /** @return list<string> */
    private function validarPerfil(string $username, array $generos): array
    {
        $errores = [];

        if (strlen($username) < 2 || strlen($username) > 50) {
            $errores[] = 'El nombre debe tener entre 2 y 50 caracteres.';
        }

        if (count($generos) > self::GENEROS_MAX) {
            $errores[] = 'Puedes elegir como máximo ' . self::GENEROS_MAX . ' géneros.';
        }

        return $errores;
    }

    private function sanitizarTexto(string $valor): string
    {
        $limpio = (new AntiXSS())->xss_clean(trim($valor));
        return strip_tags($limpio);
    }

    /**
     * @param list<int|string> $generosCrudos
     * @return list<int>
     */
    private function normalizarGeneros(array $generosCrudos): array
    {
        $ids = array_map(static fn (int|string $g): int => (int) $g, $generosCrudos);
        $ids = array_filter($ids, static fn (int $id): bool => $id > 0);
        $ids = array_values(array_unique($ids));

        return $ids;
    }

    /**
     * Genera XML de preferencias (username + generos + tema).
     *
     * <preferencias version="1">
     *   <username>...</username>
     *   <tema>...</tema>
     *   <generos><genero id="28"/><genero id="16"/></generos>
     * </preferencias>
     */
    public function exportarSettingsXml(string $idUsuario): string
    {
        // Se construyó este XML para que el usuario pueda sacar su configuración
        // de forma simple y luego volver a cargarla cuando la necesite.
        $usuario = $this->usuarios->buscarPorId($idUsuario);
        if ($usuario === null) {
            throw new \RuntimeException('Usuario no encontrado.');
        }

        $tema = $this->temaExportable($usuario->preferences['tema'] ?? ($_COOKIE['tema'] ?? 'light'));
        $generos = $usuario->preferences['generos'] ?? [];
        $generos = is_array($generos) ? $generos : [];

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;

        $root = $dom->createElement('preferencias');
        $root->setAttribute('version', '1');
        $dom->appendChild($root);

        $root->appendChild($this->crearNodoTexto($dom, 'username', $usuario->username));
        $root->appendChild($this->crearNodoTexto($dom, 'tema', $tema));

        $nodoGeneros = $dom->createElement('generos');
        foreach ($this->normalizarGeneros(array_map('intval', $generos)) as $idGenero) {
            $genero = $dom->createElement('genero');
            $genero->setAttribute('id', (string) $idGenero);
            $nodoGeneros->appendChild($genero);
        }
        $root->appendChild($nodoGeneros);

        return $dom->saveXML() ?: '';
    }

    public function importarSettingsXml(string $idUsuario, string $contenidoXml): ResultadoPerfil
    {
        // Aquí se validá primero la estructura y los valores del XML para no pisar
        // la cuenta con datos raros o incompletos.
        $usuarioActual = $this->usuarios->buscarPorId($idUsuario);
        if ($usuarioActual === null) {
            return new ResultadoPerfil(success: false, errores: ['Usuario no encontrado.']);
        }

        $dom = $this->cargarXmlSeguro($contenidoXml);
        if ($dom === null) {
            return new ResultadoPerfil(success: false, errores: ['El archivo XML es inválido o no se pudo leer.']);
        }

        $root = $dom->documentElement;
        if ($root === null || $root->nodeName !== 'preferencias') {
            return new ResultadoPerfil(success: false, errores: ['La raíz del XML debe ser <preferencias>.']);
        }

        if ($root->getAttribute('version') !== '1') {
            return new ResultadoPerfil(success: false, errores: ['La versión del XML no es compatible.']);
        }

        $username = $this->sanitizarTexto($this->leerTextoNodo($root, 'username'));
        if ($username === '') {
            return new ResultadoPerfil(success: false, errores: ['El XML debe incluir un nombre de usuario válido.']);
        }

        $tema = $this->temaImportable($this->leerTextoNodo($root, 'tema'));
        if ($tema === null) {
            return new ResultadoPerfil(success: false, errores: ['El tema del XML no es válido.']);
        }

        $generos = $this->leerGenerosDesdeXml($root);
        if (count($generos) > self::GENEROS_MAX) {
            return new ResultadoPerfil(success: false, errores: ['Puedes elegir como máximo ' . self::GENEROS_MAX . ' géneros.']);
        }

        $preferences = $usuarioActual->preferences;
        $preferences['generos'] = $generos;
        $preferences['tema'] = $tema;

        $this->usuarios->actualizarPerfil($idUsuario, $username, $preferences);

        return new ResultadoPerfil(success: true, username: $username, preferences: $preferences);
    }

    private function temaExportable(mixed $tema): string
    {
        $temaNormalizado = is_string($tema) ? strtolower(trim($tema)) : 'light';

        return in_array($temaNormalizado, self::TEMAS_VALIDOS, true) ? $temaNormalizado : 'light';
    }

    private function temaImportable(string $tema): ?string
    {
        $temaNormalizado = strtolower(trim($tema));

        return in_array($temaNormalizado, self::TEMAS_VALIDOS, true) ? $temaNormalizado : null;
    }

    private function crearNodoTexto(\DOMDocument $dom, string $nombre, string $valor): \DOMElement
    {
        $nodo = $dom->createElement($nombre);
        $nodo->appendChild($dom->createTextNode($valor));

        return $nodo;
    }

    private function leerTextoNodo(\DOMElement $padre, string $nombre): string
    {
        $nodos = $padre->getElementsByTagName($nombre);
        $nodo = $nodos->item(0);

        return $nodo?->textContent ?? '';
    }

    /**
     * @return list<int>
     */
    private function leerGenerosDesdeXml(\DOMElement $root): array
    {
        $generos = [];

        $nodosGeneros = $root->getElementsByTagName('generos')->item(0);
        if (!$nodosGeneros instanceof \DOMElement) {
            return [];
        }

        foreach ($nodosGeneros->getElementsByTagName('genero') as $nodoGenero) {
            if (!$nodoGenero instanceof \DOMElement) {
                continue;
            }

            $idGenero = (int) $nodoGenero->getAttribute('id');
            if ($idGenero > 0) {
                $generos[] = $idGenero;
            }
        }

        return array_values(array_unique($generos));
    }

    private function cargarXmlSeguro(string $contenidoXml): ?\DOMDocument
    {
        $contenidoXml = trim($contenidoXml);
        if ($contenidoXml === '' || str_contains($contenidoXml, '<!DOCTYPE') || str_contains($contenidoXml, '<!ENTITY')) {
            return null;
        }

        $anterior = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->resolveExternals = false;
        $dom->substituteEntities = false;

        $cargado = $dom->loadXML($contenidoXml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        return $cargado ? $dom : null;
    }
}
