<?php

declare(strict_types=1);

if (!defined('RAIZ_PROYECTO')) {
    define('RAIZ_PROYECTO', dirname(__DIR__));
}

/** @var array<string, mixed> $configuracion */
$configuracion = require RAIZ_PROYECTO . '/config/configuracion.php';
$GLOBALS['configuracion'] = $configuracion;

date_default_timezone_set((string) $configuracion['zona_horaria']);

spl_autoload_register(static function (string $clase): void {
    $ruta = RAIZ_PROYECTO . '/clases/' . $clase . '.php';

    if (is_file($ruta)) {
        require $ruta;
    }
});

require_once RAIZ_PROYECTO . '/includes/funciones.php';
require_once RAIZ_PROYECTO . '/config/conexion.php';
require_once RAIZ_PROYECTO . '/config/acceso.php';
