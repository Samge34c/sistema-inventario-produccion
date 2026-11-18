<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

$gestorProduccion = new GestorProduccion($conexion);
$produccion = $gestorProduccion->obtenerProduccion(leerParametroEntero('id'));

if ($produccion === null) {
    agregarMensaje('error', 'El plan de producción no existe.');
    redirigir('modules/produccion/index.php');
}

$esPendiente = $produccion['estado'] === 'PLANEADA';
$analisis = $produccion['analisis'];
$unidadesMaximasAhora = (int) ($produccion['capacidad_actual']['unidades_maximas'] ?? 0);
$alcanzaHoy = $unidadesMaximasAhora >= $produccion['cantidad_planeada'];

$tituloPagina = $produccion['receta'] . ' · ' . describirDia($produccion['fecha_planeada']);
$seccionActiva = 'produccion';
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<nav aria-label="Ruta de navegación">
  <ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="<?= escapar(url('modules/produccion/index.php')) ?>">Producción</a></li>
    <li class="breadcrumb-item active" aria-current="page">Plan #<?= $produccion['id'] ?></li>
  </ol>
</nav>

<div class="encabezado-pagina">
  <div>
    <h1><?= escapar($produccion['receta']) ?> · <?= escapar(pluralizar($produccion['cantidad_planeada'], 'unidad', 'unidades')) ?></h1>
    <p class="d-flex flex-wrap gap-2 align-items-center">
      <span><i class="bi bi-calendar3" aria-hidden="true"></i> <?= escapar(ucfirst(formatearFechaLarga($produccion['fecha_planeada']))) ?></span>
      <?= generarInsigniaProduccion($produccion['estado'], $produccion['esta_atrasada']) ?>
      <?= $analisis !== null ? generarInsigniaAbastecimiento($analisis['estado']) : '' ?>
    </p>
    <?php if ($produccion['observacion']) : ?>
      <p class="mt-1"><i class="bi bi-chat-left-text" aria-hidden="true"></i> <?= escapar($produccion['observacion']) ?></p>
    <?php endif; ?>
  </div>
  <?php if ($esPendiente) : ?>
    <div class="acciones">
      <a class="btn btn-outline-primary" href="<?= escapar(url('modules/produccion/plan.php?id=' . $produccion['id'])) ?>">
        <i class="bi bi-pencil" aria-hidden="true"></i> Modificar
      </a>
      <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalCancelar">
        <i class="bi bi-slash-circle" aria-hidden="true"></i> Cancelar plan
      </button>
    </div>
  <?php endif; ?>
</div>

<?php if ($esPendiente && $analisis !== null) : ?>
  <div class="row g-4">
    <div class="col-xl-8">
      <div class="tarjeta">
        <div class="card-header">Materiales del plan</div>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th scope="col">Ingrediente</th>
                <th scope="col" class="text-end">Necesita</th>
                <th scope="col" class="text-end">Hay hoy</th>
                <th scope="col" class="text-end" title="Lo que usan los planes pendientes anteriores a este">Planes previos</th>
                <th scope="col" class="text-end" title="Pedidos pendientes que llegan hasta la fecha del plan">Llega antes</th>
                <th scope="col" class="text-end">Falta</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($analisis['ingredientes'] as $fila) : ?>
                <?php $unidad = $fila['unidad_medida']; ?>
                <tr class="<?= $fila['faltante'] > 0 ? 'fila-peligro' : ($fila['faltante_sin_pedidos'] > 0 ? 'fila-alerta' : '') ?>">
                  <td><?= escapar($fila['nombre']) ?></td>
                  <td class="numero fw-semibold"><?= escapar(formatearCantidadConUnidad($fila['cantidad_requerida'], $unidad)) ?></td>
                  <td class="numero"><?= escapar(formatearCantidad($fila['cantidad_disponible'])) ?></td>
                  <td class="numero text-body-secondary"><?= $fila['cantidad_comprometida'] > 0 ? '−' . escapar(formatearCantidad($fila['cantidad_comprometida'])) : '—' ?></td>
                  <td class="numero <?= $fila['cantidad_por_llegar'] > 0 ? 'texto-pedido' : 'text-body-secondary' ?>">
                    <?= $fila['cantidad_por_llegar'] > 0 ? '+' . escapar(formatearCantidad($fila['cantidad_por_llegar'])) : '—' ?>
                  </td>
                  <td class="numero">
                    <?php if ($fila['faltante'] > 0) : ?>
                      <span class="texto-faltante"><?= escapar(formatearCantidadConUnidad($fila['faltante'], $unidad)) ?></span>
                    <?php elseif ($fila['faltante_sin_pedidos'] > 0) : ?>
                      <span class="texto-pedido" title="Falta si el pedido no llega a tiempo">
                        <i class="bi bi-truck" aria-hidden="true"></i> <?= escapar(formatearCantidad($fila['faltante_sin_pedidos'])) ?>
                      </span>
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
      <div class="texto-ayuda mt-2">
        <p class="mb-1">
          <strong>Cómo se calcula:</strong> lo que hay hoy, menos lo que usan los planes pendientes de fechas anteriores
          (o del mismo día, creados antes), más los pedidos que llegan hasta la fecha de este plan.
        </p>
        <?php if ($analisis['estado'] === CalculadoraProduccion::FALTAN_MATERIALES) : ?>
          <p class="mb-0">
            Para resolverlo: <a href="<?= escapar(url('modules/inventario/pedido.php')) ?>">registre un pedido</a>,
            reduzca las unidades del plan o muévalo a otra fecha.
          </p>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-xl-4">
      <form method="post" action="<?= escapar(url('modules/produccion/registrar.php')) ?>" class="tarjeta p-4" id="registrar">
        <?= generarCampoCsrf() ?>
        <input type="hidden" name="id" value="<?= $produccion['id'] ?>">
        <h2 class="h5">Registrar producción</h2>
        <p class="small text-body-secondary">
          Al registrar, se descuentan del inventario los ingredientes de las unidades producidas.
        </p>
        <label class="form-label" for="unidades_producidas">Unidades que salieron</label>
        <div class="input-group mb-1">
          <input type="number" class="form-control form-control-lg" id="unidades_producidas" name="unidades_producidas"
            min="1" max="100000" step="1" required value="<?= $produccion['cantidad_planeada'] ?>">
          <span class="input-group-text">unidades</span>
        </div>
        <div class="form-text mb-3 <?= $alcanzaHoy ? '' : 'text-danger' ?>">
          Con la existencia de hoy alcanza para <?= escapar(pluralizar($unidadesMaximasAhora, 'unidad', 'unidades')) ?>.
          <?php if (!$alcanzaHoy) : ?>
            Reciba los pedidos pendientes o registre menos unidades.
          <?php endif; ?>
        </div>
        <label class="form-label" for="observacion">Observación <span class="text-body-secondary fw-normal">(opcional)</span></label>
        <input type="text" class="form-control mb-3" id="observacion" name="observacion" maxlength="255" placeholder="Ej.: se quemaron 4">
        <button class="btn btn-success w-100" type="submit" <?= $unidadesMaximasAhora === 0 ? 'disabled' : '' ?>>
          <i class="bi bi-check2-square" aria-hidden="true"></i> Registrar y descontar insumos
        </button>
      </form>
    </div>
  </div>

  <div class="modal fade" id="modalCancelar" tabindex="-1" aria-labelledby="modalCancelarTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" method="post" action="<?= escapar(url('modules/produccion/cancelar.php')) ?>">
        <?= generarCampoCsrf() ?>
        <input type="hidden" name="id" value="<?= $produccion['id'] ?>">
        <div class="modal-header">
          <h2 class="modal-title fs-5" id="modalCancelarTitulo">Cancelar plan</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <p>El plan queda en el historial como cancelado y sus materiales dejan de estar comprometidos.</p>
          <label class="form-label" for="motivo">Motivo <span class="text-body-secondary fw-normal">(opcional)</span></label>
          <input type="text" class="form-control" id="motivo" name="motivo" maxlength="255">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Volver</button>
          <button type="submit" class="btn btn-danger">Cancelar plan</button>
        </div>
      </form>
    </div>
  </div>
<?php elseif ($produccion['estado'] === 'REALIZADA') : ?>
  <div class="row g-4">
    <div class="col-lg-4">
      <div class="tarjeta indicador">
        <span class="indicador-icono bien"><i class="bi bi-check2-all" aria-hidden="true"></i></span>
        <div>
          <div class="indicador-valor"><?= escapar(formatearCantidad((int) $produccion['cantidad_producida'])) ?></div>
          <div class="indicador-etiqueta">
            unidades producidas de <?= escapar(formatearCantidad($produccion['cantidad_planeada'])) ?> planeadas
            · <?= escapar(formatearFechaHora((string) $produccion['fecha_realizacion'])) ?>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-8">
      <div class="tarjeta">
        <div class="card-header">Insumos descontados del inventario</div>
        <table class="table align-middle">
          <tbody>
            <?php foreach ($produccion['consumos'] as $consumo) : ?>
              <tr>
                <td><?= escapar($consumo['nombre']) ?></td>
                <td class="numero text-danger">−<?= escapar(formatearCantidadConUnidad((float) $consumo['cantidad'], $consumo['unidad_medida'])) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if ($produccion['consumos'] === []) : ?>
              <tr><td class="estado-vacio">No hay movimientos asociados a esta producción.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php else : ?>
  <div class="tarjeta estado-vacio">
    <i class="bi bi-slash-circle" aria-hidden="true"></i>
    Este plan fue cancelado y no descontó insumos del inventario.
  </div>
<?php endif; ?>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
