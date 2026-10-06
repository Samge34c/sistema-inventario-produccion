<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/funciones.php';

$consulta = $conexion->query(
    'SELECT id, nombre, unidad_medida, cantidad_disponible, stock_minimo
     FROM materias_primas
     ORDER BY nombre ASC'
);
$materiasPrimas = $consulta->fetchAll();

$errores = $_SESSION['errores_materia_prima'] ?? [];
$datosAnteriores = $_SESSION['datos_materia_prima'] ?? [];
unset($_SESSION['errores_materia_prima'], $_SESSION['datos_materia_prima']);

$estado = (string) ($_GET['estado'] ?? '');
$mensajes = [
    'creada' => 'Materia prima registrada correctamente.',
    'actualizada' => 'Materia prima actualizada correctamente.',
    'eliminada' => 'Materia prima eliminada correctamente.',
];
$mensaje = $mensajes[$estado] ?? null;

function escapar(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Materias primas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Materias primas</h1>
            <p class="text-secondary mb-0">Registro y consulta de existencias.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="movimientos.php">Movimientos</a>
            <a class="btn btn-outline-secondary" href="../../index.php">Inicio</a>
        </div>
    </div>

    <?php if ($mensaje !== null): ?>
        <div class="alert alert-success" role="alert"><?= escapar($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($errores !== []): ?>
        <div class="alert alert-danger" role="alert">
            <strong>No se pudo completar la operación.</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errores as $error): ?>
                    <li><?= escapar((string) $error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <section class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5">Registrar materia prima</h2>
            <form action="guardar.php" method="post" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input class="form-control" id="nombre" name="nombre" maxlength="100" required
                        value="<?= escapar((string) ($datosAnteriores['nombre'] ?? '')) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="unidad_medida">Unidad</label>
                    <select class="form-select" id="unidad_medida" name="unidad_medida" required>
                        <option value="">Seleccione</option>
                        <?php foreach (obtenerUnidadesPermitidas() as $unidad): ?>
                            <option value="<?= escapar($unidad) ?>"
                                <?= (($datosAnteriores['unidad_medida'] ?? '') === $unidad) ? 'selected' : '' ?>>
                                <?= escapar($unidad) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="cantidad_disponible">Existencia</label>
                    <input class="form-control" id="cantidad_disponible" name="cantidad_disponible"
                        type="number" min="0" step="0.01" required
                        value="<?= escapar((string) ($datosAnteriores['cantidad_disponible'] ?? '0')) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="stock_minimo">Stock mínimo</label>
                    <input class="form-control" id="stock_minimo" name="stock_minimo"
                        type="number" min="0" step="0.01" required
                        value="<?= escapar((string) ($datosAnteriores['stock_minimo'] ?? '0')) ?>">
                </div>

                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Guardar materia prima</button>
                </div>
            </form>
        </div>
    </section>

    <section class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5">Existencias registradas</h2>

            <?php if ($materiasPrimas === []): ?>
                <p class="text-secondary mb-0">No hay materias primas registradas.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Unidad</th>
                            <th class="text-end">Existencia</th>
                            <th class="text-end">Stock mínimo</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($materiasPrimas as $materiaPrima): ?>
                            <tr>
                                <td><?= escapar((string) $materiaPrima['nombre']) ?></td>
                                <td><?= escapar((string) $materiaPrima['unidad_medida']) ?></td>
                                <td class="text-end"><?= escapar(number_format((float) $materiaPrima['cantidad_disponible'], 2, '.', '')) ?></td>
                                <td class="text-end"><?= escapar(number_format((float) $materiaPrima['stock_minimo'], 2, '.', '')) ?></td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="editar.php?id=<?= (int) $materiaPrima['id'] ?>">Editar</a>
                                    <form action="eliminar.php" method="post" class="d-inline">
                                        <input type="hidden" name="id" value="<?= (int) $materiaPrima['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
