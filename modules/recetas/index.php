<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/acceso.php';
exigirAcceso();
require __DIR__ . '/../../config/database.php';
$materiasPrimas = $conexion->query('SELECT id, nombre, unidad_medida FROM materias_primas ORDER BY nombre')->fetchAll();
$recetas = $conexion->query('SELECT id, nombre, descripcion FROM recetas ORDER BY nombre')->fetchAll();
$errores = $_SESSION['errores_receta'] ?? [];
$datos = $_SESSION['datos_receta'] ?? [];
unset($_SESSION['errores_receta'], $_SESSION['datos_receta']);
$filas = is_array($datos['ingredientes'] ?? null) ? $datos['ingredientes'] : [[], [], []];
function escaparReceta(mixed $valor): string
{
    return htmlspecialchars(is_scalar($valor) ? (string) $valor : '', ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recetas</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-4">
<div class="d-flex justify-content-between"><h1 class="h3">Recetas e ingredientes</h1><a href="../../index.php">Inicio</a></div>
<?php if ($errores !== []): ?><div class="alert alert-danger" role="alert"><?php foreach ($errores as $error): ?><p><?= escaparReceta($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<section class="card card-body my-4"><h2 class="h5">Registrar receta</h2>
<p>Indique cantidades para una unidad del producto. La unidad es la definida para cada materia prima. Registrar la receta no descuenta existencias.</p>
<?php if ($materiasPrimas === []): ?><p>Registre primero las materias primas en Inventario.</p><?php else: ?>
<form action="guardar.php" method="post">
<input type="hidden" name="token" value="<?= obtenerTokenAcceso() ?>">
<label for="nombre">Nombre</label><input class="form-control mb-3" id="nombre" name="nombre" maxlength="100" required value="<?= escaparReceta($datos['nombre'] ?? '') ?>">
<label for="descripcion">Descripción</label><textarea class="form-control mb-3" id="descripcion" name="descripcion" maxlength="255"><?= escaparReceta($datos['descripcion'] ?? '') ?></textarea>
<div id="ingredientes">
<?php foreach ($filas as $numero => $fila): $fila = is_array($fila) ? $fila : []; ?>
<div class="row g-2 mb-3 fila-ingrediente">
<div class="col-md-8"><label>Ingrediente<select class="form-select" name="ingredientes[<?= (int) $numero ?>][materia_prima_id]">
<option value="">Seleccione</option>
<?php foreach ($materiasPrimas as $materia): ?><option value="<?= (int) $materia['id'] ?>" <?= (string) ($fila['materia_prima_id'] ?? '') === (string) $materia['id'] ? 'selected' : '' ?>><?= escaparReceta($materia['nombre']) ?> (<?= escaparReceta($materia['unidad_medida']) ?>)</option><?php endforeach; ?>
</select></label></div>
<div class="col-md-4"><label>Cantidad por unidad<input class="form-control" type="number" min="0.01" max="99999999.99" step="0.01" name="ingredientes[<?= (int) $numero ?>][cantidad]" value="<?= escaparReceta($fila['cantidad'] ?? '') ?>"></label></div>
</div>
<?php endforeach; ?>
</div>
<button class="btn btn-outline-secondary" id="agregar-ingrediente" type="button">Añadir ingrediente</button>
<button class="btn btn-primary" type="submit">Guardar receta</button>
</form>
<?php endif; ?></section>
<section class="card card-body"><h2 class="h5">Recetas registradas</h2>
<?php if ($recetas === []): ?><p>No hay recetas registradas.</p><?php else: ?><ul><?php foreach ($recetas as $receta): ?><li><a href="consultar.php?id=<?= (int) $receta['id'] ?>"><?= escaparReceta($receta['nombre']) ?></a></li><?php endforeach; ?></ul><?php endif; ?>
</section>
</main>
<script src="recetas.js" defer></script>
</body></html>
