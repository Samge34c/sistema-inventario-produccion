<?php

declare(strict_types=1);

final class BaseDatos
{
    /**
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
