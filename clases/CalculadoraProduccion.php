<?php

declare(strict_types=1);

/**
 * Cálculos de producción sin acceso a base de datos: producción máxima,
 * ingrediente limitante, materiales requeridos y faltantes.
 *
 * Convenciones:
 * - Cada ingrediente trae la cantidad que pide UNA tanda de la receta, en la
 *   unidad de medida de su materia prima.
 * - El rendimiento es la cantidad de unidades de producto que salen de una tanda.
 * - Las cantidades se escalan en proporción: para 60 unidades de una receta
 *   que rinde 30, se necesita el doble de cada ingrediente.
 *
 * Formato de un ingrediente:
 *   ['materia_prima_id' => int, 'nombre' => string, 'unidad_medida' => string,
 *    'cantidad' => float, 'cantidad_disponible' => float]
 *
 * @phpstan-type Ingrediente array{materia_prima_id: int, nombre: string, unidad_medida: string, cantidad: float, cantidad_disponible: float}
 */
final class CalculadoraProduccion
{
    public const COMPLETO = 'COMPLETO';
    public const DEPENDE_DE_PEDIDOS = 'DEPENDE_DE_PEDIDOS';
    public const FALTAN_MATERIALES = 'FALTAN_MATERIALES';
    public const SIN_INGREDIENTES = 'SIN_INGREDIENTES';

    /** Margen para errores de redondeo de punto flotante. */
    private const TOLERANCIA = 0.000001;

    /**
     * Unidades máximas que se pueden producir con lo disponible y cuál
     * ingrediente lo limita.
     *
     * @param list<Ingrediente> $ingredientes
     * @param array<int, float> $cantidadesAdicionales materia_prima_id => cantidad extra
     *                                                  (por ejemplo, pedidos por llegar)
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

    /**
     * Unidades completas de producto que alcanzan con una cantidad de un ingrediente.
     */
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

    /**
     * Cantidad de un ingrediente que se necesita para producir $unidades.
     */
    public function calcularCantidadRequerida(float $cantidadPorTanda, int $rendimientoUnidades, int $unidades): float
    {
        $this->validarRendimiento($rendimientoUnidades);

        return round($unidades * $cantidadPorTanda / $rendimientoUnidades, 2);
    }

    /**
     * @param list<Ingrediente> $ingredientes
     * @return array<int, float> materia_prima_id => cantidad requerida
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
     * Revisa si un plan de producción tiene los materiales que necesita.
     *
     * Para cada ingrediente compara lo requerido contra:
     *   existencia actual − lo comprometido por planes anteriores + pedidos que llegan hasta la fecha.
     *
     * Estados posibles:
     * - COMPLETO: alcanza con lo que hay hoy.
     * - DEPENDE_DE_PEDIDOS: alcanza solo si llegan los pedidos pendientes.
     * - FALTAN_MATERIALES: no alcanza ni contando los pedidos.
     *
     * @param list<Ingrediente> $ingredientes
     * @param array<int, float> $cantidadesComprometidas materia_prima_id => lo que piden planes anteriores
     * @param array<int, float> $cantidadesPorLlegar     materia_prima_id => pedidos que llegan a tiempo
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

    /**
     * Lo que le falta a este plan, entre cero y lo requerido.
     */
    private function calcularFaltante(float $cantidadRequerida, float $saldoDisponible): float
    {
        $faltante = $cantidadRequerida - max(0.0, $saldoDisponible);

        return round(min($cantidadRequerida, max(0.0, $faltante)), 2);
    }

    /**
     * @param list<array<string, mixed>> $filas
     */
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
