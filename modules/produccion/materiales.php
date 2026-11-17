<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

// Lista de alistamiento: materiales de todos los planes pendientes de un día.

$gestorProduccion = new GestorProduccion($conexion);
$fecha = leerParametroFecha('fecha', obtenerFechaHoy());
$planes = $gestorProduccion->listarProducciones($fecha, $fecha, 'PLANEADA', false);
$materiales = $gestorProduccion->resumirMaterialesPorFecha($fecha);
$totalFaltantes = count(array_filter($materiales, static fn (array $fila): bool => $fila['faltante'] > 0));

$tituloPagina = 'Materiales para ' . describirDia($fecha);
$seccionActiva = 'produccion';
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<nav aria-label="Ruta de navegación" class="no-imprimir">
  <ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="<?= escapar(url('modules/produccion/index.php')) ?>">Producción</a></li>
    <li class="breadcrumb-item active" aria-current="page">Materiales del día</li>
  </ol>
</nav>

<div class="encabezado-pagina">
  <div>
    <h1>Materiales para <?= escapar(mb_strtolower(describirDia($fecha))) ?></h1>
    <p><?= escapar(ucfirst(formatearFechaLarga($fecha))) ?> · lista de alistamiento con lo que necesitan los planes pendientes.</p>
  </div>
  <div class="acciones">
    <form method="get" class="d-flex gap-2">
      <label class="visually-hidden" for="fecha">Fecha</label>
      <input type="date" class="form-control" id="fecha" name="fecha" value="<?= escapar($fecha) ?>" data-enviar-al-cambiar>
    </form>
    <button type="button" class="btn btn-outline-primary" data-imprimir>
      <i class="bi bi-printer" aria-hidden="true"></i> Imprimir
    </button>
  </div>
</div>

<?php if ($planes === []) : ?>
  <div class="tarjeta estado-vacio">
    <i class="bi bi-calendar3" aria-hidden="true"></i>
    <p class="mb-3">No hay planes pendientes para este día.</p>
    <a class="btn btn-primary no-imprimir" href="<?= escapar(url('modules/produccion/plan.php?fecha=' . $fecha)) ?>">Planificar producción</a>
  </div>
<?php else : ?>
  <div class="row g-4">
    <div class="col-lg-4">
      <div class="tarjeta">
        <div class="card-header">Qué se va a producir</div>
        <ul class="list-group list-group-flush">
          <?php foreach ($planes as $plan) : ?>
            <li class="list-group-item d-flex justify-content-between gap-2">
              <a href="<?= escapar(url('modules/produccion/detalle.php?id=' . $plan['id'])) ?>"><?= escapar($plan['receta']) ?></a>
              <span class="numero"><?= escapar(pluralizar($plan['cantidad_planeada'], 'unidad', 'unidades')) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <div class="col-lg-8">
      <?php if ($totalFaltantes > 0) : ?>
        <div class="alert alert-danger d-flex gap-2">
          <i class="bi bi-cart-x" aria-hidden="true"></i>
          <div>
            <?= escapar(pluralizar($totalFaltantes, 'material no alcanza', 'materiales no alcanzan')) ?> para todo lo planeado,
            aun contando los pedidos que llegan hasta ese día.
          </div>
        </div>
      <?php endif; ?>
      <div class="tarjeta">
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th scope="col" class="no-imprimir" style="width: 2rem"><span class="visually-hidden">Listo</span></th>
                <th scope="col">Material</th>
                <th scope="col" class="text-end">Total a sacar</th>
                <th scope="col" class="text-end">Hay hoy</th>
                <th scope="col" class="text-end">Falta</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($materiales as $indice => $fila) : ?>
                <tr class="<?= $fila['faltante'] > 0 ? 'fila-peligro' : '' ?>">
                  <td class="no-imprimir">
                    <input class="form-check-input" type="checkbox" id="listo<?= $indice ?>" aria-label="Marcar <?= escapar($fila['nombre']) ?> como alistado">
                  </td>
                  <td><label for="listo<?= $indice ?>"><?= escapar($fila['nombre']) ?></label></td>
                  <td class="numero fw-semibold"><?= escapar(formatearCantidadConUnidad($fila['cantidad_requerida'], $fila['unidad_medida'])) ?></td>
                  <td class="numero"><?= escapar(formatearCantidadConUnidad($fila['cantidad_disponible'], $fila['unidad_medida'])) ?></td>
                  <td class="numero">
                    <?php if ($fila['faltante'] > 0) : ?>
                      <span class="texto-faltante"><?= escapar(formatearCantidadConUnidad($fila['faltante'], $fila['unidad_medida'])) ?></span>
                    <?php elseif ($fila['faltante_sin_pedidos'] > 0) : ?>
                      <span class="texto-pedido"><i class="bi bi-truck" aria-hidden="true"></i> llega pedido</span>
                    <?php else : ?>
                      <i class="bi bi-check2 text-success" aria-label="Completo"></i>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <p class="texto-ayuda mt-2">
        «Falta» ya tiene en cuenta los planes pendientes de días anteriores y los pedidos que llegan hasta este día.
      </p>
    </div>
  </div>
<?php endif; ?>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
