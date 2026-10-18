<?php

declare(strict_types=1);

require_once __DIR__ . '/../inventario/movimientos-funciones.php';

function calcularProduccionMaxima(array $ingredientes): array
{
    if ($ingredientes === []) {
        throw new DomainException('La receta debe tener ingredientes para calcular su producción.');
    }
    $maximoUnidades = PHP_INT_MAX;
    $detalle = [];
    foreach ($ingredientes as $ingrediente) {
        $cantidadNecesariaCentesimas = convertirCantidadCentesimas($ingrediente['cantidad'] ?? null);
        $cantidadDisponibleCentesimas = convertirExistenciaCalculo($ingrediente['cantidad_disponible'] ?? null);
        if ($cantidadNecesariaCentesimas === null || $cantidadNecesariaCentesimas <= 0
            || $cantidadDisponibleCentesimas === null) {
            throw new DomainException('Revise las cantidades de la receta y del inventario antes de calcular.');
        }
        $unidadesPosibles = intdiv($cantidadDisponibleCentesimas, $cantidadNecesariaCentesimas);
        $maximoUnidades = min($maximoUnidades, $unidadesPosibles);
        $detalle[] = ['materia_prima_id' => (int) $ingrediente['materia_prima_id'],
            'nombre' => (string) $ingrediente['nombre'], 'unidad_medida' => (string) $ingrediente['unidad_medida'],
            'cantidad_necesaria' => $ingrediente['cantidad'], 'cantidad_disponible' => $ingrediente['cantidad_disponible'],
            'unidades_posibles' => $unidadesPosibles];
    }
    return ['maximo_unidades' => $maximoUnidades, 'ingredientes' => $detalle,
        'limitantes' => array_values(array_filter($detalle, fn (array $ingrediente): bool => $ingrediente['unidades_posibles'] === $maximoUnidades))];
}

function convertirExistenciaCalculo(mixed $cantidadUnidad): ?int
{
    if (!is_string($cantidadUnidad) || !preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/D', $cantidadUnidad)) {
        return null;
    }
    $partes = explode('.', $cantidadUnidad);
    $digitos = ltrim($partes[0] . str_pad($partes[1] ?? '', 2, '0'), '0');
    $digitos = $digitos === '' ? '0' : $digitos;
    $maximo = (string) PHP_INT_MAX;
    if (strlen($digitos) > strlen($maximo) || (strlen($digitos) === strlen($maximo) && strcmp($digitos, $maximo) > 0)) {
        return null;
    }
    return (int) $digitos;
}

function iniciarConsultaProduccion(PDO $conexion): void
{
    if ($conexion->inTransaction()) {
        throw new LogicException('La consulta necesita una transacción independiente.');
    }
    // Las consultas relacionadas deben leer la misma versión del inventario.
    $conexion->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $conexion->beginTransaction();
}

function obtenerIngredientesCalculo(PDO $conexion, int $recetaId): array
{
    $consulta = $conexion->prepare('SELECT nombre, cantidad_producir FROM recetas WHERE id = ?');
    $consulta->execute([$recetaId]);
    $receta = $consulta->fetch(PDO::FETCH_ASSOC);
    if ($receta === false || (int) $receta['cantidad_producir'] !== 1) {
        throw new DomainException('Seleccione una receta con cantidades definidas por unidad.');
    }
    $consulta = $conexion->prepare('SELECT mp.id AS materia_prima_id, mp.nombre, mp.unidad_medida,
        mp.cantidad_disponible, ri.cantidad,
        COALESCE(p.cantidad_pendiente, 0) AS cantidad_pendiente
        FROM receta_ingredientes ri
        JOIN materias_primas mp ON mp.id = ri.materia_prima_id
        LEFT JOIN (SELECT materia_prima_id, SUM(cantidad) AS cantidad_pendiente
            FROM materiales_pendientes GROUP BY materia_prima_id) p ON p.materia_prima_id = mp.id
        WHERE ri.receta_id = ? ORDER BY mp.id');
    $consulta->execute([$recetaId]);
    $ingredientes = $consulta->fetchAll(PDO::FETCH_ASSOC);
    if (count(array_unique(array_column($ingredientes, 'materia_prima_id'))) !== count($ingredientes)) {
        throw new DomainException('La receta contiene ingredientes repetidos; revise sus cantidades.');
    }
    return ['receta_nombre' => $receta['nombre'], 'ingredientes' => $ingredientes];
}

function obtenerProduccionMaxima(PDO $conexion, int $recetaId): array
{
    iniciarConsultaProduccion($conexion);
    try {
        $datos = obtenerIngredientesCalculo($conexion, $recetaId);
        $resultado = calcularProduccionMaxima($datos['ingredientes']);
        $resultado['receta_nombre'] = $datos['receta_nombre'];
        $conexion->commit();
        return $resultado;
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        throw $error;
    }
}

function obtenerRecetasPosibles(PDO $conexion): array
{
    iniciarConsultaProduccion($conexion);
    try {
        $recetas = $conexion->query('SELECT id, nombre FROM recetas ORDER BY nombre, id')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($recetas as &$receta) {
            try {
                $datos = obtenerIngredientesCalculo($conexion, (int) $receta['id']);
                $calculo = calcularProduccionMaxima($datos['ingredientes']);
                $receta['maximo_unidades'] = $calculo['maximo_unidades'];
                $receta['es_posible'] = $calculo['maximo_unidades'] >= 1;
                $receta['error'] = '';
            } catch (DomainException $error) {
                $receta['maximo_unidades'] = null;
                $receta['es_posible'] = false;
                $receta['error'] = $error->getMessage();
            }
        }
        unset($receta);
        $conexion->commit();
        return $recetas;
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        throw $error;
    }
}

function obtenerProyeccionProduccion(PDO $conexion, int $recetaId): array
{
    iniciarConsultaProduccion($conexion);
    try {
        $datos = obtenerIngredientesCalculo($conexion, $recetaId);
        $actual = calcularProduccionMaxima($datos['ingredientes']);
        $proyectados = $datos['ingredientes'];
        foreach ($proyectados as &$ingrediente) {
            $disponibleCentesimas = convertirExistenciaCalculo($ingrediente['cantidad_disponible']);
            $pendienteCentesimas = convertirExistenciaCalculo((string) $ingrediente['cantidad_pendiente']);
            if ($disponibleCentesimas === null || $pendienteCentesimas === null
                || $pendienteCentesimas > PHP_INT_MAX - $disponibleCentesimas) {
                throw new DomainException('La proyección supera el rango de cálculo o tiene cantidades inválidas.');
            }
            $ingrediente['cantidad_disponible'] = convertirCentesimasCantidad($disponibleCentesimas + $pendienteCentesimas);
        }
        unset($ingrediente);
        $proyeccion = calcularProduccionMaxima($proyectados);
        $conexion->commit();
        return ['receta_nombre' => $datos['receta_nombre'], 'actual' => $actual,
            'proyeccion' => $proyeccion, 'ingredientes' => $datos['ingredientes']];
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        throw $error;
    }
}
