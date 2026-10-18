<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/acceso.php';
exigirAcceso();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/calculo-funciones.php';
require_once __DIR__ . '/vista-comun.php';
$recetas = $conexion->query('SELECT id, nombre FROM recetas ORDER BY nombre, id')->fetchAll(PDO::FETCH_ASSOC);
$id = filter_input(INPUT_GET, 'receta_id', FILTER_VALIDATE_INT);
$resultado = null;
$error = '';
if ($id !== null) {
    try {
        if ($id === false || $id < 1) {
            throw new DomainException('Seleccione una receta válida.');
        }
        $resultado = obtenerProyeccionProduccion($conexion, $id);
    } catch (DomainException $problema) {
        http_response_code(400);
        $error = $problema->getMessage();
    } catch (Throwable $problema) {
        error_log('No se pudo consultar la proyección: ' . $problema->getMessage());
        http_response_code(503);
        $error = 'No se pudo calcular la proyección en este momento.';
    }
}
mostrarInicioProduccion('Proyección con pendientes');
mostrarSelectorReceta($recetas, $id, 'Proyectar');
?>
<p>La proyección supone que llegan todos los materiales registrados como pendientes. No confirma su recepción ni modifica existencias.</p>
<?php if ($error !== ''): ?><p class="alert alert-danger" role="alert"><?= escaparProduccion($error) ?></p><?php endif; ?>
<?php if ($resultado !== null): ?>
<section class="card card-body"><h2 class="h5"><?= escaparProduccion($resultado['receta_nombre']) ?></h2>
<p>Producción actual: <strong id="unidades-actuales"><?= (int) $resultado['actual']['maximo_unidades'] ?></strong> unidades.</p>
<p>Proyección con pendientes: <strong id="unidades-proyectadas"><?= (int) $resultado['proyeccion']['maximo_unidades'] ?></strong> unidades.</p>
<p>Limitantes actuales: <?= escaparProduccion(implode(', ', array_column($resultado['actual']['limitantes'], 'nombre'))) ?>.</p>
<p>Limitantes de la proyección: <?= escaparProduccion(implode(', ', array_column($resultado['proyeccion']['limitantes'], 'nombre'))) ?>.</p>
<div class="table-responsive"><table class="table"><thead><tr><th>Ingrediente</th><th>Disponible</th><th>Pendiente</th><th>Necesario por unidad</th><th>Unidad</th></tr></thead><tbody>
    <?php foreach ($resultado['ingredientes'] as $ingrediente): ?>
<tr><td><?= escaparProduccion($ingrediente['nombre']) ?></td><td><?= escaparProduccion($ingrediente['cantidad_disponible']) ?></td>
<td><?= escaparProduccion($ingrediente['cantidad_pendiente']) ?></td><td><?= escaparProduccion($ingrediente['cantidad']) ?></td><td><?= escaparProduccion($ingrediente['unidad_medida']) ?></td></tr>
    <?php endforeach; ?>
</tbody></table></div></section>
<?php endif; ?>
</main></body></html>
