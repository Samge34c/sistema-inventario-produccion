<?php

declare(strict_types=1);

$configuracion = [
    'bd_dsn' => getenv('INVENTARIO_DSN') ?: null,
    'bd_host' => getenv('BD_HOST') ?: 'localhost',
    'bd_puerto' => (int) (getenv('BD_PUERTO') ?: 3306),
    'bd_nombre' => getenv('BD_NOMBRE') ?: 'sistema_inventario',
    'bd_usuario' => getenv('INVENTARIO_USUARIO') ?: (getenv('BD_USUARIO') ?: 'root'),
    'bd_contrasena' => getenv('INVENTARIO_CLAVE') ?: (getenv('BD_CONTRASENA') ?: ''),
    'zona_horaria' => 'America/Bogota',
    'nombre_negocio' => 'Panadería',
    'mostrar_detalle_errores' => true,
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
