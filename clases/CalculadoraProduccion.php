<?php

declare(strict_types=1);

/**
 * @phpstan-type Ingrediente array{materia_prima_id: int, nombre: string, unidad_medida: string, cantidad: float, cantidad_disponible: float}
 */
final class CalculadoraProduccion
{
    public const COMPLETO = 'COMPLETO';
    public const DEPENDE_DE_PEDIDOS = 'DEPENDE_DE_PEDIDOS';
    public const FALTAN_MATERIALES = 'FALTAN_MATERIALES';
    public const SIN_INGREDIENTES = 'SIN_INGREDIENTES';

    private const TOLERANCIA = 0.000001;

    /**
     * @param list<Ingrediente> $ingredientes
     * @param array<int, float> $cantidadesAdicionales
     * @return array{unidades_maximas: ?int, limitante: ?array<string, mixed>, ingredientes: list<array<string, mixed>>}
     */
    public function calcularProduccionMaxima(
        array $ingredientes,
        int $rendimientoUnidades,
        array $cantidadesAdicionales = []
    ): array {
        $this->validarRendimiento($rendimientoUnidades);
        $detalle = [];
        $limitante = null;

        foreach ($ingredientes as $ingrediente) {
            $cantidadConsiderada = max(
                0.0,
                $ingrediente['cantidad_disponible'] + ($cantidadesAdicionales[$ingrediente['materia_prima_id']] ?? 0.0)
            );
            $fila = $ingrediente + [
                'cantidad_considerada' => $cantidadConsiderada,
                'unidades_posibles' => $this->calcularUnidadesPosibles(
                    $cantidadConsiderada,
                    $ingrediente['cantidad'],
                    $rendimientoUnidades
                ),
            ];
            $detalle[] = $fila;

            if ($limitante === null || $fila['unidades_posibles'] < $limitante['unidades_posibles']) {
                $limitante = $fila;
            }
        }

        return [
            'unidades_maximas' => $limitante === null ? null : $limitante['unidades_posibles'],
            'limitante' => $limitante,
            'ingredientes' => $detalle,
        ];
    }

    public function calcularUnidadesPosibles(
        float $cantidadDisponible,
        float $cantidadPorTanda,
        int $rendimientoUnidades
    ): int {
        if ($cantidadPorTanda <= 0) {
            throw new InvalidArgumentException('La cantidad por tanda debe ser mayor que cero.');
        }

        if ($cantidadDisponible <= 0) {
            return 0;
        }

        return (int) floor($cantidadDisponible * $rendimientoUnidades / $cantidadPorTanda + self::TOLERANCIA);
    }

    public function calcularCantidadRequerida(float $cantidadPorTanda, int $rendimientoUnidades, int $unidades): float
    {
        $this->validarRendimiento($rendimientoUnidades);

        return round($unidades * $cantidadPorTanda / $rendimientoUnidades, 2);
    }

    /**
     * @param list<Ingrediente> $ingredientes
     * @return array<int, float>
     */
    public function calcularRequerimientos(array $ingredientes, int $rendimientoUnidades, int $unidades): array
    {
        $requerimientos = [];

        foreach ($ingredientes as $ingrediente) {
            $requerimientos[$ingrediente['materia_prima_id']] = $this->calcularCantidadRequerida(
                $ingrediente['cantidad'],
                $rendimientoUnidades,
                $unidades
            );
        }

        return $requerimientos;
    }

    /**
     * @param list<Ingrediente> $ingredientes
     * @param array<int, float> $cantidadesComprometidas
     * @param array<int, float> $cantidadesPorLlegar
     * @return array{estado: string, ingredientes: list<array<string, mixed>>}
     */
    public function analizarAbastecimiento(
        array $ingredientes,
        int $rendimientoUnidades,
        int $unidades,
        array $cantidadesComprometidas = [],
        array $cantidadesPorLlegar = []
    ): array {
        if ($ingredientes === []) {
            return ['estado' => self::SIN_INGREDIENTES, 'ingredientes' => []];
        }

        $filas = [];

        foreach ($ingredientes as $ingrediente) {
            $materiaPrimaId = $ingrediente['materia_prima_id'];
            $filas[] = $this->analizarIngrediente(
                $ingrediente,
                $this->calcularCantidadRequerida($ingrediente['cantidad'], $rendimientoUnidades, $unidades),
                $cantidadesComprometidas[$materiaPrimaId] ?? 0.0,
                $cantidadesPorLlegar[$materiaPrimaId] ?? 0.0
            );
        }

        return ['estado' => $this->determinarEstado($filas), 'ingredientes' => $filas];
    }

    /**
     * @param Ingrediente $ingrediente
     * @return array<string, mixed>
     */
    private function analizarIngrediente(
        array $ingrediente,
        float $cantidadRequerida,
        float $cantidadComprometida,
        float $cantidadPorLlegar
    ): array {
        $saldoSinPedidos = $ingrediente['cantidad_disponible'] - $cantidadComprometida;
        $saldoConPedidos = $saldoSinPedidos + $cantidadPorLlegar;

        return [
            'materia_prima_id' => $ingrediente['materia_prima_id'],
            'nombre' => $ingrediente['nombre'],
            'unidad_medida' => $ingrediente['unidad_medida'],
            'cantidad_requerida' => $cantidadRequerida,
            'cantidad_disponible' => $ingrediente['cantidad_disponible'],
            'cantidad_comprometida' => $cantidadComprometida,
            'cantidad_por_llegar' => $cantidadPorLlegar,
            'faltante' => $this->calcularFaltante($cantidadRequerida, $saldoConPedidos),
            'faltante_sin_pedidos' => $this->calcularFaltante($cantidadRequerida, $saldoSinPedidos),
        ];
    }

    private function calcularFaltante(float $cantidadRequerida, float $saldoDisponible): float
    {
        $faltante = $cantidadRequerida - max(0.0, $saldoDisponible);

        return round(min($cantidadRequerida, max(0.0, $faltante)), 2);
    }

    /** @param list<array<string, mixed>> $filas */
    private function determinarEstado(array $filas): string
    {
        $tieneFaltantes = false;
        $dependeDePedidos = false;

        foreach ($filas as $fila) {
            $tieneFaltantes = $tieneFaltantes || $fila['faltante'] > self::TOLERANCIA;
            $dependeDePedidos = $dependeDePedidos || $fila['faltante_sin_pedidos'] > self::TOLERANCIA;
        }

        if ($tieneFaltantes) {
            return self::FALTAN_MATERIALES;
        }

        return $dependeDePedidos ? self::DEPENDE_DE_PEDIDOS : self::COMPLETO;
    }

    private function validarRendimiento(int $rendimientoUnidades): void
    {
        if ($rendimientoUnidades <= 0) {
            throw new InvalidArgumentException('El rendimiento de la receta debe ser mayor que cero.');
        }
    }
}
