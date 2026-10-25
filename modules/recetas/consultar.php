<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/acceso.php';
exigirAcceso();
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/funciones.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    exit('Identificador de receta inválido.');
}
$receta = obtenerReceta($conexion, $id);
if ($receta === null) {
    http_response_code(404);
    exit('Receta no encontrada.');
}
function escaparReceta(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= escaparReceta($receta['nombre']) ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-4">
<a href="index.php">Volver a recetas</a><h1 class="h3 mt-3"><?= escaparReceta($receta['nombre']) ?></h1>
<p><?= escaparReceta($receta['descripcion'] ?? '') ?></p>
<p>Cantidades necesarias por unidad del producto.</p>
<table class="table"><thead><tr><th>Ingrediente</th><th>Cantidad</th><th>Unidad</th></tr></thead><tbody>
<?php foreach ($receta['ingredientes'] as $ingrediente): ?><tr><td><?= escaparReceta($ingrediente['nombre']) ?></td><td><?= escaparReceta($ingrediente['cantidad']) ?></td><td><?= escaparReceta($ingrediente['unidad_medida']) ?></td></tr><?php endforeach; ?>
</tbody></table>
</main></body></html>
