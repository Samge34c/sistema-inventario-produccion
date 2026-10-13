<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/movimientos-funciones.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    exit('Identificador de materia prima inválido.');
}

$sentencia = $conexion->prepare(
    'SELECT id, nombre, unidad_medida, cantidad_disponible, stock_minimo
     FROM materias_primas
     WHERE id = :id'
);
$sentencia->execute(['id' => $id]);
$materiaPrima = $sentencia->fetch();

if ($materiaPrima === false) {
    http_response_code(404);
    exit('Materia prima no encontrada.');
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'nombre' => trim((string) ($_POST['nombre'] ?? '')),
        'unidad_medida' => trim((string) ($_POST['unidad_medida'] ?? '')),
        'cantidad_disponible' => (string) ($_POST['cantidad_disponible'] ?? ''),
        'stock_minimo' => (string) ($_POST['stock_minimo'] ?? ''),
    ];

    $errores = validarMateriaPrima($datos);

    if ($errores === []) {
        try {
            actualizarMateriaPrimaProtegida($conexion, $id, $datos);
            header('Location: index.php?estado=actualizada', true, 303);
            exit;
        } catch (DomainException $error) {
            $errores[] = $error->getMessage();
        } catch (Throwable $error) {
            error_log('No se pudo editar la materia prima: ' . $error->getMessage());
            $errores[] = 'No se pudo guardar la materia prima. Revise la conexión y la migración HU02.';
        }
    }

    $materiaPrima = array_merge($materiaPrima, $datos);
}

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
    <title>Editar materia prima</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-4">
    <div class="card shadow-sm mx-auto" style="max-width: 760px">
        <div class="card-body">
            <h1 class="h4">Editar materia prima</h1>

            <?php if ($errores !== []): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?= escapar((string) $error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="row g-3">
                <input type="hidden" name="id" value="<?= (int) $materiaPrima['id'] ?>">

                <div class="col-md-6">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input class="form-control" id="nombre" name="nombre" maxlength="100" required
                        value="<?= escapar((string) $materiaPrima['nombre']) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="unidad_medida">Unidad</label>
                    <select class="form-select" id="unidad_medida" name="unidad_medida" required>
                        <?php foreach (obtenerUnidadesPermitidas() as $unidad): ?>
                            <option value="<?= escapar($unidad) ?>"
                                <?= ((string) $materiaPrima['unidad_medida'] === $unidad) ? 'selected' : '' ?>>
                                <?= escapar($unidad) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="cantidad_disponible">Existencia</label>
                    <input class="form-control" id="cantidad_disponible" name="cantidad_disponible"
                        type="number" min="0" step="0.01" required
                        value="<?= escapar((string) $materiaPrima['cantidad_disponible']) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="stock_minimo">Stock mínimo</label>
                    <input class="form-control" id="stock_minimo" name="stock_minimo"
                        type="number" min="0" step="0.01" required
                        value="<?= escapar((string) $materiaPrima['stock_minimo']) ?>">
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Guardar cambios</button>
                    <a class="btn btn-outline-secondary" href="index.php">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</main>
</body>
</html>
