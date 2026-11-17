<?php

declare(strict_types=1);

/*
 * Carga lo que necesita cualquier parte del sistema, incluidas las pruebas:
 * configuración, zona horaria, clases, funciones de apoyo y de acceso. No
 * abre sesión ni conexión; eso lo hace includes/inicio.php para las páginas web.
 */

if (!defined('RAIZ_PROYECTO')) {
    define('RAIZ_PROYECTO', dirname(__DIR__));
}

/** @var array<string, mixed> $configuracion */
$configuracion = require RAIZ_PROYECTO . '/config/configuracion.php';
// Se guarda también como global: si este archivo se incluye dentro de una
// función (PHPUnit 10+ lo hace con el bootstrap), $configuracion sería local.
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
