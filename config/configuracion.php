<?php

declare(strict_types=1);

/*
 * Configuración general del sistema.
 *
 * Los valores por defecto sirven para XAMPP recién instalado (usuario root sin
 * contraseña). Para usar otros valores sin modificar este archivo, copie
 * config/configuracion.local.ejemplo.php como config/configuracion.local.php
 * y cambie allí lo que necesite. Ese archivo está en .gitignore, así que las
 * contraseñas reales nunca llegan al repositorio.
 *
 * También se pueden usar variables de entorno. INVENTARIO_DSN,
 * INVENTARIO_USUARIO e INVENTARIO_CLAVE (las del README de HU02) tienen
 * prioridad; BD_HOST, BD_PUERTO, BD_NOMBRE, BD_USUARIO y BD_CONTRASENA
 * sirven cuando no se quiere escribir el DSN completo.
 */

$configuracion = [
    // DSN completo, por ejemplo 'mysql:host=localhost;dbname=sistema_inventario;charset=utf8mb4'.
    // Si se deja en null, se arma con bd_host, bd_puerto y bd_nombre.
    'bd_dsn' => getenv('INVENTARIO_DSN') ?: null,
    'bd_host' => getenv('BD_HOST') ?: 'localhost',
    'bd_puerto' => (int) (getenv('BD_PUERTO') ?: 3306),
    'bd_nombre' => getenv('BD_NOMBRE') ?: 'sistema_inventario',
    'bd_usuario' => getenv('INVENTARIO_USUARIO') ?: (getenv('BD_USUARIO') ?: 'root'),
    'bd_contrasena' => getenv('INVENTARIO_CLAVE') ?: (getenv('BD_CONTRASENA') ?: ''),
    'zona_horaria' => 'America/Bogota',
    'nombre_negocio' => 'Panadería',
    // Muestra el detalle técnico de los errores. Póngalo en false si el
    // sistema queda publicado en un servidor accesible por otras personas.
    'mostrar_detalle_errores' => true,
    // Ruta web del proyecto, por ejemplo '/sistema-inventario-produccion'.
    // Si se deja en null, se calcula sola.
    'url_base' => null,
];

$rutaConfiguracionLocal = __DIR__ . '/configuracion.local.php';

if (is_file($rutaConfiguracionLocal)) {
    $configuracionLocal = require $rutaConfiguracionLocal;

    if (is_array($configuracionLocal)) {
        $configuracion = array_merge($configuracion, $configuracionLocal);
    }
}

return $configuracion;
