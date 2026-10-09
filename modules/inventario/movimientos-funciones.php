<?php

declare(strict_types=1);

function obtenerTiposMovimiento(): array
{
    return ['ENTRADA', 'SALIDA', 'PENDIENTE'];
}

function convertirCantidadCentesimas(mixed $cantidadUnidad): ?int
{
    if (!is_string($cantidadUnidad) || !preg_match('/^[0-9]{1,8}(?:\.[0-9]{1,2})?$/D', $cantidadUnidad)) {
        return null;
    }

    $partes = explode('.', $cantidadUnidad);

    return (int) $partes[0] * 100 + (int) str_pad($partes[1] ?? '', 2, '0');
}

function convertirCentesimasCantidad(int $cantidadCentesimas): string
{
    return intdiv($cantidadCentesimas, 100) . '.' . str_pad((string) ($cantidadCentesimas % 100), 2, '0', STR_PAD_LEFT);
}

function validarMovimiento(array $datos): array
{
    $errores = [];
    $materiaPrimaId = filter_var($datos['materia_prima_id'] ?? null, FILTER_VALIDATE_INT);
    $cantidadCentesimas = convertirCantidadCentesimas($datos['cantidad'] ?? null);

    if ($materiaPrimaId === false || $materiaPrimaId === null || $materiaPrimaId < 1) {
        $errores[] = 'Seleccione una materia prima válida.';
    }
    if (!in_array($datos['tipo'] ?? null, obtenerTiposMovimiento(), true)) {
        $errores[] = 'Seleccione ENTRADA, SALIDA o PENDIENTE.';
    }
    if ($cantidadCentesimas === null || $cantidadCentesimas <= 0) {
        $errores[] = 'La cantidad debe ser positiva, con máximo dos decimales y hasta 99999999.99 en la unidad de la materia prima.';
    }
    if (!is_string($datos['observacion'] ?? null) || mb_strlen($datos['observacion']) > 255) {
        $errores[] = 'La observación debe ser texto de máximo 255 caracteres.';
    }

    return $errores;
}

function registrarMovimiento(PDO $conexion, array $datos): void
{
    $errores = validarMovimiento($datos);
    if ($errores !== []) {
        throw new InvalidArgumentException(implode(' ', $errores));
    }
    if ($conexion->inTransaction()) {
        throw new LogicException('El registro necesita una transacción independiente.');
    }

    $cantidadCentesimas = convertirCantidadCentesimas($datos['cantidad']);
    $conexion->beginTransaction();
    try {
        // Bloqueo de la materia prima: dos salidas no pueden gastar el mismo saldo.
        $consulta = $conexion->prepare('SELECT cantidad_disponible FROM materias_primas WHERE id = :id FOR UPDATE');
        $consulta->execute(['id' => $datos['materia_prima_id']]);
        $materiaPrima = $consulta->fetch(PDO::FETCH_ASSOC);
        if ($materiaPrima === false) {
            throw new DomainException('La materia prima ya no existe.');
        }

        $existenciaCentesimas = convertirCantidadCentesimas((string) $materiaPrima['cantidad_disponible']);
        if ($existenciaCentesimas === null) {
            throw new DomainException('La existencia actual es inválida; revise los datos antes de registrar movimientos.');
        }
        if ($datos['tipo'] !== 'PENDIENTE') {
            $nuevaExistenciaCentesimas = $datos['tipo'] === 'ENTRADA'
                ? $existenciaCentesimas + $cantidadCentesimas
                : $existenciaCentesimas - $cantidadCentesimas;
            if ($nuevaExistenciaCentesimas < 0) {
                throw new DomainException('La salida supera la existencia disponible.');
            }
            if ($nuevaExistenciaCentesimas > 9999999999) {
                throw new DomainException('La entrada supera el límite de existencia permitido.');
            }

            $actualizar = $conexion->prepare('UPDATE materias_primas SET cantidad_disponible = :cantidad WHERE id = :id');
            $actualizar->execute([
                'cantidad' => convertirCentesimasCantidad($nuevaExistenciaCentesimas),
                'id' => $datos['materia_prima_id'],
            ]);
        }

        // Las tablas dependen únicamente de un tipo validado; nunca de SQL del usuario.
        $sql = $datos['tipo'] === 'PENDIENTE'
            ? 'INSERT INTO materiales_pendientes (materia_prima_id, cantidad, observacion) VALUES (:id, :cantidad, :observacion)'
            : 'INSERT INTO movimientos_inventario (materia_prima_id, tipo, cantidad, observacion) VALUES (:id, :tipo, :cantidad, :observacion)';
        $parametros = [
            'id' => $datos['materia_prima_id'],
            'cantidad' => convertirCentesimasCantidad($cantidadCentesimas),
            'observacion' => $datos['observacion'],
        ];
        if ($datos['tipo'] !== 'PENDIENTE') {
            $parametros['tipo'] = $datos['tipo'];
        }
        $registrar = $conexion->prepare($sql);
        $registrar->execute($parametros);
        $conexion->commit();
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        throw $error;
    }
}

function obtenerTokenMovimiento(): string
{
    if (!isset($_SESSION['token_movimiento'])) {
        $_SESSION['token_movimiento'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['token_movimiento'];
}

function actualizarMateriaPrimaProtegida(PDO $conexion, int $id, array $datos): void
{
    $cantidadDisponibleCentesimas = convertirCantidadCentesimas($datos['cantidad_disponible'] ?? null);
    $stockMinimoCentesimas = convertirCantidadCentesimas($datos['stock_minimo'] ?? null);
    if ($cantidadDisponibleCentesimas === null || $stockMinimoCentesimas === null) {
        throw new DomainException('Existencia y stock mínimo deben tener hasta dos decimales y estar entre 0 y 99999999.99.');
    }

    $conexion->beginTransaction();
    try {
        $consultar = $conexion->prepare('SELECT cantidad_disponible, unidad_medida FROM materias_primas WHERE id = :id FOR UPDATE');
        $consultar->execute(['id' => $id]);
        $actual = $consultar->fetch(PDO::FETCH_ASSOC);
        if ($actual === false) {
            throw new DomainException('La materia prima ya no existe.');
        }
        $consultarReferencias = $conexion->prepare('SELECT
            (SELECT COUNT(*) FROM movimientos_inventario WHERE materia_prima_id = :movimiento_id) +
            (SELECT COUNT(*) FROM materiales_pendientes WHERE materia_prima_id = :pendiente_id) AS movimientos,
            (SELECT COUNT(*) FROM receta_ingredientes WHERE materia_prima_id = :ingrediente_id) AS ingredientes');
        $consultarReferencias->execute(['movimiento_id' => $id, 'pendiente_id' => $id, 'ingrediente_id' => $id]);
        $referencias = $consultarReferencias->fetch(PDO::FETCH_ASSOC);
        $tieneMovimientos = (int) $referencias['movimientos'] > 0;
        $tieneIngredientes = (int) $referencias['ingredientes'] > 0;
        if (($tieneMovimientos || $tieneIngredientes) && $actual['unidad_medida'] !== $datos['unidad_medida']) {
            throw new DomainException('No cambie la unidad de una materia prima con movimientos, pendientes o ingredientes registrados.');
        }
        if ($tieneMovimientos && convertirCantidadCentesimas((string) $actual['cantidad_disponible']) !== $cantidadDisponibleCentesimas) {
            throw new DomainException('Registre una entrada o salida para cambiar la existencia de una materia prima con historial.');
        }
        $actualizar = $conexion->prepare('UPDATE materias_primas SET nombre = :nombre, unidad_medida = :unidad,
            cantidad_disponible = :cantidad, stock_minimo = :stock WHERE id = :id');
        $actualizar->execute([
            'nombre' => $datos['nombre'], 'unidad' => $datos['unidad_medida'],
            'cantidad' => convertirCentesimasCantidad($cantidadDisponibleCentesimas),
            'stock' => convertirCentesimasCantidad($stockMinimoCentesimas), 'id' => $id,
        ]);
        $conexion->commit();
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        throw $error;
    }
}

function validarTokenMovimiento(mixed $token): bool
{
    return is_string($token) && isset($_SESSION['token_movimiento'])
        && hash_equals($_SESSION['token_movimiento'], $token);
}
