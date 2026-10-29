<?php

declare(strict_types=1);

require_once __DIR__ . '/../inventario/movimientos-funciones.php';

function validarReceta(array $datos): array
{
    $errores = [];
    if (!is_string($datos['nombre'] ?? null) || trim($datos['nombre']) === '' || mb_strlen($datos['nombre']) > 100) {
        $errores[] = 'Indique un nombre de máximo 100 caracteres.';
    }
    if (!is_string($datos['descripcion'] ?? null) || mb_strlen($datos['descripcion']) > 255) {
        $errores[] = 'La descripción admite máximo 255 caracteres.';
    }
    if (!is_array($datos['ingredientes'] ?? null) || $datos['ingredientes'] === [] || count($datos['ingredientes']) > 100) {
        return array_merge($errores, ['Indique entre uno y cien ingredientes.']);
    }
    $identificadores = [];
    foreach ($datos['ingredientes'] as $ingrediente) {
        if (!is_array($ingrediente)) {
            $errores[] = 'Ingrediente inválido.';
            continue;
        }
        $id = filter_var($ingrediente['materia_prima_id'] ?? null, FILTER_VALIDATE_INT);
        $cantidadCentesimas = convertirCantidadCentesimas($ingrediente['cantidad'] ?? null);
        if ($id === false || $id === null || $id < 1 || in_array($id, $identificadores, true)) {
            $errores[] = 'Seleccione materias primas válidas y sin repetir.';
        }
        if ($cantidadCentesimas === null || $cantidadCentesimas <= 0) {
            $errores[] = 'Cada cantidad debe ser positiva, con máximo dos decimales y dentro de 99999999.99.';
        }
        $identificadores[] = $id;
    }
    return array_values(array_unique($errores));
}

function registrarReceta(PDO $conexion, array $datos): int
{
    $errores = validarReceta($datos);
    if ($errores !== []) {
        throw new InvalidArgumentException(implode(' ', $errores));
    }
    $conexion->beginTransaction();
    try {
        // El orden de bloqueo evita que dos recetas bloqueen las mismas materias en orden opuesto.
        $ingredientes = $datos['ingredientes'];
        usort($ingredientes, fn (array $a, array $b): int => (int) $a['materia_prima_id'] <=> (int) $b['materia_prima_id']);
        $consulta = $conexion->prepare('SELECT id, unidad_medida FROM materias_primas WHERE id = ? FOR UPDATE');
        foreach ($ingredientes as $ingrediente) {
            $consulta->execute([$ingrediente['materia_prima_id']]);
            if ($consulta->fetch() === false) {
                throw new DomainException('Un ingrediente ya no existe. No se guardó la receta.');
            }
        }
        $guardar = $conexion->prepare('INSERT INTO recetas (nombre, descripcion, cantidad_producir) VALUES (?, ?, 1)');
        $guardar->execute([trim($datos['nombre']), trim($datos['descripcion'])]);
        $recetaId = (int) $conexion->lastInsertId();
        $guardarIngrediente = $conexion->prepare('INSERT INTO receta_ingredientes (receta_id, materia_prima_id, cantidad) VALUES (?, ?, ?)');
        foreach ($ingredientes as $ingrediente) {
            $guardarIngrediente->execute([$recetaId, $ingrediente['materia_prima_id'],
                convertirCentesimasCantidad(convertirCantidadCentesimas($ingrediente['cantidad']))]);
        }
        $conexion->commit();
        return $recetaId;
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        throw $error;
    }
}

function obtenerReceta(PDO $conexion, int $recetaId): ?array
{
    $consulta = $conexion->prepare('SELECT id, nombre, descripcion, cantidad_producir FROM recetas WHERE id = ?');
    $consulta->execute([$recetaId]);
    $receta = $consulta->fetch();
    if ($receta === false) {
        return null;
    }
    $consulta = $conexion->prepare('SELECT mp.id AS materia_prima_id, mp.nombre, mp.unidad_medida, ri.cantidad
        FROM receta_ingredientes ri JOIN materias_primas mp ON mp.id = ri.materia_prima_id
        WHERE ri.receta_id = ? ORDER BY mp.nombre, mp.id');
    $consulta->execute([$recetaId]);
    $receta['ingredientes'] = $consulta->fetchAll();
    return $receta;
}
