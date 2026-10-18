<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/acceso.php';
exigirAcceso();
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/calculo-funciones.php';
require_once __DIR__ . '/vista-comun.php';
$recetas = $conexion->query('SELECT id, nombre FROM recetas ORDER BY nombre')->fetchAll();
$resultado = null;
$error = '';
$id = filter_input(INPUT_GET, 'receta_id', FILTER_VALIDATE_INT);
if ($id !== null) {
    try {
        if ($id === false || $id < 1) {
            throw new DomainException('Seleccione una receta válida.');
        }
        $resultado = obtenerProduccionMaxima($conexion, $id);
    } catch (DomainException $problema) {
        http_response_code(400);
        $error = $problema->getMessage();
    } catch (Throwable $problema) {
        error_log('No se pudo calcular la producción: ' . $problema->getMessage());
        http_response_code(503);
        $error = 'No se pudo calcular la producción en este momento.';
    }
}
mostrarInicioProduccion('Producción máxima');
mostrarSelectorReceta($recetas, $id, 'Calcular');
?>
<p>El cálculo usa existencias actuales. Los materiales pendientes no cuentan y consultar no descuenta inventario.</p>
<?php if ($error !== ''): ?><p class="alert alert-danger" role="alert"><?= escaparProduccion($error) ?></p><?php endif; ?>
<?php if ($resultado !== null): ?>
<section class="card card-body"><h2 class="h5"><?= escaparProduccion($resultado['receta_nombre']) ?></h2>
<p>Producción máxima: <strong id="maximo-unidades"><?= (int) $resultado['maximo_unidades'] ?></strong> unidades.</p>
<p>Ingredientes limitantes: <strong><?= escaparProduccion(implode(', ', array_column($resultado['limitantes'], 'nombre'))) ?></strong>.</p>
<div class="table-responsive"><table class="table"><thead><tr><th>Ingrediente</th><th>Disponible</th><th>Necesario por unidad</th><th>Unidad</th><th>Unidades posibles</th></tr></thead><tbody>
<?php foreach ($resultado['ingredientes'] as $ingrediente): ?><tr><td><?= escaparProduccion($ingrediente['nombre']) ?></td><td><?= escaparProduccion($ingrediente['cantidad_disponible']) ?></td><td><?= escaparProduccion($ingrediente['cantidad_necesaria']) ?></td><td><?= escaparProduccion($ingrediente['unidad_medida']) ?></td><td><?= (int) $ingrediente['unidades_posibles'] ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php endif; ?>
</main></body></html>
