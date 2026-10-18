<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/acceso.php';
exigirAcceso();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/calculo-funciones.php';
require_once __DIR__ . '/vista-comun.php';
$recetas = [];
$error = '';
try {
    $recetas = obtenerRecetasPosibles($conexion);
} catch (Throwable $problema) {
    error_log('No se pudieron consultar las recetas posibles: ' . $problema->getMessage());
    http_response_code(503);
    $error = 'No se pudo consultar el inventario en este momento.';
}
mostrarInicioProduccion('Recetas posibles');
?>
<p>Una receta es posible si alcanza el inventario actual para al menos una unidad. Los pendientes no cuentan.</p>
<p>Cada receta se evalúa por separado. La lista no reserva ingredientes ni garantiza producirlas todas a la vez.</p>
<?php if ($error !== ''): ?><p class="alert alert-danger" role="alert"><?= escaparProduccion($error) ?></p>
<?php elseif ($recetas === []): ?><p role="status">No hay recetas registradas.</p>
<?php else: ?>
<div class="table-responsive"><table class="table" id="recetas-posibles"><caption>Viabilidad por receta con las existencias actuales</caption>
<thead><tr><th>Receta</th><th>Estado</th><th>Unidades posibles</th></tr></thead><tbody>
    <?php foreach ($recetas as $receta): ?>
<tr data-receta-id="<?= (int) $receta['id'] ?>"><td><a href="index.php?receta_id=<?= (int) $receta['id'] ?>"><?= escaparProduccion($receta['nombre']) ?></a></td>
<td><?= $receta['error'] !== '' ? 'Revisar receta: ' . escaparProduccion($receta['error']) : ($receta['es_posible'] ? 'Posible' : 'No posible') ?></td>
<td><?= $receta['maximo_unidades'] === null ? 'Sin cálculo' : (int) $receta['maximo_unidades'] ?></td></tr>
    <?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</main></body></html>
