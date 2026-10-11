<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/movimientos-funciones.php';

$errores = $_SESSION['errores_movimiento'] ?? [];
$datosAnteriores = $_SESSION['datos_movimiento'] ?? [];
unset($_SESSION['errores_movimiento'], $_SESSION['datos_movimiento']);
$materiasPrimas = [];
$movimientos = [];
$pendientes = [];
try {
    $materiasPrimas = $conexion->query('SELECT id, nombre, unidad_medida, cantidad_disponible FROM materias_primas ORDER BY nombre, id')->fetchAll();
    $movimientos = $conexion->query('SELECT m.id, m.tipo, m.cantidad, m.fecha, m.observacion, p.nombre, p.unidad_medida
        FROM movimientos_inventario m INNER JOIN materias_primas p ON p.id = m.materia_prima_id ORDER BY m.fecha DESC, m.id DESC')->fetchAll();
    $pendientes = $conexion->query('SELECT m.id, m.cantidad, m.fecha_registro, m.observacion, p.nombre, p.unidad_medida
        FROM materiales_pendientes m INNER JOIN materias_primas p ON p.id = m.materia_prima_id ORDER BY m.fecha_registro DESC, m.id DESC')->fetchAll();
} catch (PDOException $error) {
    error_log('No se pudieron consultar movimientos: ' . $error->getMessage());
    $errores[] = 'No se pudo consultar. Revise la conexión y aplique la migración HU02 antes de usar Movimientos.';
}

function escaparMovimiento(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Movimientos de inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-4">
    <div class="d-flex justify-content-between mb-4">
        <h1 class="h3">Movimientos de inventario</h1>
        <a class="btn btn-outline-secondary" href="index.php">Materias primas</a>
    </div>
    <p>Use la unidad de la materia prima. Los materiales pendientes se registran aparte y no aumentan las existencias actuales.</p>
    <?php if (($_GET['estado'] ?? '') === 'registrado'): ?>
        <div class="alert alert-success" role="alert">Movimiento registrado correctamente.</div>
    <?php endif; ?>
    <?php if ($errores !== []): ?>
        <div class="alert alert-danger" role="alert"><ul class="mb-0">
            <?php foreach ($errores as $error): ?>
                <li><?= escaparMovimiento($error) ?></li>
            <?php endforeach; ?>
        </ul></div>
    <?php endif; ?>
    <section class="card mb-4"><div class="card-body">
        <h2 class="h5">Registrar movimiento o material pendiente</h2>
        <?php if ($materiasPrimas === []): ?>
            <p>Registre una materia prima antes de registrar movimientos.</p>
        <?php else: ?>
            <form action="guardar-movimiento.php" method="post" class="row g-3">
                <input type="hidden" name="token" value="<?= escaparMovimiento(obtenerTokenMovimiento()) ?>">
                <div class="col-md-5">
                    <label class="form-label" for="materia_prima_id">Materia prima, unidad y existencia actual</label>
                    <select class="form-select" id="materia_prima_id" name="materia_prima_id" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($materiasPrimas as $materiaPrima): ?>
                            <option value="<?= (int) $materiaPrima['id'] ?>" <?= (($datosAnteriores['materia_prima_id'] ?? '') === (string) $materiaPrima['id']) ? 'selected' : '' ?>>
                                <?= escaparMovimiento($materiaPrima['nombre'] . ' (' . $materiaPrima['unidad_medida'] . ') — ' . $materiaPrima['cantidad_disponible']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="tipo">Tipo</label>
                    <select class="form-select" id="tipo" name="tipo" required>
                        <?php foreach (obtenerTiposMovimiento() as $tipo): ?>
                            <option value="<?= escaparMovimiento($tipo) ?>" <?= (($datosAnteriores['tipo'] ?? '') === $tipo) ? 'selected' : '' ?>><?= escaparMovimiento($tipo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="cantidad">Cantidad en la unidad seleccionada</label>
                    <input class="form-control" id="cantidad" name="cantidad" type="number" min="0.01" max="99999999.99" step="0.01" required value="<?= escaparMovimiento($datosAnteriores['cantidad'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="observacion">Observación opcional</label>
                    <input class="form-control" id="observacion" name="observacion" maxlength="255" value="<?= escaparMovimiento($datosAnteriores['observacion'] ?? '') ?>">
                </div>
                <div class="col-12"><button class="btn btn-primary" type="submit">Guardar movimiento</button></div>
            </form>
        <?php endif; ?>
    </div></section>
    <section class="card mb-4"><div class="card-body">
        <h2 class="h5">Entradas y salidas realizadas</h2>
        <div class="table-responsive"><table class="table table-striped">
            <thead><tr><th>Fecha</th><th>Materia prima</th><th>Tipo</th><th>Cantidad</th><th>Observación</th></tr></thead>
            <tbody>
            <?php foreach ($movimientos as $movimiento): ?>
                <tr><td><?= escaparMovimiento($movimiento['fecha']) ?></td><td><?= escaparMovimiento($movimiento['nombre']) ?></td><td><?= escaparMovimiento($movimiento['tipo']) ?></td><td><?= escaparMovimiento($movimiento['cantidad'] . ' ' . $movimiento['unidad_medida']) ?></td><td><?= escaparMovimiento($movimiento['observacion'] ?? '') ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php if ($movimientos === []): ?><p>No hay entradas ni salidas registradas.</p><?php endif; ?>
    </div></section>
    <section class="card"><div class="card-body">
        <h2 class="h5">Materiales pendientes de llegada</h2>
        <p>Disponibilidad futura. Este listado no representa existencias actuales.</p>
        <div class="table-responsive"><table class="table table-striped">
            <thead><tr><th>Fecha de registro</th><th>Materia prima</th><th>Cantidad pendiente</th><th>Observación</th></tr></thead>
            <tbody>
            <?php foreach ($pendientes as $pendiente): ?>
                <tr><td><?= escaparMovimiento($pendiente['fecha_registro']) ?></td><td><?= escaparMovimiento($pendiente['nombre']) ?></td><td><?= escaparMovimiento($pendiente['cantidad'] . ' ' . $pendiente['unidad_medida']) ?></td><td><?= escaparMovimiento($pendiente['observacion'] ?? '') ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php if ($pendientes === []): ?><p>No hay materiales pendientes registrados.</p><?php endif; ?>
    </div></section>
</main>
</body>
</html>
