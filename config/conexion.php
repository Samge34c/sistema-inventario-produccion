<?php

declare(strict_types=1);

/**
 * Crea la conexión PDO a MySQL con los datos de config/configuracion.php.
 *
 * Si la configuración trae un DSN completo (variable INVENTARIO_DSN), se usa
 * tal cual; si no, se arma con host, puerto y nombre de la base. La sesión de
 * MySQL usa la misma zona horaria que PHP, para que NOW() y CURDATE()
 * coincidan con las fechas que muestra la aplicación.
 *
 * @param array<string, mixed> $configuracion
 */
function crearConexion(array $configuracion): PDO
{
    $dsn = is_string($configuracion['bd_dsn'] ?? null) && $configuracion['bd_dsn'] !== ''
        ? $configuracion['bd_dsn']
        : sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $configuracion['bd_host'],
            $configuracion['bd_puerto'],
            $configuracion['bd_nombre']
        );

    $conexion = new PDO($dsn, $configuracion['bd_usuario'], $configuracion['bd_contrasena'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $desfaseHorario = (new DateTimeImmutable())->format('P');
    $conexion->exec("SET time_zone = '" . $desfaseHorario . "'");

    return $conexion;
}
