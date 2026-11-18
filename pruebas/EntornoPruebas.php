<?php

declare(strict_types=1);

final class EntornoPruebas
{
    private static ?PDO $conexion = null;
    private static bool $estaIntentado = false;

    public static function obtenerConexion(): ?PDO
    {
        if (self::$estaIntentado) {
            return self::$conexion;
        }

        self::$estaIntentado = true;
        $configuracion = $GLOBALS['configuracion'];
        [$dsnServidor, $nombreBase] = self::separarDsn($configuracion);
        $nombrePruebas = $nombreBase . '_pruebas';

        try {
            $servidor = new PDO(
                $dsnServidor,
                $configuracion['bd_usuario'],
                $configuracion['bd_contrasena'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $servidor->exec("DROP DATABASE IF EXISTS `$nombrePruebas`");
            $esquema = (string) file_get_contents(RAIZ_PROYECTO . '/database/schema.sql');
            $servidor->exec(str_replace('sistema_inventario', $nombrePruebas, $esquema));
            self::$conexion = crearConexion(['bd_dsn' => $dsnServidor . ';dbname=' . $nombrePruebas] + $configuracion);
        } catch (PDOException) {
            self::$conexion = null;
        }

        return self::$conexion;
    }

    /**
     * @param array<string, mixed> $configuracion
     * @return array{0: string, 1: string}
     */
    private static function separarDsn(array $configuracion): array
    {
        $dsn = $configuracion['bd_dsn'] ?? null;

        if (!is_string($dsn) || $dsn === '') {
            return [
                sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $configuracion['bd_host'], $configuracion['bd_puerto']),
                (string) $configuracion['bd_nombre'],
            ];
        }

        $nombreBase = preg_match('/dbname=([^;]+)/', $dsn, $coincidencia) === 1 ? $coincidencia[1] : (string) $configuracion['bd_nombre'];

        return [rtrim((string) preg_replace('/dbname=[^;]*;?/', '', $dsn), ';'), $nombreBase];
    }

    /** @return array{harina: int, levadura: int, huevos: int, pan: int, galleta: int} */
    public static function cargarDatosBase(PDO $conexion): array
    {
        $conexion->exec('SET FOREIGN_KEY_CHECKS = 0');

        foreach (['venta_detalles', 'ventas', 'producciones', 'materiales_pendientes', 'movimientos_inventario',
            'receta_ingredientes', 'recetas', 'materias_primas'] as $tabla) {
            $conexion->exec("TRUNCATE TABLE $tabla");
        }

        $conexion->exec('SET FOREIGN_KEY_CHECKS = 1');
        $conexion->exec(
            "INSERT INTO materias_primas (id, nombre, unidad_medida, cantidad_disponible, stock_minimo) VALUES
                (1, 'Harina de trigo', 'g', 10000, 2000),
                (2, 'Levadura seca', 'g', 100, 50),
                (3, 'Huevos', 'unidad', 12, 6)"
        );
        $conexion->exec(
            "INSERT INTO recetas (id, nombre, cantidad_producir) VALUES (1, 'Pan', 20), (2, 'Galleta', 10)"
        );
        $conexion->exec(
            'INSERT INTO receta_ingredientes (receta_id, materia_prima_id, cantidad) VALUES
                (1, 1, 1000), (1, 2, 20), (2, 1, 500), (2, 3, 2)'
        );

        return ['harina' => 1, 'levadura' => 2, 'huevos' => 3, 'pan' => 1, 'galleta' => 2];
    }

    public static function obtenerExistencia(PDO $conexion, int $materiaPrimaId): float
    {
        $consulta = $conexion->prepare('SELECT cantidad_disponible FROM materias_primas WHERE id = :id');
        $consulta->execute([':id' => $materiaPrimaId]);

        return (float) $consulta->fetchColumn();
    }
}
