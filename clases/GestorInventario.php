<?php

declare(strict_types=1);

/**
 * Materias primas y movimientos de entrada y salida. Los pedidos pendientes
 * de llegada están en GestorPedidos.
 *
 * La existencia (cantidad_disponible) solo cambia mediante movimientos, para
 * que el historial siempre explique la cantidad actual.
 */
final class GestorInventario
{
    public const UNIDADES = [
        'g' => 'Gramos (g)',
        'kg' => 'Kilogramos (kg)',
        'ml' => 'Mililitros (ml)',
        'l' => 'Litros (l)',
        'unidad' => 'Unidades',
    ];

    public const SIN_EXISTENCIAS = 'SIN_EXISTENCIAS';
    public const BAJO_MINIMO = 'BAJO_MINIMO';
    public const DISPONIBLE = 'DISPONIBLE';

    public const MOVIMIENTOS_POR_PAGINA = 50;

    public function __construct(private readonly PDO $conexion)
    {
    }

    // -----------------------------------------------------------------------
    // Materias primas
    // -----------------------------------------------------------------------

    /**
     * Materias primas con su estado de existencia, lo que está por llegar y
     * en cuántas recetas se usan.
     *
     * @return list<array<string, mixed>>
     */
    public function listarMateriasPrimas(string $busqueda = '', string $estado = ''): array
    {
        $consulta = $this->conexion->prepare(
            "SELECT mp.*,
                    COALESCE(pe.cantidad_por_llegar, 0) AS cantidad_por_llegar,
                    pe.proxima_llegada,
                    (SELECT COUNT(*) FROM receta_ingredientes ri
                      WHERE ri.materia_prima_id = mp.id) AS total_recetas
               FROM materias_primas mp
               LEFT JOIN (SELECT materia_prima_id,
                                 SUM(cantidad) AS cantidad_por_llegar,
                                 MIN(fecha_estimada_llegada) AS proxima_llegada
                            FROM materiales_pendientes
                           WHERE estado = 'PENDIENTE'
                           GROUP BY materia_prima_id) pe ON pe.materia_prima_id = mp.id
              WHERE mp.nombre LIKE :busqueda
              ORDER BY mp.nombre"
        );
        $consulta->execute([':busqueda' => '%' . $busqueda . '%']);
        $materias = array_map([$this, 'normalizarMateriaPrima'], $consulta->fetchAll());

        if ($estado === '') {
            return $materias;
        }

        return array_values(array_filter(
            $materias,
            static fn (array $materia): bool => $estado === 'ALERTA'
                ? $materia['estado'] !== self::DISPONIBLE
                : $materia['estado'] === $estado
        ));
    }

    /**
     * Materias primas sin existencias o por debajo del mínimo.
     *
     * @return list<array<string, mixed>>
     */
    public function listarMateriasEnAlerta(): array
    {
        return $this->listarMateriasPrimas('', 'ALERTA');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerMateriaPrima(int $id): ?array
    {
        $consulta = $this->conexion->prepare('SELECT * FROM materias_primas WHERE id = :id');
        $consulta->execute([':id' => $id]);
        $fila = $consulta->fetch();

        return $fila === false ? null : $this->normalizarMateriaPrima($fila);
    }

    /**
     * Lista corta para selectores: id, nombre, unidad y existencia.
     *
     * @return list<array{id: int, nombre: string, unidad_medida: string, cantidad_disponible: float}>
     */
    public function listarOpcionesMateriasPrimas(): array
    {
        $filas = $this->conexion
            ->query('SELECT id, nombre, unidad_medida, cantidad_disponible FROM materias_primas ORDER BY nombre')
            ->fetchAll();

        return array_map(static fn (array $fila): array => [
            'id' => (int) $fila['id'],
            'nombre' => (string) $fila['nombre'],
            'unidad_medida' => (string) $fila['unidad_medida'],
            'cantidad_disponible' => (float) $fila['cantidad_disponible'],
        ], $filas);
    }

    /**
     * Crea o actualiza una materia prima a partir de los datos del formulario.
     * Al crearla, la cantidad inicial queda registrada como un movimiento de
     * entrada «Inventario inicial».
     *
     * @param array<string, mixed> $datos
     */
    public function guardarMateriaPrima(array $datos, ?int $id = null): int
    {
        $validador = new Validador($datos);
        $nombre = $validador->obtenerTexto('nombre', 'el nombre', 100);
        $unidadMedida = $validador->obtenerOpcion('unidad_medida', 'la unidad de medida', array_keys(self::UNIDADES));
        $stockMinimo = $validador->obtenerDecimal('stock_minimo', 'el stock mínimo', true);
        $cantidadInicial = $id === null ? $validador->obtenerDecimal('cantidad_inicial', 'la existencia actual', true) : 0.0;

        if ($nombre !== '' && $this->existeNombreMateriaPrima($nombre, $id)) {
            $validador->agregarError('nombre', 'Ya existe una materia prima con ese nombre.');
        }

        $this->validarCambioDeUnidad($validador, $id, $unidadMedida);
        $validador->lanzarSiHayErrores();

        return BaseDatos::ejecutarEnTransaccion($this->conexion, function () use (
            $id,
            $nombre,
            $unidadMedida,
            $stockMinimo,
            $cantidadInicial
        ): int {
            if ($id !== null) {
                $this->conexion
                    ->prepare('UPDATE materias_primas SET nombre = :nombre, unidad_medida = :unidad, stock_minimo = :minimo WHERE id = :id')
                    ->execute([':nombre' => $nombre, ':unidad' => $unidadMedida, ':minimo' => $stockMinimo, ':id' => $id]);

                return $id;
            }

            $this->conexion
                ->prepare('INSERT INTO materias_primas (nombre, unidad_medida, cantidad_disponible, stock_minimo) VALUES (:nombre, :unidad, 0, :minimo)')
                ->execute([':nombre' => $nombre, ':unidad' => $unidadMedida, ':minimo' => $stockMinimo]);
            $nuevoId = (int) $this->conexion->lastInsertId();

            if ($cantidadInicial > 0) {
                $this->registrarMovimiento($nuevoId, 'ENTRADA', $cantidadInicial, 'Inventario inicial');
            }

            return $nuevoId;
        });
    }

    /**
     * Elimina una materia prima que no tenga historial. Si se usa en recetas,
     * movimientos o pedidos, explica por qué no se puede.
     */
    public function eliminarMateriaPrima(int $id): void
    {
        $materia = $this->obtenerMateriaPrimaOFallar($id);
        $consulta = $this->conexion->prepare(
            "SELECT
                (SELECT COUNT(*) FROM receta_ingredientes WHERE materia_prima_id = :id1) AS recetas,
                (SELECT COUNT(*) FROM movimientos_inventario WHERE materia_prima_id = :id2) AS movimientos,
                (SELECT COUNT(*) FROM materiales_pendientes WHERE materia_prima_id = :id3) AS pedidos"
        );
        $consulta->execute([':id1' => $id, ':id2' => $id, ':id3' => $id]);
        $uso = array_map('intval', $consulta->fetch());

        if ($uso['recetas'] > 0) {
            throw new ErrorValidacion(['general' => sprintf(
                'No se puede eliminar «%s» porque se usa en %s. Quítela primero de esas recetas.',
                $materia['nombre'],
                pluralizar($uso['recetas'], 'receta', 'recetas')
            )]);
        }

        if ($uso['movimientos'] + $uso['pedidos'] > 0) {
            throw new ErrorValidacion(['general' => sprintf(
                'No se puede eliminar «%s» porque tiene historial de movimientos o pedidos, '
                    . 'y borrarlo dejaría el inventario sin explicación.',
                $materia['nombre']
            )]);
        }

        $this->conexion->prepare('DELETE FROM materias_primas WHERE id = :id')->execute([':id' => $id]);
    }

    // -----------------------------------------------------------------------
    // Movimientos
    // -----------------------------------------------------------------------

    /**
     * Registra una entrada o salida desde el formulario de movimientos.
     *
     * @param array<string, mixed> $datos
     */
    public function registrarMovimientoDesdeFormulario(int $materiaPrimaId, array $datos): void
    {
        $validador = new Validador($datos);
        $tipo = $validador->obtenerOpcion('tipo', 'si es entrada o salida', ['ENTRADA', 'SALIDA']);
        $cantidad = $validador->obtenerDecimal('cantidad', 'la cantidad');
        $observacion = $validador->obtenerTexto('observacion', 'la observación', 255, false);
        $validador->lanzarSiHayErrores();

        $this->registrarMovimiento($materiaPrimaId, $tipo, $cantidad, $observacion);
    }

    /**
     * Suma o resta existencias y deja el movimiento en el historial. Una
     * salida nunca puede dejar la existencia en negativo.
     */
    public function registrarMovimiento(
        int $materiaPrimaId,
        string $tipo,
        float $cantidad,
        string $observacion = '',
        string $origen = 'MANUAL',
        ?int $referenciaId = null
    ): void {
        if (!in_array($tipo, ['ENTRADA', 'SALIDA'], true)) {
            throw new InvalidArgumentException('Tipo de movimiento no válido: ' . $tipo);
        }

        $cantidad = round($cantidad, 2);

        if ($cantidad <= 0) {
            throw new ErrorValidacion(['cantidad' => 'La cantidad debe ser mayor que cero.']);
        }

        BaseDatos::ejecutarEnTransaccion($this->conexion, function () use (
            $materiaPrimaId,
            $tipo,
            $cantidad,
            $observacion,
            $origen,
            $referenciaId
        ): void {
            $materia = $this->bloquearMateriaPrima($materiaPrimaId);
            $cantidadNueva = round(
                $materia['cantidad_disponible'] + ($tipo === 'ENTRADA' ? $cantidad : -$cantidad),
                2
            );

            $this->validarCantidadResultante($materia, $cantidad, $cantidadNueva);

            $this->conexion
                ->prepare('UPDATE materias_primas SET cantidad_disponible = :cantidad WHERE id = :id')
                ->execute([':cantidad' => $cantidadNueva, ':id' => $materiaPrimaId]);
            $this->conexion
                ->prepare(
                    'INSERT INTO movimientos_inventario (materia_prima_id, tipo, cantidad, observacion, origen, referencia_id)
                     VALUES (:materia, :tipo, :cantidad, :observacion, :origen, :referencia)'
                )
                ->execute([
                    ':materia' => $materiaPrimaId,
                    ':tipo' => $tipo,
                    ':cantidad' => $cantidad,
                    ':observacion' => $observacion === '' ? null : $observacion,
                    ':origen' => $origen,
                    ':referencia' => $referenciaId,
                ]);
        });
    }

    /**
     * Historial de movimientos con filtros opcionales y paginación.
     *
     * @param array{materia_prima_id?: int, tipo?: string, desde?: string, hasta?: string} $filtros
     * @return array{movimientos: list<array<string, mixed>>, total: int}
     */
    public function listarMovimientos(array $filtros, int $pagina = 1): array
    {
        [$condiciones, $parametros] = $this->construirFiltrosMovimientos($filtros);
        $where = $condiciones === [] ? '' : 'WHERE ' . implode(' AND ', $condiciones);

        $consultaTotal = $this->conexion->prepare("SELECT COUNT(*) FROM movimientos_inventario m $where");
        $consultaTotal->execute($parametros);
        $total = (int) $consultaTotal->fetchColumn();

        $consulta = $this->conexion->prepare(
            "SELECT m.*, mp.nombre AS materia_prima, mp.unidad_medida
               FROM movimientos_inventario m
               JOIN materias_primas mp ON mp.id = m.materia_prima_id
               $where
              ORDER BY m.fecha DESC, m.id DESC
              LIMIT :limite OFFSET :desplazamiento"
        );

        foreach ($parametros as $marcador => $valor) {
            $consulta->bindValue($marcador, $valor);
        }

        $consulta->bindValue(':limite', self::MOVIMIENTOS_POR_PAGINA, PDO::PARAM_INT);
        $consulta->bindValue(':desplazamiento', (max(1, $pagina) - 1) * self::MOVIMIENTOS_POR_PAGINA, PDO::PARAM_INT);
        $consulta->execute();

        $movimientos = array_map(static function (array $fila): array {
            $fila['cantidad'] = (float) $fila['cantidad'];

            return $fila;
        }, $consulta->fetchAll());

        return ['movimientos' => $movimientos, 'total' => $total];
    }

    // -----------------------------------------------------------------------
    // Apoyo
    // -----------------------------------------------------------------------

    public static function determinarEstadoExistencia(float $cantidadDisponible, float $stockMinimo): string
    {
        if ($cantidadDisponible <= 0) {
            return self::SIN_EXISTENCIAS;
        }

        return $cantidadDisponible < $stockMinimo ? self::BAJO_MINIMO : self::DISPONIBLE;
    }

    /**
     * @param array<string, mixed> $fila
     * @return array<string, mixed>
     */
    private function normalizarMateriaPrima(array $fila): array
    {
        $fila['id'] = (int) $fila['id'];
        $fila['cantidad_disponible'] = (float) $fila['cantidad_disponible'];
        $fila['stock_minimo'] = (float) $fila['stock_minimo'];

        if (array_key_exists('cantidad_por_llegar', $fila)) {
            $fila['cantidad_por_llegar'] = (float) $fila['cantidad_por_llegar'];
            $fila['total_recetas'] = (int) $fila['total_recetas'];
        }

        $fila['estado'] = self::determinarEstadoExistencia($fila['cantidad_disponible'], $fila['stock_minimo']);

        return $fila;
    }

    private function existeNombreMateriaPrima(string $nombre, ?int $idExcluido): bool
    {
        $consulta = $this->conexion->prepare('SELECT COUNT(*) FROM materias_primas WHERE nombre = :nombre AND id <> :id');
        $consulta->execute([':nombre' => $nombre, ':id' => $idExcluido ?? 0]);

        return (int) $consulta->fetchColumn() > 0;
    }

    /**
     * Cambiar la unidad de una materia prima con existencias o en recetas
     * cambiaría el significado de esas cantidades, así que no se permite.
     */
    private function validarCambioDeUnidad(Validador $validador, ?int $id, string $unidadMedida): void
    {
        if ($id === null || $unidadMedida === '') {
            return;
        }

        $materia = $this->obtenerMateriaPrimaOFallar($id);

        if ($materia['unidad_medida'] === $unidadMedida) {
            return;
        }

        $consulta = $this->conexion->prepare(
            'SELECT (SELECT COUNT(*) FROM receta_ingredientes WHERE materia_prima_id = :id1)
                  + (SELECT COUNT(*) FROM movimientos_inventario WHERE materia_prima_id = :id2)'
        );
        $consulta->execute([':id1' => $id, ':id2' => $id]);

        if ((int) $consulta->fetchColumn() > 0) {
            $validador->agregarError(
                'unidad_medida',
                'No se puede cambiar la unidad porque ya hay movimientos o recetas con esta materia prima.'
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function obtenerMateriaPrimaOFallar(int $id): array
    {
        $materia = $this->obtenerMateriaPrima($id);

        if ($materia === null) {
            throw new ErrorValidacion(['general' => 'La materia prima no existe o fue eliminada.']);
        }

        return $materia;
    }

    /**
     * @return array<string, mixed>
     */
    private function bloquearMateriaPrima(int $id): array
    {
        $consulta = $this->conexion->prepare('SELECT * FROM materias_primas WHERE id = :id FOR UPDATE');
        $consulta->execute([':id' => $id]);
        $fila = $consulta->fetch();

        if ($fila === false) {
            throw new ErrorValidacion(['general' => 'La materia prima no existe o fue eliminada.']);
        }

        return $this->normalizarMateriaPrima($fila);
    }

    /**
     * @param array<string, mixed> $materia
     */
    private function validarCantidadResultante(array $materia, float $cantidad, float $cantidadNueva): void
    {
        if ($cantidadNueva < 0) {
            throw new ErrorValidacion(['cantidad' => sprintf(
                'No hay suficiente %s: hay %s y se intentan sacar %s.',
                mb_strtolower((string) $materia['nombre']),
                formatearCantidadConUnidad($materia['cantidad_disponible'], $materia['unidad_medida']),
                formatearCantidadConUnidad($cantidad, $materia['unidad_medida'])
            )]);
        }

        if ($cantidadNueva > Validador::DECIMAL_MAXIMO) {
            throw new ErrorValidacion(['cantidad' => 'La existencia resultante es demasiado grande.']);
        }
    }

    /**
     * @param array{materia_prima_id?: int, tipo?: string, desde?: string, hasta?: string} $filtros
     * @return array{0: list<string>, 1: array<string, int|string>}
     */
    private function construirFiltrosMovimientos(array $filtros): array
    {
        $condiciones = [];
        $parametros = [];

        if (!empty($filtros['materia_prima_id'])) {
            $condiciones[] = 'm.materia_prima_id = :materia';
            $parametros[':materia'] = $filtros['materia_prima_id'];
        }

        if (in_array($filtros['tipo'] ?? '', ['ENTRADA', 'SALIDA'], true)) {
            $condiciones[] = 'm.tipo = :tipo';
            $parametros[':tipo'] = $filtros['tipo'];
        }

        if (!empty($filtros['desde'])) {
            $condiciones[] = 'm.fecha >= :desde';
            $parametros[':desde'] = $filtros['desde'] . ' 00:00:00';
        }

        if (!empty($filtros['hasta'])) {
            $condiciones[] = 'm.fecha < :hasta';
            $parametros[':hasta'] = sumarDias($filtros['hasta'], 1) . ' 00:00:00';
        }

        return [$condiciones, $parametros];
    }
}
