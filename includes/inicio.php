<?php

declare(strict_types=1);

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
