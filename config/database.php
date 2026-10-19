<?php

declare(strict_types=1);

$dsnConexion = getenv('INVENTARIO_DSN') ?: 'mysql:host=localhost;dbname=sistema_inventario;charset=utf8mb4';
$usuarioConexion = getenv('INVENTARIO_USUARIO') ?: 'root';
$claveConexion = getenv('INVENTARIO_CLAVE') ?: '';
try {
    $conexion = new PDO($dsnConexion, $usuarioConexion, $claveConexion, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $error) {
    error_log('Conexión no disponible: ' . $error->getMessage());
    http_response_code(503);
    exit('No se pudo conectar al sistema. Revise la configuración local de la base de datos.');
}
