<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/inicio.php';

$conexion = obtenerConexion();

$inventario = new GestorInventario($conexion);
$gestorProduccion = new GestorProduccion($conexion);
$gestorVentas = new GestorVentas($conexion);
$gestorRecetas = new GestorRecetas($conexion);

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
$planesDeHoy = array_filter($planesPendientes, static fn (array $plan): bool => $plan['fecha_planeada'] <= $hoy);
$planesConFaltantes = array_values(array_filter(
    $planesPendientes,
    static fn (array $plan): bool => ($plan['analisis']['estado'] ?? '') === CalculadoraProduccion::FALTAN_MATERIALES
));
$ventasHoy = $gestorVentas->obtenerResumenDia($hoy);
$ventasSemanaPasada = $gestorVentas->obtenerResumenDia(sumarDias($hoy, -7));
$recetasPosibles = array_values(array_filter($gestorRecetas->listarRecetas(), static fn (array $receta): bool => $receta['puede_producirse']));
usort($recetasPosibles, static fn (array $a, array $b): int => $b['capacidad']['unidades_maximas'] <=> $a['capacidad']['unidades_maximas']);

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
usort($pendientes, static fn (array $a, array $b): int => $ordenNivel[$a['nivel']] <=> $ordenNivel[$b['nivel']]);
$coloresNivel = ['peligro' => 'estado-mal', 'aviso' => 'estado-aviso', 'info' => ''];

$tituloPagina = 'Inicio';
$seccionActiva = 'inicio';
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<div class="encabezado-pagina">
  <div>
    <h1>Inicio</h1>
    <p><?= escapar(ucfirst(formatearFechaLarga($hoy))) ?></p>
  </div>
  <div class="acciones">
    <a class="btn btn-outline-primary" href="<?= escapar(url('modules/produccion/plan.php')) ?>">
      Planificar
    </a>
    <a class="btn btn-primary" href="<?= escapar(url('modules/ventas/index.php')) ?>">
      Registrar venta
    </a>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <h2 class="titulo-seccion">Para atender</h2>
    <?php if ($pendientes === []) : ?>
      <p>No hay pendientes.</p>
    <?php else : ?>
      <table class="table table-bordered">
        <?php foreach ($pendientes as $pendiente) : ?>
          <tr>
            <td class="<?= $coloresNivel[$pendiente['nivel']] ?>"><?= escapar($pendiente['texto']) ?></td>
            <td class="text-nowrap"><a href="<?= escapar(url($pendiente['accion'][1])) ?>"><?= escapar($pendiente['accion'][0]) ?></a></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>

    <h2 class="titulo-seccion">Se puede hornear ahora</h2>
    <?php if ($recetasPosibles === []) : ?>
      <p>Con la existencia actual no alcanza para ninguna receta.</p>
    <?php else : ?>
      <table class="table table-bordered">
        <thead>
          <tr><th scope="col">Receta</th><th scope="col" class="numero">Hasta</th><th scope="col">Limita</th></tr>
        </thead>
        <?php foreach (array_slice($recetasPosibles, 0, 5) as $receta) : ?>
          <tr>
            <td><a href="<?= escapar(url('modules/recetas/detalle.php?id=' . $receta['id'])) ?>"><?= escapar($receta['nombre']) ?></a></td>
            <td class="numero"><?= escapar(pluralizar($receta['capacidad']['unidades_maximas'], 'unidad', 'unidades')) ?></td>
            <td><?= escapar($receta['capacidad']['limitante']['nombre']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>

  <div class="col-lg-5">
    <h2 class="titulo-seccion">Resumen de hoy</h2>
    <table class="table table-bordered tabla-resumen">
      <tr>
        <th scope="row">Ventas</th>
        <td>
          <a href="<?= escapar(url('modules/ventas/reportes.php?tipo=diario')) ?>"><?= escapar(pluralizar((int) $ventasHoy['unidades'], 'unidad', 'unidades')) ?></a>
          en <?= escapar(pluralizar($ventasHoy['transacciones'], 'venta', 'ventas')) ?>
          <?php if ($ventasSemanaPasada['unidades'] > 0) : ?>
            <div class="small text-body-secondary">El <?= escapar(DIAS_SEMANA_LARGOS[(int) date('w')]) ?> pasado: <?= escapar(formatearCantidad($ventasSemanaPasada['unidades'])) ?> en todo el día</div>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th scope="row">Producción pendiente hoy</th>
        <td>
          <a href="<?= escapar(url('modules/produccion/index.php')) ?>"><?= escapar(pluralizar(count($planesDeHoy), 'plan', 'planes')) ?></a>
          <?php if ($planesConFaltantes !== []) : ?>
            <span class="estado-mal">(<?= count($planesConFaltantes) ?> con faltantes)</span>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th scope="row">Materias primas por reabastecer</th>
        <td><a href="<?= escapar(url('modules/inventario/index.php?estado=ALERTA')) ?>"><?= count($materiasEnAlerta) ?></a></td>
      </tr>
      <tr>
        <th scope="row">Pedidos por llegar</th>
        <td>
          <a href="<?= escapar(url('modules/inventario/pedidos.php')) ?>"><?= count($pedidosPendientes) ?></a>
          <?php if ($pedidosAtrasados !== []) : ?>
            <span class="estado-mal">(<?= escapar(pluralizar(count($pedidosAtrasados), 'atrasado', 'atrasados')) ?>)</span>
          <?php endif; ?>
        </td>
      </tr>
    </table>

    <h2 class="titulo-seccion">Producción de hoy y mañana <a class="small fw-normal" href="<?= escapar(url('modules/produccion/index.php')) ?>">(ver agenda)</a></h2>
    <?php if ($producciones === []) : ?>
      <p>No hay producción planificada para hoy ni mañana.</p>
    <?php else : ?>
      <table class="table table-bordered">
        <thead>
          <tr><th scope="col">Producto</th><th scope="col">Día</th><th scope="col" class="numero">Unidades</th><th scope="col">Materiales</th></tr>
        </thead>
        <?php foreach ($producciones as $plan) : ?>
          <tr>
            <td><a href="<?= escapar(url('modules/produccion/detalle.php?id=' . $plan['id'])) ?>"><?= escapar($plan['receta']) ?></a></td>
            <td><?= escapar(describirDia($plan['fecha_planeada'])) ?></td>
            <td class="numero"><?= escapar(formatearCantidad((int) ($plan['cantidad_producida'] ?? $plan['cantidad_planeada']))) ?></td>
            <td>
              <?= $plan['analisis'] !== null
                  ? generarInsigniaAbastecimiento($plan['analisis']['estado'])
                  : generarInsigniaProduccion($plan['estado'], $plan['esta_atrasada']) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
