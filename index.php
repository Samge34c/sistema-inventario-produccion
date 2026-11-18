<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/inicio.php';

$conexion = obtenerConexion();

$inventario = new GestorInventario($conexion);
$gestorProduccion = new GestorProduccion($conexion);
$gestorVentas = new GestorVentas($conexion);

$hoy = obtenerFechaHoy();
$manana = sumarDias($hoy, 1);
$materiasEnAlerta = $inventario->listarMateriasEnAlerta();
$pedidosPendientes = (new GestorPedidos($conexion))->listarPedidos();
$pedidosAtrasados = array_values(array_filter($pedidosPendientes, static fn (array $pedido): bool => $pedido['esta_atrasado']));
$producciones = array_values(array_filter(
    $gestorProduccion->listarProducciones($hoy, $manana),
    static fn (array $produccion): bool => $produccion['estado'] !== 'CANCELADA'
));
$planesPendientes = array_values(array_filter($producciones, static fn (array $plan): bool => $plan['estado'] === 'PLANEADA'));
$ventasHoy = $gestorVentas->obtenerResumenDia($hoy);

// Lista de pendientes, de lo más urgente a lo menos urgente.
$pendientes = [];

foreach ($planesPendientes as $plan) {
    $cuando = $plan['esta_atrasada'] ? 'era para ' . mb_strtolower(describirDia($plan['fecha_planeada'])) : mb_strtolower(describirDia($plan['fecha_planeada']));

    if (($plan['analisis']['estado'] ?? '') === CalculadoraProduccion::FALTAN_MATERIALES) {
        $pendientes[] = [
            'nivel' => 'peligro',
            'texto' => sprintf('%s (%s): faltan %s.', $plan['receta'], $cuando, describirFaltantes($plan['analisis'])),
            'accion' => ['Ver plan', 'modules/produccion/detalle.php?id=' . $plan['id']],
        ];
    } elseif ($plan['esta_atrasada']) {
        $pendientes[] = [
            'nivel' => 'aviso',
            'texto' => sprintf('%s %s y no se ha registrado como producido ni cancelado.', $plan['receta'], $cuando),
            'accion' => ['Registrar', 'modules/produccion/detalle.php?id=' . $plan['id'] . '#registrar'],
        ];
    }
}

foreach ($pedidosAtrasados as $pedido) {
    $pendientes[] = [
        'nivel' => 'aviso',
        'texto' => sprintf(
            'El pedido de %s de %s%s debía llegar %s.',
            formatearCantidadConUnidad($pedido['cantidad'], $pedido['unidad_medida']),
            mb_strtolower($pedido['materia_prima']),
            $pedido['proveedor'] ? ' (' . $pedido['proveedor'] . ')' : '',
            mb_strtolower(describirDia($pedido['fecha_estimada_llegada']))
        ),
        'accion' => ['Recibir', 'modules/inventario/pedidos.php'],
    ];
}

foreach ($materiasEnAlerta as $materia) {
    $estaAgotada = $materia['estado'] === GestorInventario::SIN_EXISTENCIAS;
    $textoPedido = $materia['cantidad_por_llegar'] > 0
        ? sprintf(' Ya hay un pedido de %s.', formatearCantidadConUnidad($materia['cantidad_por_llegar'], $materia['unidad_medida']))
        : '';
    $pendientes[] = [
        'nivel' => $materia['cantidad_por_llegar'] > 0 ? 'info' : ($estaAgotada ? 'peligro' : 'aviso'),
        'texto' => $estaAgotada
            ? sprintf('%s está agotado.%s', $materia['nombre'], $textoPedido)
            : sprintf(
                '%s está bajo el mínimo: hay %s y el mínimo es %s.%s',
                $materia['nombre'],
                formatearCantidadConUnidad($materia['cantidad_disponible'], $materia['unidad_medida']),
                formatearCantidadConUnidad($materia['stock_minimo'], $materia['unidad_medida']),
                $textoPedido
            ),
        'accion' => $materia['cantidad_por_llegar'] > 0
            ? ['Ver pedidos', 'modules/inventario/pedidos.php']
            : ['Pedir', 'modules/inventario/pedido.php?materia_prima_id=' . $materia['id']],
    ];
}

$ordenNivel = ['peligro' => 0, 'aviso' => 1, 'info' => 2];
$iconosNivel = ['peligro' => 'bi-exclamation-triangle-fill', 'aviso' => 'bi-clock-fill', 'info' => 'bi-info-circle-fill'];
usort($pendientes, static fn (array $a, array $b): int => $ordenNivel[$a['nivel']] <=> $ordenNivel[$b['nivel']]);

$tituloPagina = 'Inicio';
$seccionActiva = 'inicio';
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<div class="encabezado-pagina">
  <div>
    <h1>Hola, hoy es <?= escapar(formatearFechaLarga($hoy)) ?></h1>
    <p>
      Llevas <?= escapar(pluralizar((int) $ventasHoy['unidades'], 'unidad vendida', 'unidades vendidas')) ?>
      en <?= escapar(pluralizar($ventasHoy['transacciones'], 'venta', 'ventas')) ?>.
    </p>
  </div>
  <div class="acciones">
    <a class="btn btn-primary btn-lg" href="<?= escapar(url('modules/ventas/index.php')) ?>"><i class="bi bi-cart-plus" aria-hidden="true"></i> Registrar venta</a>
    <a class="btn btn-outline-primary btn-lg" href="<?= escapar(url('modules/produccion/plan.php')) ?>"><i class="bi bi-calendar-plus" aria-hidden="true"></i> Planificar</a>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <h2 class="titulo-seccion"><i class="bi bi-bell" aria-hidden="true"></i> Para atender</h2>
    <?php if ($pendientes === []) : ?>
      <p class="aviso-tranquilo"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Todo en orden: no hay nada pendiente.</p>
    <?php else : ?>
      <ul class="lista-atender">
        <?php foreach (array_slice($pendientes, 0, 4) as $pendiente) : ?>
          <li class="nivel-<?= $pendiente['nivel'] ?>">
            <i class="bi <?= $iconosNivel[$pendiente['nivel']] ?> icono-nivel" aria-hidden="true"></i>
            <span class="flex-grow-1"><?= escapar($pendiente['texto']) ?></span>
            <a href="<?= escapar(url($pendiente['accion'][1])) ?>"><?= escapar($pendiente['accion'][0]) ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php if (count($pendientes) > 4) : ?>
        <details class="ver-mas">
          <summary>Ver <?= count($pendientes) - 4 ?> más</summary>
          <ul class="lista-atender">
            <?php foreach (array_slice($pendientes, 4) as $pendiente) : ?>
              <li class="nivel-<?= $pendiente['nivel'] ?>">
                <i class="bi <?= $iconosNivel[$pendiente['nivel']] ?> icono-nivel" aria-hidden="true"></i>
                <span class="flex-grow-1"><?= escapar($pendiente['texto']) ?></span>
                <a href="<?= escapar(url($pendiente['accion'][1])) ?>"><?= escapar($pendiente['accion'][0]) ?></a>
              </li>
            <?php endforeach; ?>
          </ul>
        </details>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="col-lg-5">
    <h2 class="titulo-seccion"><i class="bi bi-fire" aria-hidden="true"></i> Producción de hoy y mañana</h2>
    <?php if ($producciones === []) : ?>
      <p class="aviso-tranquilo">No hay producción planificada para hoy ni mañana.</p>
    <?php else : ?>
      <ul class="lista-atender">
        <?php foreach ($producciones as $plan) : ?>
          <li>
            <img class="miniatura" src="<?= escapar(urlImagenProducto($plan['receta'])) ?>" alt="" width="36" height="36">
            <span class="flex-grow-1">
              <a href="<?= escapar(url('modules/produccion/detalle.php?id=' . $plan['id'])) ?>"><?= escapar($plan['receta']) ?></a>
              <span class="small text-body-secondary">
                <?= escapar(describirDia($plan['fecha_planeada'])) ?>,
                <?= escapar(formatearCantidad((int) ($plan['cantidad_producida'] ?? $plan['cantidad_planeada']))) ?> unidades
              </span>
            </span>
            <?= $plan['analisis'] !== null
                ? generarInsigniaAbastecimiento($plan['analisis']['estado'])
                : generarInsigniaProduccion($plan['estado'], $plan['esta_atrasada']) ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <p class="small"><a href="<?= escapar(url('modules/produccion/index.php')) ?>">Ver toda la agenda</a></p>
  </div>
</div>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
