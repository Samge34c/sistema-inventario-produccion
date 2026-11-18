<?php

declare(strict_types=1);

final class GestorRecetas
{
    private readonly CalculadoraProduccion $calculadora;
    private readonly GestorInventario $inventario;
    private readonly GestorPedidos $pedidos;

    public function __construct(private readonly PDO $conexion)
    {
        $this->calculadora = new CalculadoraProduccion();
        $this->inventario = new GestorInventario($conexion);
        $this->pedidos = new GestorPedidos($conexion);
    }

    /** @return list<array<string, mixed>> */
    public function listarRecetas(bool $incluirArchivadas = false): array
    {
        $sql = 'SELECT * FROM recetas' . ($incluirArchivadas ? '' : ' WHERE activa = 1') . ' ORDER BY activa DESC, nombre';
        $recetas = $this->conexion->query($sql)->fetchAll();
        $ingredientesPorReceta = $this->listarIngredientesPorReceta(array_map(
            static fn (array $receta): int => (int) $receta['id'],
            $recetas
        ));
        $cantidadesPorLlegar = $this->pedidos->obtenerCantidadesPorLlegar();

        return array_map(function (array $receta) use ($ingredientesPorReceta, $cantidadesPorLlegar): array {
            $receta = $this->normalizarReceta($receta);
            $receta['ingredientes'] = $ingredientesPorReceta[$receta['id']] ?? [];

            return $this->agregarCapacidad($receta, $cantidadesPorLlegar);
        }, $recetas);
    }

    /** @return list<array{id: int, nombre: string, cantidad_producir: int}> */
    public function listarOpcionesRecetas(): array
    {
        $filas = $this->conexion
            ->query('SELECT id, nombre, cantidad_producir FROM recetas WHERE activa = 1 ORDER BY nombre')
            ->fetchAll();

        return array_map(static fn (array $fila): array => [
            'id' => (int) $fila['id'],
            'nombre' => (string) $fila['nombre'],
            'cantidad_producir' => (int) $fila['cantidad_producir'],
        ], $filas);
    }

    /** @return array<string, mixed>|null */
    public function obtenerReceta(int $id): ?array
    {
        $consulta = $this->conexion->prepare('SELECT * FROM recetas WHERE id = :id');
        $consulta->execute([':id' => $id]);
        $receta = $consulta->fetch();

        if ($receta === false) {
            return null;
        }

        $receta = $this->normalizarReceta($receta);
        $receta['ingredientes'] = $this->listarIngredientesPorReceta([$id])[$id] ?? [];

        return $this->agregarCapacidad($receta, $this->pedidos->obtenerCantidadesPorLlegar());
    }

    /**
     * @param list<int> $recetaIds
     * @return array<int, list<array{materia_prima_id: int, nombre: string, unidad_medida: string, cantidad: float, cantidad_disponible: float}>>
     */
    public function listarIngredientesPorReceta(array $recetaIds): array
    {
        if ($recetaIds === []) {
            return [];
        }

        [$marcadores, $parametros] = BaseDatos::prepararListaIn($recetaIds);
        $consulta = $this->conexion->prepare(
            "SELECT ri.receta_id, ri.materia_prima_id, ri.cantidad,
                    mp.nombre, mp.unidad_medida, mp.cantidad_disponible
               FROM receta_ingredientes ri
               JOIN materias_primas mp ON mp.id = ri.materia_prima_id
              WHERE ri.receta_id IN ($marcadores)
              ORDER BY mp.nombre"
        );
        $consulta->execute($parametros);
        $agrupados = [];

        foreach ($consulta->fetchAll() as $fila) {
            $agrupados[(int) $fila['receta_id']][] = [
                'materia_prima_id' => (int) $fila['materia_prima_id'],
                'nombre' => (string) $fila['nombre'],
                'unidad_medida' => (string) $fila['unidad_medida'],
                'cantidad' => (float) $fila['cantidad'],
                'cantidad_disponible' => (float) $fila['cantidad_disponible'],
            ];
        }

        return $agrupados;
    }

    /** @param array<string, mixed> $datos */
    public function guardarReceta(array $datos, ?int $id = null): int
    {
        $validador = new Validador($datos);
        $nombre = $validador->obtenerTexto('nombre', 'el nombre del producto', 100);
        $descripcion = $validador->obtenerTexto('descripcion', 'la descripción', 255, false);
        $rendimientoUnidades = $validador->obtenerEntero('cantidad_producir', 'el rendimiento', 1, 100000);
        $ingredientes = $this->leerIngredientes($datos['ingredientes'] ?? [], $validador);

        if ($nombre !== '' && $this->existeNombreReceta($nombre, $id)) {
            $validador->agregarError('nombre', 'Ya existe una receta con ese nombre.');
        }

        $validador->lanzarSiHayErrores();

        return BaseDatos::ejecutarEnTransaccion($this->conexion, function () use (
            $id,
            $nombre,
            $descripcion,
            $rendimientoUnidades,
            $ingredientes
        ): int {
            $parametros = [
                ':nombre' => $nombre,
                ':descripcion' => $descripcion === '' ? null : $descripcion,
                ':rendimiento' => $rendimientoUnidades,
            ];

            if ($id === null) {
                $this->conexion
                    ->prepare('INSERT INTO recetas (nombre, descripcion, cantidad_producir) VALUES (:nombre, :descripcion, :rendimiento)')
                    ->execute($parametros);
                $id = (int) $this->conexion->lastInsertId();
            } else {
                $this->conexion
                    ->prepare('UPDATE recetas SET nombre = :nombre, descripcion = :descripcion, cantidad_producir = :rendimiento WHERE id = :id')
                    ->execute($parametros + [':id' => $id]);
                $this->conexion->prepare('DELETE FROM receta_ingredientes WHERE receta_id = :id')->execute([':id' => $id]);
            }

            $insercion = $this->conexion->prepare(
                'INSERT INTO receta_ingredientes (receta_id, materia_prima_id, cantidad) VALUES (:receta, :materia, :cantidad)'
            );

            foreach ($ingredientes as $materiaPrimaId => $cantidad) {
                $insercion->execute([':receta' => $id, ':materia' => $materiaPrimaId, ':cantidad' => $cantidad]);
            }

            return $id;
        });
    }

    public function cambiarEstadoReceta(int $id, bool $estaActiva): void
    {
        $this->conexion
            ->prepare('UPDATE recetas SET activa = :activa WHERE id = :id')
            ->execute([':activa' => $estaActiva ? 1 : 0, ':id' => $id]);
    }

    public function eliminarReceta(int $id): void
    {
        $consulta = $this->conexion->prepare(
            'SELECT (SELECT COUNT(*) FROM producciones WHERE receta_id = :id1)
                  + (SELECT COUNT(*) FROM venta_detalles WHERE receta_id = :id2)'
        );
        $consulta->execute([':id1' => $id, ':id2' => $id]);

        if ((int) $consulta->fetchColumn() > 0) {
            throw new ErrorValidacion(['general' => 'Esta receta tiene producciones o ventas registradas, así que no se puede '
                . 'eliminar sin perder ese historial. Archívela: dejará de aparecer en la caja y en la planificación.']);
        }

        $this->conexion->prepare('DELETE FROM recetas WHERE id = :id')->execute([':id' => $id]);
    }

    /** @return array<int, float> */
    private function leerIngredientes(mixed $filas, Validador $validador): array
    {
        $ingredientes = [];
        $materiasExistentes = array_column($this->inventario->listarOpcionesMateriasPrimas(), 'nombre', 'id');

        foreach (is_array($filas) ? $filas : [] as $indice => $fila) {
            $materiaPrimaId = (int) ($fila['materia_prima_id'] ?? 0);
            $textoCantidad = trim((string) ($fila['cantidad'] ?? ''));

            if ($materiaPrimaId === 0 && $textoCantidad === '') {
                continue;
            }

            $cantidad = convertirTextoANumero($textoCantidad);
            $this->validarFilaIngrediente($validador, (string) $indice, $materiaPrimaId, $cantidad, $materiasExistentes, $ingredientes);
            $ingredientes[$materiaPrimaId] = round((float) $cantidad, 2);
        }

        if ($ingredientes === []) {
            $validador->agregarError('ingredientes', 'Agregue al menos un ingrediente con su cantidad.');
        }

        return $ingredientes;
    }

    /**
     * @param array<int, string> $materiasExistentes
     * @param array<int, float> $ingredientesLeidos
     */
    private function validarFilaIngrediente(
        Validador $validador,
        string $indice,
        int $materiaPrimaId,
        ?float $cantidad,
        array $materiasExistentes,
        array $ingredientesLeidos
    ): void {
        if (!isset($materiasExistentes[$materiaPrimaId])) {
            $validador->agregarError('ingredientes_' . $indice . '_materia', 'Elija la materia prima.');
        } elseif (isset($ingredientesLeidos[$materiaPrimaId])) {
            $validador->agregarError(
                'ingredientes_' . $indice . '_materia',
                sprintf('%s ya está en la receta. Sume las cantidades en una sola fila.', $materiasExistentes[$materiaPrimaId])
            );
        }

        if ($cantidad === null || $cantidad <= 0 || $cantidad > Validador::DECIMAL_MAXIMO) {
            $validador->agregarError('ingredientes_' . $indice . '_cantidad', 'Escriba una cantidad mayor que cero.');
        }
    }

    /**
     * @param array<string, mixed> $receta
     * @param array<int, float> $cantidadesPorLlegar
     * @return array<string, mixed>
     */
    private function agregarCapacidad(array $receta, array $cantidadesPorLlegar): array
    {
        $capacidad = $this->calculadora->calcularProduccionMaxima($receta['ingredientes'], $receta['cantidad_producir']);
        $proyeccion = $this->calculadora->calcularProduccionMaxima(
            $receta['ingredientes'],
            $receta['cantidad_producir'],
            $cantidadesPorLlegar
        );

        $receta['capacidad'] = $capacidad;
        $receta['proyeccion'] = $proyeccion;
        $receta['puede_producirse'] = ($capacidad['unidades_maximas'] ?? 0) > 0;

        return $receta;
    }

    /**
     * @param array<string, mixed> $receta
     * @return array<string, mixed>
     */
    private function normalizarReceta(array $receta): array
    {
        $receta['id'] = (int) $receta['id'];
        $receta['cantidad_producir'] = (int) $receta['cantidad_producir'];
        $receta['esta_activa'] = (int) $receta['activa'] === 1;

        return $receta;
    }

    private function existeNombreReceta(string $nombre, ?int $idExcluido): bool
    {
        $consulta = $this->conexion->prepare('SELECT COUNT(*) FROM recetas WHERE nombre = :nombre AND id <> :id');
        $consulta->execute([':nombre' => $nombre, ':id' => $idExcluido ?? 0]);

        return (int) $consulta->fetchColumn() > 0;
    }
}
