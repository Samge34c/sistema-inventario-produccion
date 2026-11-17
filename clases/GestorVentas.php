<?php

declare(strict_types=1);

/**
 * Registro de ventas y reportes diario, semanal y de hora pico.
 *
 * Una venta es una atención en caja (un cliente) y puede llevar varios
 * productos. En los reportes, «ventas» cuenta atenciones y «unidades» cuenta
 * productos vendidos.
 *
 * Hora pico: la franja de una hora con más ventas; si hay empate, la de más
 * unidades, y si sigue el empate, la más temprana.
 */
final class GestorVentas
{
    public function __construct(private readonly PDO $conexion)
    {
    }

    /**
     * Registra una venta con sus productos.
     *
     * @param array<string, mixed> $datos 'cantidades' => [receta_id => unidades],
     *                                    'fecha_hora' (opcional, AAAA-MM-DDTHH:MM),
     *                                    'observacion' (opcional)
     */
    public function registrarVenta(array $datos): int
    {
        $validador = new Validador($datos);
        $observacion = $validador->obtenerTexto('observacion', 'la observación', 255, false);
        $fechaHora = $this->leerFechaHora((string) ($datos['fecha_hora'] ?? ''), $validador);
        $lineas = $this->leerLineas($datos['cantidades'] ?? [], $validador);
        $validador->lanzarSiHayErrores();

        return BaseDatos::ejecutarEnTransaccion($this->conexion, function () use ($fechaHora, $observacion, $lineas): int {
            $this->conexion
                ->prepare('INSERT INTO ventas (fecha_hora, observacion) VALUES (:fecha_hora, :observacion)')
                ->execute([':fecha_hora' => $fechaHora, ':observacion' => $observacion === '' ? null : $observacion]);
            $ventaId = (int) $this->conexion->lastInsertId();
            $insercion = $this->conexion->prepare(
                'INSERT INTO venta_detalles (venta_id, receta_id, cantidad) VALUES (:venta, :receta, :cantidad)'
            );

            foreach ($lineas as $recetaId => $cantidadUnidades) {
                $insercion->execute([':venta' => $ventaId, ':receta' => $recetaId, ':cantidad' => $cantidadUnidades]);
            }

            return $ventaId;
        });
    }

    /**
     * Ventas de un día, la más reciente primero, con sus productos.
     *
     * @return list<array<string, mixed>>
     */
    public function listarVentasDelDia(string $fecha): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT v.id, v.fecha_hora, v.observacion, r.nombre, d.cantidad
               FROM ventas v
               JOIN venta_detalles d ON d.venta_id = v.id
               JOIN recetas r ON r.id = d.receta_id
              WHERE v.fecha_hora >= :inicio AND v.fecha_hora < :fin
              ORDER BY v.fecha_hora DESC, v.id DESC, r.nombre'
        );
        $consulta->execute([':inicio' => $fecha . ' 00:00:00', ':fin' => sumarDias($fecha, 1) . ' 00:00:00']);
        $ventas = [];

        foreach ($consulta->fetchAll() as $fila) {
            $id = (int) $fila['id'];
            $ventas[$id] ??= [
                'id' => $id,
                'fecha_hora' => $fila['fecha_hora'],
                'observacion' => $fila['observacion'],
                'productos' => [],
                'unidades' => 0,
            ];
            $ventas[$id]['productos'][] = ['nombre' => $fila['nombre'], 'cantidad' => (int) $fila['cantidad']];
            $ventas[$id]['unidades'] += (int) $fila['cantidad'];
        }

        return array_values($ventas);
    }

    public function anularVenta(int $id): void
    {
        $consulta = $this->conexion->prepare('DELETE FROM ventas WHERE id = :id');
        $consulta->execute([':id' => $id]);

        if ($consulta->rowCount() === 0) {
            throw new ErrorValidacion(['general' => 'La venta no existe o ya fue anulada.']);
        }
    }

    /**
     * @return array{transacciones: int, unidades: int}
     */
    public function obtenerResumenDia(string $fecha): array
    {
        return $this->consultarTotales($fecha . ' 00:00:00', sumarDias($fecha, 1) . ' 00:00:00');
    }

    /**
     * Unidades vendidas por día, en promedio, de cada producto durante los
     * últimos $dias días completos (sin contar hoy). Sirve para decidir
     * cuánto planificar.
     *
     * @return array<int, float> receta_id => promedio de unidades por día
     */
    public function calcularPromedioDiarioPorProducto(int $dias = 7): array
    {
        $hoy = obtenerFechaHoy();
        $consulta = $this->conexion->prepare(
            'SELECT d.receta_id, SUM(d.cantidad) AS unidades
               FROM ventas v
               JOIN venta_detalles d ON d.venta_id = v.id
              WHERE v.fecha_hora >= :inicio AND v.fecha_hora < :fin
              GROUP BY d.receta_id'
        );
        $consulta->execute([':inicio' => sumarDias($hoy, -$dias) . ' 00:00:00', ':fin' => $hoy . ' 00:00:00']);
        $promedios = [];

        foreach ($consulta->fetchAll() as $fila) {
            $promedios[(int) $fila['receta_id']] = (float) $fila['unidades'] / $dias;
        }

        return $promedios;
    }

    // -----------------------------------------------------------------------
    // Reportes
    // -----------------------------------------------------------------------

    /**
     * Reporte de un día, comparado con el mismo día de la semana anterior.
     *
     * @return array<string, mixed>
     */
    public function generarReporteDiario(string $fecha): array
    {
        $inicio = $fecha . ' 00:00:00';
        $fin = sumarDias($fecha, 1) . ' 00:00:00';
        $fechaComparacion = sumarDias($fecha, -7);
        $porHora = $this->consultarPorHora($inicio, $fin);

        return [
            'fecha' => $fecha,
            'resumen' => $this->consultarTotales($inicio, $fin),
            'fecha_comparacion' => $fechaComparacion,
            'resumen_comparacion' => $this->obtenerResumenDia($fechaComparacion),
            'por_producto' => $this->consultarPorProducto($inicio, $fin),
            'por_hora' => $porHora,
            'hora_pico' => $this->determinarHoraPico($porHora),
        ];
    }

    /**
     * Reporte de lunes a domingo, comparado con la semana anterior.
     *
     * @return array<string, mixed>
     */
    public function generarReporteSemanal(string $fechaCualquiera): array
    {
        $lunes = obtenerLunesDeSemana($fechaCualquiera);
        $domingo = sumarDias($lunes, 6);
        $inicio = $lunes . ' 00:00:00';
        $fin = sumarDias($lunes, 7) . ' 00:00:00';
        $totalesPorDia = $this->consultarPorDia($inicio, $fin);
        $porDia = [];

        for ($indice = 0; $indice < 7; $indice++) {
            $dia = sumarDias($lunes, $indice);
            $porDia[$dia] = $totalesPorDia[$dia] ?? ['transacciones' => 0, 'unidades' => 0];
        }

        $mejorDia = null;

        foreach ($porDia as $dia => $totales) {
            if ($totales['unidades'] > 0 && ($mejorDia === null || $totales['unidades'] > $porDia[$mejorDia]['unidades'])) {
                $mejorDia = $dia;
            }
        }

        return [
            'inicio' => $lunes,
            'fin' => $domingo,
            'resumen' => $this->consultarTotales($inicio, $fin),
            'resumen_anterior' => $this->consultarTotales(sumarDias($lunes, -7) . ' 00:00:00', $inicio),
            'por_dia' => $porDia,
            'mejor_dia' => $mejorDia,
            'por_producto' => $this->consultarPorProducto($inicio, $fin),
        ];
    }

    /**
     * Ventas por franja horaria en un rango de días, con promedio por día y
     * el cruce día de la semana × hora.
     *
     * @return array<string, mixed>
     */
    public function generarReporteHoraPico(string $desde, string $hasta): array
    {
        $inicio = $desde . ' 00:00:00';
        $fin = sumarDias($hasta, 1) . ' 00:00:00';
        $totalDias = max(1, calcularDiferenciaDias($desde, $hasta) + 1);
        $porHora = $this->consultarPorHora($inicio, $fin);

        foreach ($porHora as $hora => $totales) {
            $porHora[$hora]['promedio_transacciones'] = (float) $totales['transacciones'] / $totalDias;
            $porHora[$hora]['promedio_unidades'] = (float) $totales['unidades'] / $totalDias;
        }

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'dias' => $totalDias,
            'resumen' => $this->consultarTotales($inicio, $fin),
            'por_hora' => $porHora,
            'hora_pico' => $this->determinarHoraPico($porHora),
            'mapa' => $this->consultarMapaDiaHora($inicio, $fin),
        ];
    }

    /**
     * @param array<int, array{transacciones: int, unidades: int}> $porHora
     * @return array{hora: int, transacciones: int, unidades: int}|null
     */
    public function determinarHoraPico(array $porHora): ?array
    {
        $horaPico = null;

        foreach ($porHora as $hora => $totales) {
            if ($totales['transacciones'] === 0) {
                continue;
            }

            if ($horaPico === null || $this->esMayorActividad($totales, $horaPico)) {
                $horaPico = ['hora' => $hora, 'transacciones' => $totales['transacciones'], 'unidades' => $totales['unidades']];
            }
        }

        return $horaPico;
    }

    // -----------------------------------------------------------------------
    // Consultas
    // -----------------------------------------------------------------------

    /**
     * @return array{transacciones: int, unidades: int}
     */
    private function consultarTotales(string $inicio, string $fin): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(DISTINCT v.id) AS transacciones, COALESCE(SUM(d.cantidad), 0) AS unidades
               FROM ventas v
               JOIN venta_detalles d ON d.venta_id = v.id
              WHERE v.fecha_hora >= :inicio AND v.fecha_hora < :fin'
        );
        $consulta->execute([':inicio' => $inicio, ':fin' => $fin]);
        $fila = $consulta->fetch();

        return ['transacciones' => (int) $fila['transacciones'], 'unidades' => (int) $fila['unidades']];
    }

    /**
     * @return list<array{receta_id: int, nombre: string, unidades: int, transacciones: int, porcentaje: float}>
     */
    private function consultarPorProducto(string $inicio, string $fin): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT r.id, r.nombre, SUM(d.cantidad) AS unidades, COUNT(DISTINCT v.id) AS transacciones
               FROM ventas v
               JOIN venta_detalles d ON d.venta_id = v.id
               JOIN recetas r ON r.id = d.receta_id
              WHERE v.fecha_hora >= :inicio AND v.fecha_hora < :fin
              GROUP BY r.id, r.nombre
              ORDER BY unidades DESC, r.nombre'
        );
        $consulta->execute([':inicio' => $inicio, ':fin' => $fin]);
        $filas = $consulta->fetchAll();
        $totalUnidades = array_sum(array_map(static fn (array $fila): int => (int) $fila['unidades'], $filas));

        return array_map(static fn (array $fila): array => [
            'receta_id' => (int) $fila['id'],
            'nombre' => (string) $fila['nombre'],
            'unidades' => (int) $fila['unidades'],
            'transacciones' => (int) $fila['transacciones'],
            'porcentaje' => $totalUnidades > 0 ? (float) $fila['unidades'] * 100 / $totalUnidades : 0.0,
        ], $filas);
    }

    /**
     * @return array<int, array{transacciones: int, unidades: int}> hora (0-23) => totales, solo horas con ventas
     */
    private function consultarPorHora(string $inicio, string $fin): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT HOUR(v.fecha_hora) AS hora, COUNT(DISTINCT v.id) AS transacciones, SUM(d.cantidad) AS unidades
               FROM ventas v
               JOIN venta_detalles d ON d.venta_id = v.id
              WHERE v.fecha_hora >= :inicio AND v.fecha_hora < :fin
              GROUP BY HOUR(v.fecha_hora)
              ORDER BY hora'
        );
        $consulta->execute([':inicio' => $inicio, ':fin' => $fin]);
        $porHora = [];

        foreach ($consulta->fetchAll() as $fila) {
            $porHora[(int) $fila['hora']] = [
                'transacciones' => (int) $fila['transacciones'],
                'unidades' => (int) $fila['unidades'],
            ];
        }

        return $porHora;
    }

    /**
     * @return array<string, array{transacciones: int, unidades: int}> fecha => totales
     */
    private function consultarPorDia(string $inicio, string $fin): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT DATE(v.fecha_hora) AS dia, COUNT(DISTINCT v.id) AS transacciones, SUM(d.cantidad) AS unidades
               FROM ventas v
               JOIN venta_detalles d ON d.venta_id = v.id
              WHERE v.fecha_hora >= :inicio AND v.fecha_hora < :fin
              GROUP BY DATE(v.fecha_hora)'
        );
        $consulta->execute([':inicio' => $inicio, ':fin' => $fin]);
        $porDia = [];

        foreach ($consulta->fetchAll() as $fila) {
            $porDia[(string) $fila['dia']] = [
                'transacciones' => (int) $fila['transacciones'],
                'unidades' => (int) $fila['unidades'],
            ];
        }

        return $porDia;
    }

    /**
     * Ventas por día de la semana (1 = lunes … 7 = domingo) y hora.
     *
     * @return array<int, array<int, int>>
     */
    private function consultarMapaDiaHora(string $inicio, string $fin): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT WEEKDAY(fecha_hora) + 1 AS dia_semana, HOUR(fecha_hora) AS hora, COUNT(*) AS transacciones
               FROM ventas
              WHERE fecha_hora >= :inicio AND fecha_hora < :fin
              GROUP BY dia_semana, hora'
        );
        $consulta->execute([':inicio' => $inicio, ':fin' => $fin]);
        $mapa = [];

        foreach ($consulta->fetchAll() as $fila) {
            $mapa[(int) $fila['dia_semana']][(int) $fila['hora']] = (int) $fila['transacciones'];
        }

        return $mapa;
    }

    // -----------------------------------------------------------------------
    // Apoyo
    // -----------------------------------------------------------------------

    /**
     * @param array{transacciones: int, unidades: int} $candidata
     * @param array{hora: int, transacciones: int, unidades: int} $actual
     */
    private function esMayorActividad(array $candidata, array $actual): bool
    {
        if ($candidata['transacciones'] !== $actual['transacciones']) {
            return $candidata['transacciones'] > $actual['transacciones'];
        }

        return $candidata['unidades'] > $actual['unidades'];
    }

    /**
     * Fecha y hora de la venta. Vacía significa «ahora»; no se aceptan
     * fechas futuras.
     */
    private function leerFechaHora(string $texto, Validador $validador): string
    {
        $texto = trim($texto);

        if ($texto === '') {
            return date('Y-m-d H:i:s');
        }

        $fechaHora = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $texto);

        if ($fechaHora === false || $fechaHora->format('Y-m-d\TH:i') !== $texto) {
            $validador->agregarError('fecha_hora', 'Elija una fecha y hora válidas.');

            return '';
        }

        if ($fechaHora > new DateTimeImmutable('+1 minute')) {
            $validador->agregarError('fecha_hora', 'La venta no puede quedar con una hora futura.');
        }

        return $fechaHora->format('Y-m-d H:i:s');
    }

    /**
     * @return array<int, int> receta_id => unidades
     */
    private function leerLineas(mixed $cantidades, Validador $validador): array
    {
        $lineas = [];
        $recetasActivas = array_map('intval', $this->conexion->query('SELECT id FROM recetas WHERE activa = 1')->fetchAll(PDO::FETCH_COLUMN));

        foreach (is_array($cantidades) ? $cantidades : [] as $recetaId => $texto) {
            $texto = trim((string) $texto);

            if ($texto === '' || $texto === '0') {
                continue;
            }

            $cantidadUnidades = filter_var($texto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]]);

            if ($cantidadUnidades === false || !in_array((int) $recetaId, $recetasActivas, true)) {
                $validador->agregarError('cantidades', 'Revise las cantidades: deben ser números enteros entre 1 y 10.000.');
                continue;
            }

            $lineas[(int) $recetaId] = $cantidadUnidades;
        }

        if ($lineas === []) {
            $validador->agregarError('cantidades', 'Agregue al menos un producto a la venta.');
        }

        return $lineas;
    }
}
