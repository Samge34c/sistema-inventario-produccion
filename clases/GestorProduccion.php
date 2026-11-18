<?php

declare(strict_types=1);

final class GestorProduccion
{
    private readonly CalculadoraProduccion $calculadora;
    private readonly GestorInventario $inventario;
    private readonly GestorPedidos $pedidos;
    private readonly GestorRecetas $recetas;

    public function __construct(private readonly PDO $conexion)
    {
        $this->calculadora = new CalculadoraProduccion();
        $this->inventario = new GestorInventario($conexion);
        $this->pedidos = new GestorPedidos($conexion);
        $this->recetas = new GestorRecetas($conexion);
    }

    /** @return list<array<string, mixed>> */
    public function listarProducciones(string $desde, string $hasta, string $estado = '', bool $incluirAtrasadas = true): array
    {
        $condicionFecha = 'p.fecha_planeada BETWEEN :desde AND :hasta';
        $parametros = [':desde' => $desde, ':hasta' => $hasta];

        if ($incluirAtrasadas) {
            $condicionFecha = "($condicionFecha OR (p.estado = 'PLANEADA' AND p.fecha_planeada < :hoy))";
            $parametros[':hoy'] = obtenerFechaHoy();
        }

        $sql = "SELECT p.*, r.nombre AS receta, r.cantidad_producir
                  FROM producciones p
                  JOIN recetas r ON r.id = p.receta_id
                 WHERE $condicionFecha";

        if (in_array($estado, ['PLANEADA', 'REALIZADA', 'CANCELADA'], true)) {
            $sql .= ' AND p.estado = :estado';
            $parametros[':estado'] = $estado;
        }

        $consulta = $this->conexion->prepare($sql . ' ORDER BY p.fecha_planeada, p.id');
        $consulta->execute($parametros);
        $analisis = $this->analizarPlanesPendientes();

        return array_map(
            fn (array $produccion): array => $this->completarProduccion($produccion, $analisis),
            $consulta->fetchAll()
        );
    }

    /** @return array<string, mixed>|null */
    public function obtenerProduccion(int $id): ?array
    {
        $consulta = $this->conexion->prepare(
            'SELECT p.*, r.nombre AS receta, r.cantidad_producir, r.activa AS receta_activa
               FROM producciones p
               JOIN recetas r ON r.id = p.receta_id
              WHERE p.id = :id'
        );
        $consulta->execute([':id' => $id]);
        $produccion = $consulta->fetch();

        if ($produccion === false) {
            return null;
        }

        $produccion = $this->completarProduccion($produccion, $this->analizarPlanesPendientes());
        $ingredientes = $this->recetas->listarIngredientesPorReceta([$produccion['receta_id']])[$produccion['receta_id']] ?? [];
        $produccion['ingredientes'] = $ingredientes;
        $produccion['capacidad_actual'] = $this->calculadora->calcularProduccionMaxima(
            $ingredientes,
            $produccion['cantidad_producir']
        );
        $produccion['consumos'] = $produccion['estado'] === 'REALIZADA' ? $this->listarConsumos($id) : [];

        return $produccion;
    }

    /** @return list<array<string, mixed>> */
    public function listarUltimasProduccionesDeReceta(int $recetaId, int $limite = 5): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT id, fecha_planeada, cantidad_planeada, cantidad_producida, estado
               FROM producciones
              WHERE receta_id = :receta
              ORDER BY fecha_planeada DESC, id DESC
              LIMIT :limite'
        );
        $consulta->bindValue(':receta', $recetaId, PDO::PARAM_INT);
        $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    /** @return array<int, array{estado: string, ingredientes: list<array<string, mixed>>}> */
    public function analizarPlanesPendientes(): array
    {
        $planes = $this->conexion
            ->query(
                "SELECT p.id, p.receta_id, p.fecha_planeada, p.cantidad_planeada, r.cantidad_producir
                   FROM producciones p
                   JOIN recetas r ON r.id = p.receta_id
                  WHERE p.estado = 'PLANEADA'
                  ORDER BY p.fecha_planeada, p.id"
            )
            ->fetchAll();

        if ($planes === []) {
            return [];
        }

        $ingredientesPorReceta = $this->recetas->listarIngredientesPorReceta(
            array_values(array_unique(array_map(static fn (array $plan): int => (int) $plan['receta_id'], $planes)))
        );
        $llegadas = $this->pedidos->listarLlegadasPendientes();
        $indiceLlegada = 0;
        $cantidadesPorLlegar = [];
        $cantidadesComprometidas = [];
        $resultado = [];

        foreach ($planes as $plan) {
            // Suma los pedidos que llegan hasta la fecha de este plan.
            while (isset($llegadas[$indiceLlegada]) && $llegadas[$indiceLlegada]['fecha_estimada_llegada'] <= $plan['fecha_planeada']) {
                $llegada = $llegadas[$indiceLlegada++];
                $cantidadesPorLlegar[$llegada['materia_prima_id']] =
                    ($cantidadesPorLlegar[$llegada['materia_prima_id']] ?? 0.0) + $llegada['cantidad'];
            }

            $ingredientes = $ingredientesPorReceta[(int) $plan['receta_id']] ?? [];
            $analisis = $this->calculadora->analizarAbastecimiento(
                $ingredientes,
                (int) $plan['cantidad_producir'],
                (int) $plan['cantidad_planeada'],
                $cantidadesComprometidas,
                $cantidadesPorLlegar
            );
            $resultado[(int) $plan['id']] = $analisis;

            foreach ($analisis['ingredientes'] as $fila) {
                $cantidadesComprometidas[$fila['materia_prima_id']] =
                    ($cantidadesComprometidas[$fila['materia_prima_id']] ?? 0.0) + $fila['cantidad_requerida'];
            }
        }

        return $resultado;
    }

    /** @return list<array<string, mixed>> */
    public function resumirMaterialesPorFecha(string $fecha): array
    {
        $planesDelDia = array_filter(
            $this->listarProducciones($fecha, $fecha, 'PLANEADA', false),
            static fn (array $produccion): bool => $produccion['analisis'] !== null
        );
        $resumen = [];

        foreach ($planesDelDia as $plan) {
            foreach ($plan['analisis']['ingredientes'] as $fila) {
                $id = $fila['materia_prima_id'];
                $resumen[$id] ??= $fila + ['planes' => 0];
                $resumen[$id]['planes']++;

                if ($resumen[$id]['planes'] > 1) {
                    $resumen[$id]['cantidad_requerida'] += $fila['cantidad_requerida'];
                    $resumen[$id]['faltante'] += $fila['faltante'];
                    $resumen[$id]['faltante_sin_pedidos'] += $fila['faltante_sin_pedidos'];
                }
            }
        }

        usort($resumen, static fn (array $a, array $b): int => strcmp($a['nombre'], $b['nombre']));

        return $resumen;
    }

    /** @param array<string, mixed> $datos */
    public function planificarProduccion(array $datos, ?int $id = null): int
    {
        $produccionActual = $id === null ? null : $this->obtenerPlanPendiente($id);
        $esLaMismaFecha = $produccionActual !== null && ($datos['fecha_planeada'] ?? '') === $produccionActual['fecha_planeada'];
        $validador = new Validador($datos);
        $recetaId = $validador->obtenerId('receta_id', 'la receta');
        $fechaPlaneada = $validador->obtenerFecha('fecha_planeada', 'la fecha', $esLaMismaFecha ? null : obtenerFechaHoy());
        $cantidadPlaneada = $validador->obtenerEntero('cantidad_planeada', 'la cantidad', 1, 100000);
        $observacion = $validador->obtenerTexto('observacion', 'la observación', 255, false);
        $this->validarRecetaPlanificable($validador, $recetaId, $produccionActual);
        $validador->lanzarSiHayErrores();

        $parametros = [
            ':receta' => $recetaId,
            ':fecha' => $fechaPlaneada,
            ':cantidad' => $cantidadPlaneada,
            ':observacion' => $observacion === '' ? null : $observacion,
        ];

        if ($id === null) {
            $this->conexion
                ->prepare(
                    'INSERT INTO producciones (receta_id, fecha_planeada, cantidad_planeada, observacion)
                     VALUES (:receta, :fecha, :cantidad, :observacion)'
                )
                ->execute($parametros);

            return (int) $this->conexion->lastInsertId();
        }

        $this->conexion
            ->prepare(
                "UPDATE producciones
                    SET receta_id = :receta, fecha_planeada = :fecha, cantidad_planeada = :cantidad, observacion = :observacion
                  WHERE id = :id AND estado = 'PLANEADA'"
            )
            ->execute($parametros + [':id' => $id]);

        return $id;
    }

    /** @param array<string, mixed> $datos */
    public function registrarProduccion(int $id, array $datos): void
    {
        $validador = new Validador($datos);
        $unidadesProducidas = $validador->obtenerEntero('unidades_producidas', 'las unidades producidas', 1, 100000);
        $observacion = $validador->obtenerTexto('observacion', 'la observación', 255, false);
        $validador->lanzarSiHayErrores();

        BaseDatos::ejecutarEnTransaccion($this->conexion, function () use ($id, $unidadesProducidas, $observacion): void {
            $plan = $this->bloquearPlanPendiente($id);
            $ingredientes = $this->bloquearIngredientes((int) $plan['receta_id']);

            if ($ingredientes === []) {
                throw new ErrorValidacion(['unidades_producidas' => 'La receta no tiene ingredientes; agréguelos antes de registrar la producción.']);
            }

            $rendimientoUnidades = (int) $plan['cantidad_producir'];
            $requerimientos = $this->calculadora->calcularRequerimientos($ingredientes, $rendimientoUnidades, $unidadesProducidas);
            $this->validarExistenciasSuficientes($ingredientes, $requerimientos, $rendimientoUnidades, $unidadesProducidas);

            foreach ($ingredientes as $ingrediente) {
                $cantidad = $requerimientos[$ingrediente['materia_prima_id']];

                if ($cantidad > 0) {
                    $this->inventario->registrarMovimiento(
                        $ingrediente['materia_prima_id'],
                        'SALIDA',
                        $cantidad,
                        sprintf('Producción #%d: %s de %s', $id, pluralizar($unidadesProducidas, 'unidad', 'unidades'), $plan['receta']),
                        'PRODUCCION',
                        $id
                    );
                }
            }

            $this->conexion
                ->prepare(
                    "UPDATE producciones
                        SET estado = 'REALIZADA', cantidad_producida = :unidades, fecha_realizacion = NOW(),
                            observacion = COALESCE(:observacion, observacion)
                      WHERE id = :id"
                )
                ->execute([':unidades' => $unidadesProducidas, ':observacion' => $observacion === '' ? null : $observacion, ':id' => $id]);
        });
    }

    /** @param array<string, mixed> $datos */
    public function cancelarProduccion(int $id, array $datos): void
    {
        $validador = new Validador($datos);
        $motivo = $validador->obtenerTexto('motivo', 'el motivo', 255, false);
        $validador->lanzarSiHayErrores();

        BaseDatos::ejecutarEnTransaccion($this->conexion, function () use ($id, $motivo): void {
            $this->bloquearPlanPendiente($id);
            $this->conexion
                ->prepare("UPDATE producciones SET estado = 'CANCELADA', observacion = COALESCE(:motivo, observacion) WHERE id = :id")
                ->execute([':motivo' => $motivo === '' ? null : $motivo, ':id' => $id]);
        });
    }

    /**
     * @param array<string, mixed> $produccion
     * @param array<int, array{estado: string, ingredientes: list<array<string, mixed>>}> $analisis
     * @return array<string, mixed>
     */
    private function completarProduccion(array $produccion, array $analisis): array
    {
        $produccion['id'] = (int) $produccion['id'];
        $produccion['receta_id'] = (int) $produccion['receta_id'];
        $produccion['cantidad_planeada'] = (int) $produccion['cantidad_planeada'];
        $produccion['cantidad_producir'] = (int) $produccion['cantidad_producir'];
        $produccion['cantidad_producida'] = $produccion['cantidad_producida'] === null ? null : (int) $produccion['cantidad_producida'];
        $produccion['esta_atrasada'] = $produccion['estado'] === 'PLANEADA' && $produccion['fecha_planeada'] < obtenerFechaHoy();
        $produccion['analisis'] = $analisis[$produccion['id']] ?? null;

        return $produccion;
    }

    /** @return list<array<string, mixed>> */
    private function listarConsumos(int $produccionId): array
    {
        $consulta = $this->conexion->prepare(
            "SELECT m.cantidad, m.fecha, mp.nombre, mp.unidad_medida
               FROM movimientos_inventario m
               JOIN materias_primas mp ON mp.id = m.materia_prima_id
              WHERE m.origen = 'PRODUCCION' AND m.referencia_id = :id
              ORDER BY mp.nombre"
        );
        $consulta->execute([':id' => $produccionId]);

        return $consulta->fetchAll();
    }

    /** @return array<string, mixed> */
    private function obtenerPlanPendiente(int $id): array
    {
        $produccion = $this->obtenerProduccion($id);

        if ($produccion === null) {
            throw new ErrorValidacion(['general' => 'El plan de producción no existe.']);
        }

        if ($produccion['estado'] !== 'PLANEADA') {
            throw new ErrorValidacion(['general' => 'Solo se pueden modificar los planes que aún no se han realizado ni cancelado.']);
        }

        return $produccion;
    }

    /** @param array<string, mixed>|null $produccionActual */
    private function validarRecetaPlanificable(Validador $validador, int $recetaId, ?array $produccionActual): void
    {
        if ($recetaId <= 0) {
            return;
        }

        $receta = $this->recetas->obtenerReceta($recetaId);
        $esLaMismaReceta = $produccionActual !== null && $produccionActual['receta_id'] === $recetaId;

        if ($receta === null || (!$receta['esta_activa'] && !$esLaMismaReceta)) {
            $validador->agregarError('receta_id', 'Elija una receta activa.');
        } elseif ($receta['ingredientes'] === []) {
            $validador->agregarError('receta_id', 'Esta receta no tiene ingredientes. Agréguelos antes de planificarla.');
        }
    }

    /** @return array<string, mixed> */
    private function bloquearPlanPendiente(int $id): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT p.*, r.nombre AS receta, r.cantidad_producir
               FROM producciones p
               JOIN recetas r ON r.id = p.receta_id
              WHERE p.id = :id
                FOR UPDATE'
        );
        $consulta->execute([':id' => $id]);
        $plan = $consulta->fetch();

        if ($plan === false) {
            throw new ErrorValidacion(['general' => 'El plan de producción no existe.']);
        }

        if ($plan['estado'] !== 'PLANEADA') {
            throw new ErrorValidacion(['general' => 'Este plan ya fue registrado o cancelado.']);
        }

        return $plan;
    }

    /** @return list<array{materia_prima_id: int, nombre: string, unidad_medida: string, cantidad: float, cantidad_disponible: float}> */
    private function bloquearIngredientes(int $recetaId): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT ri.materia_prima_id, ri.cantidad, mp.nombre, mp.unidad_medida, mp.cantidad_disponible
               FROM receta_ingredientes ri
               JOIN materias_primas mp ON mp.id = ri.materia_prima_id
              WHERE ri.receta_id = :receta
              ORDER BY mp.nombre
                FOR UPDATE'
        );
        $consulta->execute([':receta' => $recetaId]);

        return array_map(static fn (array $fila): array => [
            'materia_prima_id' => (int) $fila['materia_prima_id'],
            'nombre' => (string) $fila['nombre'],
            'unidad_medida' => (string) $fila['unidad_medida'],
            'cantidad' => (float) $fila['cantidad'],
            'cantidad_disponible' => (float) $fila['cantidad_disponible'],
        ], $consulta->fetchAll());
    }

    /**
     * @param list<array{materia_prima_id: int, nombre: string, unidad_medida: string, cantidad: float, cantidad_disponible: float}> $ingredientes
     * @param array<int, float> $requerimientos
     */
    private function validarExistenciasSuficientes(
        array $ingredientes,
        array $requerimientos,
        int $rendimientoUnidades,
        int $unidadesProducidas
    ): void {
        $faltantes = [];

        foreach ($ingredientes as $ingrediente) {
            $diferencia = round($requerimientos[$ingrediente['materia_prima_id']] - $ingrediente['cantidad_disponible'], 2);

            if ($diferencia > 0) {
                $faltantes[] = formatearCantidadConUnidad($diferencia, $ingrediente['unidad_medida'])
                    . ' de ' . mb_strtolower($ingrediente['nombre']);
            }
        }

        if ($faltantes === []) {
            return;
        }

        $unidadesMaximas = $this->calculadora->calcularProduccionMaxima($ingredientes, $rendimientoUnidades)['unidades_maximas'] ?? 0;

        throw new ErrorValidacion(['unidades_producidas' => sprintf(
            'No alcanzan los materiales para %s. Faltan %s. Con lo que hay se pueden producir máximo %s.',
            pluralizar($unidadesProducidas, 'unidad', 'unidades'),
            implode(', ', $faltantes),
            pluralizar($unidadesMaximas, 'unidad', 'unidades')
        )]);
    }
}
