<?php

declare(strict_types=1);

final class GestorPedidos
{
    private readonly GestorInventario $inventario;

    public function __construct(private readonly PDO $conexion)
    {
        $this->inventario = new GestorInventario($conexion);
    }

    /** @return list<array<string, mixed>> */
    public function listarPedidos(string $estado = 'PENDIENTE'): array
    {
        $orden = $estado === 'PENDIENTE' ? 'pe.fecha_estimada_llegada ASC, pe.id ASC' : 'pe.fecha_registro DESC, pe.id DESC';
        $consulta = $this->conexion->prepare(
            "SELECT pe.*, mp.nombre AS materia_prima, mp.unidad_medida
               FROM materiales_pendientes pe
               JOIN materias_primas mp ON mp.id = pe.materia_prima_id
              WHERE pe.estado = :estado
              ORDER BY $orden
              LIMIT 200"
        );
        $consulta->execute([':estado' => $estado]);
        $hoy = obtenerFechaHoy();

        return array_map(static function (array $pedido) use ($hoy): array {
            $pedido['cantidad'] = (float) $pedido['cantidad'];
            $pedido['dias_para_llegada'] = calcularDiferenciaDias($hoy, $pedido['fecha_estimada_llegada']);
            $pedido['esta_atrasado'] = $pedido['estado'] === 'PENDIENTE' && $pedido['dias_para_llegada'] < 0;

            return $pedido;
        }, $consulta->fetchAll());
    }

    /** @return array<string, mixed>|null */
    public function obtenerPedido(int $id): ?array
    {
        $consulta = $this->conexion->prepare(
            'SELECT pe.*, mp.nombre AS materia_prima, mp.unidad_medida
               FROM materiales_pendientes pe
               JOIN materias_primas mp ON mp.id = pe.materia_prima_id
              WHERE pe.id = :id'
        );
        $consulta->execute([':id' => $id]);
        $pedido = $consulta->fetch();

        if ($pedido === false) {
            return null;
        }

        $pedido['cantidad'] = (float) $pedido['cantidad'];

        return $pedido;
    }

    /** @param array<string, mixed> $datos */
    public function crearPedido(array $datos): int
    {
        $validador = new Validador($datos);
        $materiaPrimaId = $validador->obtenerId('materia_prima_id', 'la materia prima');
        $cantidad = $validador->obtenerDecimal('cantidad', 'la cantidad');
        $fechaLlegada = $validador->obtenerFecha('fecha_estimada_llegada', 'la fecha estimada de llegada', obtenerFechaHoy());
        $proveedor = $validador->obtenerTexto('proveedor', 'el proveedor', 100, false);
        $observacion = $validador->obtenerTexto('observacion', 'la observación', 255, false);

        if ($materiaPrimaId > 0 && $this->inventario->obtenerMateriaPrima($materiaPrimaId) === null) {
            $validador->agregarError('materia_prima_id', 'Elija la materia prima.');
        }

        $validador->lanzarSiHayErrores();

        $this->conexion
            ->prepare(
                'INSERT INTO materiales_pendientes (materia_prima_id, cantidad, proveedor, fecha_estimada_llegada, observacion)
                 VALUES (:materia, :cantidad, :proveedor, :llegada, :observacion)'
            )
            ->execute([
                ':materia' => $materiaPrimaId,
                ':cantidad' => $cantidad,
                ':proveedor' => $proveedor === '' ? null : $proveedor,
                ':llegada' => $fechaLlegada,
                ':observacion' => $observacion === '' ? null : $observacion,
            ]);

        return (int) $this->conexion->lastInsertId();
    }

    /** @param array<string, mixed> $datos */
    public function recibirPedido(int $id, array $datos): void
    {
        $validador = new Validador($datos);
        $cantidadRecibida = $validador->obtenerDecimal('cantidad_recibida', 'la cantidad recibida');
        $validador->lanzarSiHayErrores();

        BaseDatos::ejecutarEnTransaccion($this->conexion, function () use ($id, $cantidadRecibida): void {
            $pedido = $this->bloquearPedidoPendiente($id);
            $observacion = sprintf('Pedido #%d recibido', $id);

            if (abs($cantidadRecibida - (float) $pedido['cantidad']) > 0.001) {
                $observacion .= sprintf(
                    ' (se pidieron %s, llegaron %s)',
                    formatearCantidad((float) $pedido['cantidad']),
                    formatearCantidad($cantidadRecibida)
                );
            }

            $this->inventario->registrarMovimiento(
                (int) $pedido['materia_prima_id'],
                'ENTRADA',
                $cantidadRecibida,
                $observacion,
                'PEDIDO',
                $id
            );
            $this->conexion
                ->prepare("UPDATE materiales_pendientes SET estado = 'RECIBIDO', fecha_recepcion = NOW() WHERE id = :id")
                ->execute([':id' => $id]);
        });
    }

    public function cancelarPedido(int $id): void
    {
        BaseDatos::ejecutarEnTransaccion($this->conexion, function () use ($id): void {
            $this->bloquearPedidoPendiente($id);
            $this->conexion
                ->prepare("UPDATE materiales_pendientes SET estado = 'CANCELADO' WHERE id = :id")
                ->execute([':id' => $id]);
        });
    }

    /** @return array<int, float> */
    public function obtenerCantidadesPorLlegar(?string $hastaFecha = null): array
    {
        $sql = "SELECT materia_prima_id, SUM(cantidad) AS total
                  FROM materiales_pendientes
                 WHERE estado = 'PENDIENTE'";
        $parametros = [];

        if ($hastaFecha !== null) {
            $sql .= ' AND fecha_estimada_llegada <= :hasta';
            $parametros[':hasta'] = $hastaFecha;
        }

        $consulta = $this->conexion->prepare($sql . ' GROUP BY materia_prima_id');
        $consulta->execute($parametros);
        $cantidades = [];

        foreach ($consulta->fetchAll() as $fila) {
            $cantidades[(int) $fila['materia_prima_id']] = (float) $fila['total'];
        }

        return $cantidades;
    }

    /** @return list<array{materia_prima_id: int, fecha_estimada_llegada: string, cantidad: float}> */
    public function listarLlegadasPendientes(): array
    {
        $filas = $this->conexion
            ->query(
                "SELECT materia_prima_id, fecha_estimada_llegada, cantidad
                   FROM materiales_pendientes
                  WHERE estado = 'PENDIENTE'
                  ORDER BY fecha_estimada_llegada, id"
            )
            ->fetchAll();

        return array_map(static fn (array $fila): array => [
            'materia_prima_id' => (int) $fila['materia_prima_id'],
            'fecha_estimada_llegada' => (string) $fila['fecha_estimada_llegada'],
            'cantidad' => (float) $fila['cantidad'],
        ], $filas);
    }

    /** @return array<string, mixed> */
    private function bloquearPedidoPendiente(int $id): array
    {
        $consulta = $this->conexion->prepare('SELECT * FROM materiales_pendientes WHERE id = :id FOR UPDATE');
        $consulta->execute([':id' => $id]);
        $pedido = $consulta->fetch();

        if ($pedido === false) {
            throw new ErrorValidacion(['general' => 'El pedido no existe.']);
        }

        if ($pedido['estado'] !== 'PENDIENTE') {
            throw new ErrorValidacion(['general' => 'El pedido ya fue recibido o cancelado.']);
        }

        return $pedido;
    }
}
