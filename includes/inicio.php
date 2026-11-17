<?php

declare(strict_types=1);

/*
 * Punto de arranque de todas las páginas web: carga el sistema, abre la
 * sesión segura, exige una cuenta activa y muestra una página amable si
 * ocurre un error inesperado. Cada página obtiene la base de datos con
 * obtenerConexion().
 *
 * Una página que no requiere sesión (solo iniciar-sesion.php) define la
 * constante PAGINA_PUBLICA antes de incluir este archivo.
 */

require_once __DIR__ . '/cargador.php';

iniciarSesionSegura();

set_exception_handler(static function (Throwable $error): void {
    error_log((string) $error);
    mostrarPaginaError(
        'Algo salió mal',
        'Ocurrió un error inesperado y la operación no se completó. Vuelva a intentarlo; '
            . 'si el problema continúa, avise al equipo de soporte con la hora en que pasó.',
        obtenerConfiguracion('mostrar_detalle_errores') ? (string) $error : null
    );
});

if (!defined('PAGINA_PUBLICA')) {
    exigirAcceso();
}
