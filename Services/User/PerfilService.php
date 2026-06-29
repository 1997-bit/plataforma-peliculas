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
     * TODO: generar XML de preferencias (username + generos + tema).
     * Formato esperado (mantenerlo, el WSDL/cliente SOAP lo asume):
     *
     * <preferencias version="1">
     *   <username>...</username>
     *   <tema>...</tema>
     *   <generos><genero id="28"/><genero id="16"/></generos>
     * </preferencias>
     *
     * Usar DOMDocument, no concatenar strings.
     */
    public function exportarSettingsXml(string $idUsuario): string
    {
        throw new \RuntimeException('exportarSettingsXml no implementado todavia.');
    }
}
