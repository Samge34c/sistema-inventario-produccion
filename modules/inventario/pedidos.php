<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

$gestorPedidos = new GestorPedidos($conexion);
$estado = in_array(leerParametro('estado'), ['RECIBIDO', 'CANCELADO'], true) ? leerParametro('estado') : 'PENDIENTE';
$pedidos = $gestorPedidos->listarPedidos($estado);
$totalPendientes = $estado === 'PENDIENTE' ? count($pedidos) : count($gestorPedidos->listarPedidos());
$totalAtrasados = count(array_filter($pedidos, static fn (array $pedido): bool => $pedido['esta_atrasado']));

$tituloPagina = 'Pedidos por llegar';
$seccionActiva = 'inventario';
$scriptsPagina = ['assets/js/inventario-pedidos.js'];
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<div class="encabezado-pagina">
  <div>
    <h1>Inventario</h1>
  </div>
  <div class="acciones">
    <a class="btn btn-primary" href="<?= escapar(url('modules/inventario/pedido.php')) ?>">
      <i class="bi bi-truck" aria-hidden="true"></i> Registrar pedido
    </a>
  </div>
</div>

<?= generarPestanas(require __DIR__ . '/pestanas.php', 'pedidos') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div class="btn-group" role="group" aria-label="Estado de los pedidos">
    <?php foreach (['PENDIENTE' => 'Pendientes (' . $totalPendientes . ')', 'RECIBIDO' => 'Recibidos', 'CANCELADO' => 'Cancelados'] as $valor => $texto) : ?>
      <a class="btn btn-sm <?= $estado === $valor ? 'btn-primary' : 'btn-outline-primary' ?>"
        href="<?= escapar(url('modules/inventario/pedidos.php' . ($valor === 'PENDIENTE' ? '' : '?estado=' . $valor))) ?>"><?= escapar($texto) ?></a>
    <?php endforeach; ?>
  </div>
  <?php if ($totalAtrasados > 0) : ?>
    <span class="text-danger small fw-semibold">
      
      <?= escapar(pluralizar($totalAtrasados, 'pedido atrasado', 'pedidos atrasados')) ?>.
    </span>
  <?php endif; ?>
</div>

<div class="tarjeta">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th scope="col">Materia prima</th>
          <th scope="col" class="text-end">Cantidad</th>
          <th scope="col">Proveedor</th>
          <th scope="col"><?= $estado === 'RECIBIDO' ? 'Recibido' : 'Llegada estimada' ?></th>
          <?php if ($estado === 'PENDIENTE') : ?>
            <th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pedidos as $pedido) : ?>
          <tr class="<?= $pedido['esta_atrasado'] ? 'fila-alerta' : '' ?>">
            <td>
              <div class="fw-semibold"><?= escapar($pedido['materia_prima']) ?></div>
              <div class="small text-body-secondary">
                Pedido #<?= (int) $pedido['id'] ?> - <?= escapar(formatearFecha($pedido['fecha_registro'])) ?>
                <?= $pedido['observacion'] ? ' - ' . escapar($pedido['observacion']) : '' ?>
              </div>
            </td>
            <td class="numero"><?= escapar(formatearCantidadConUnidad($pedido['cantidad'], $pedido['unidad_medida'])) ?></td>
            <td><?= escapar($pedido['proveedor'] ?? '-') ?></td>
            <td class="text-nowrap">
              <?php if ($estado === 'RECIBIDO') : ?>
                <?= escapar(formatearFechaHora((string) $pedido['fecha_recepcion'])) ?>
              <?php else : ?>
                <div><?= escapar(describirDia($pedido['fecha_estimada_llegada'])) ?></div>
                <?php if ($pedido['esta_atrasado']) : ?>
                  <div class="small text-danger fw-semibold">
                    Atrasado <?= escapar(pluralizar(-$pedido['dias_para_llegada'], 'día', 'días')) ?>
                  </div>
                <?php endif; ?>
              <?php endif; ?>
            </td>
            <?php if ($estado === 'PENDIENTE') : ?>
              <td class="text-end text-nowrap">
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalRecibir"
                  data-pedido-id="<?= (int) $pedido['id'] ?>"
                  data-materia="<?= escapar($pedido['materia_prima']) ?>"
                  data-cantidad="<?= escapar(formatearValorCampo($pedido['cantidad'])) ?>"
                  data-cantidad-texto="<?= escapar(formatearCantidad($pedido['cantidad'])) ?>"
                  data-unidad="<?= escapar(obtenerNombreUnidad($pedido['unidad_medida'])) ?>">
                  Recibir
                </button>
                <form method="post" class="d-inline" action="<?= escapar(url('modules/inventario/cancelar-pedido.php')) ?>"
                  data-confirmar="¿Cancelar el pedido de <?= escapar(formatearCantidadConUnidad($pedido['cantidad'], $pedido['unidad_medida'])) ?> de <?= escapar(mb_strtolower($pedido['materia_prima'])) ?>? Dejará de contarse en la planificación."
                  data-confirmar-boton="Cancelar pedido">
                  <?= generarCampoCsrf() ?>
                  <input type="hidden" name="id" value="<?= (int) $pedido['id'] ?>">
                  <button class="btn btn-sm btn-outline-secondary" type="submit">Cancelar</button>
                </form>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
        <?php if ($pedidos === []) : ?>
          <tr>
            <td colspan="5" class="estado-vacio">
              
              <?= match ($estado) {
                  'RECIBIDO' => 'Todavía no se ha recibido ningún pedido.',
                  'CANCELADO' => 'No hay pedidos cancelados.',
                  default => 'No hay pedidos pendientes por llegar.',
              } ?>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalRecibir" tabindex="-1" aria-labelledby="modalRecibirTitulo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="post" action="<?= escapar(url('modules/inventario/recibir-pedido.php')) ?>">
      <?= generarCampoCsrf() ?>
      <input type="hidden" name="id" id="recibirPedidoId">
      <div class="modal-header">
        <h2 class="modal-title fs-5" id="modalRecibirTitulo">Recibir pedido</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p id="recibirDescripcion"></p>
        <label class="form-label" for="cantidad_recibida">Cantidad que llegó</label>
        <div class="input-group">
          <input type="number" class="form-control" id="cantidad_recibida" name="cantidad_recibida" min="0.01" step="0.01" required>
          <span class="input-group-text" id="recibirUnidad"></span>
        </div>
        <div class="form-text">Escriba la cantidad que llegó.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Volver</button>
        <button type="submit" class="btn btn-success">Confirmar recepción</button>
      </div>
    </form>
  </div>
</div>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
