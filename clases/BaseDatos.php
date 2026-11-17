<?php

declare(strict_types=1);

/**
 * Utilidades comunes para trabajar con la base de datos.
 */
final class BaseDatos
{
    /**
     * Ejecuta la operación dentro de una transacción. Si ya hay una abierta
     * (por ejemplo, registrar una producción que a su vez registra salidas de
     * inventario), se une a ella en lugar de abrir otra.
     *
     * @template T
     * @param callable(): T $operacion
     * @return T
     */
    public static function ejecutarEnTransaccion(PDO $conexion, callable $operacion): mixed
    {
        if ($conexion->inTransaction()) {
            return $operacion();
        }

        $conexion->beginTransaction();

        try {
            $resultado = $operacion();
            $conexion->commit();

            return $resultado;
        } catch (Throwable $error) {
            $conexion->rollBack();
            throw $error;
        }
    }

    /**
     * Arma «:p0, :p1, …» y los valores para una consulta con IN (...).
     *
     * @param list<int> $valores
     * @return array{0: string, 1: array<string, int>}
     */
    public static function prepararListaIn(array $valores, string $prefijo = 'p'): array
    {
        $marcadores = [];
        $parametros = [];

        foreach ($valores as $indice => $valor) {
            $marcador = ':' . $prefijo . $indice;
            $marcadores[] = $marcador;
            $parametros[$marcador] = $valor;
        }

        return [implode(', ', $marcadores), $parametros];
    }
}
