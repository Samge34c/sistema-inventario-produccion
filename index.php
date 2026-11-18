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
            'icono' => 'bi-cart-x',
            'texto' => sprintf('%s (%s): faltan %s.', $plan['receta'], $cuando, describirFaltantes($plan['analisis'])),
            'accion' => ['Ver plan', 'modules/produccion/detalle.php?id=' . $plan['id']],
        ];
    } elseif ($plan['esta_atrasada']) {
        $pendientes[] = [
            'nivel' => 'aviso',
            'icono' => 'bi-clock-history',
            'texto' => sprintf('%s %s y no se ha registrado como producido ni cancelado.', $plan['receta'], $cuando),
            'accion' => ['Registrar', 'modules/produccion/detalle.php?id=' . $plan['id'] . '#registrar'],
        ];
    }
}

foreach ($pedidosAtrasados as $pedido) {
    $pendientes[] = [
        'nivel' => 'aviso',
        'icono' => 'bi-truck',
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
        'icono' => $estaAgotada ? 'bi-x-octagon' : 'bi-exclamation-triangle',
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
$coloresNivel = ['peligro' => 'text-danger', 'aviso' => 'text-warning-emphasis', 'info' => 'text-primary'];

$hora = (int) date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');

$tituloPagina = 'Inicio';
$seccionActiva = 'inicio';
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<div class="encabezado-pagina">
  <div>
    <h1><?= escapar($saludo) ?></h1>
    <p><?= escapar(ucfirst(formatearFechaLarga($hoy))) ?>. Esto es lo que pasa hoy en la panadería.</p>
  </div>
  <div class="acciones">
    <a class="btn btn-outline-primary" href="<?= escapar(url('modules/produccion/plan.php')) ?>">
      <i class="bi bi-calendar-plus" aria-hidden="true"></i> Planificar
    </a>
    <a class="btn btn-primary" href="<?= escapar(url('modules/ventas/index.php')) ?>">
      <i class="bi bi-shop" aria-hidden="true"></i> Registrar venta
    </a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <a class="tarjeta indicador" href="<?= escapar(url('modules/ventas/reportes.php?tipo=diario')) ?>">
      <span class="indicador-icono"><i class="bi bi-receipt" aria-hidden="true"></i></span>
      <div>
        <div class="indicador-valor"><?= escapar(formatearCantidad($ventasHoy['unidades'])) ?></div>
        <div class="indicador-etiqueta">unidades vendidas hoy en <?= escapar(pluralizar($ventasHoy['transacciones'], 'venta', 'ventas')) ?></div>
        <?php if ($ventasSemanaPasada['unidades'] > 0) : ?>
          <div class="small text-body-secondary">El <?= escapar(DIAS_SEMANA_LARGOS[(int) date('w')]) ?> pasado: <?= escapar(formatearCantidad($ventasSemanaPasada['unidades'])) ?> en todo el día</div>
        <?php endif; ?>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-xl-3">
    <a class="tarjeta indicador" href="<?= escapar(url('modules/produccion/index.php')) ?>">
      <span class="indicador-icono <?= $planesConFaltantes === [] ? 'bien' : 'alerta' ?>"><i class="bi bi-fire" aria-hidden="true"></i></span>
      <div>
        <div class="indicador-valor"><?= count($planesDeHoy) ?></div>
        <div class="indicador-etiqueta"><?= count($planesDeHoy) === 1 ? 'plan de producción pendiente hoy' : 'planes de producción pendientes hoy' ?></div>
        <div class="small <?= $planesConFaltantes === [] ? 'text-success' : 'text-danger' ?>">
          <?= $planesConFaltantes === [] ? 'Materiales completos' : escapar(pluralizar(count($planesConFaltantes), 'plan con faltantes', 'planes con faltantes')) ?>
        </div>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-xl-3">
    <a class="tarjeta indicador" href="<?= escapar(url('modules/inventario/index.php?estado=ALERTA')) ?>">
      <span class="indicador-icono <?= $materiasEnAlerta === [] ? 'bien' : 'aviso' ?>"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
      <div>
        <div class="indicador-valor"><?= count($materiasEnAlerta) ?></div>
        <div class="indicador-etiqueta"><?= count($materiasEnAlerta) === 1 ? 'materia prima por reabastecer' : 'materias primas por reabastecer' ?></div>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-xl-3">
    <a class="tarjeta indicador" href="<?= escapar(url('modules/inventario/pedidos.php')) ?>">
      <span class="indicador-icono <?= $pedidosAtrasados === [] ? '' : 'alerta' ?>"><i class="bi bi-truck" aria-hidden="true"></i></span>
      <div>
        <div class="indicador-valor"><?= count($pedidosPendientes) ?></div>
        <div class="indicador-etiqueta"><?= count($pedidosPendientes) === 1 ? 'pedido por llegar' : 'pedidos por llegar' ?></div>
        <?php if ($pedidosAtrasados !== []) : ?>
          <div class="small text-danger"><?= escapar(pluralizar(count($pedidosAtrasados), 'atrasado', 'atrasados')) ?></div>
        <?php endif; ?>
      </div>
    </a>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <section class="tarjeta" aria-labelledby="tituloPendientes">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0" id="tituloPendientes">Para atender</h2>
        <?php if ($pendientes !== []) : ?>
          <span class="badge rounded-pill text-bg-secondary"><?= count($pendientes) ?></span>
        <?php endif; ?>
      </div>
      <?php if ($pendientes === []) : ?>
        <div class="estado-vacio">
          <i class="bi bi-emoji-smile" aria-hidden="true"></i>
          Todo en orden: no hay faltantes, pedidos atrasados ni materias primas bajo el mínimo.
        </div>
      <?php else : ?>
        <ul class="list-group list-group-flush lista-pendientes">
          <?php foreach ($pendientes as $pendiente) : ?>
            <li class="list-group-item">
              <i class="bi <?= $pendiente['icono'] ?> icono-pendiente <?= $coloresNivel[$pendiente['nivel']] ?>" aria-hidden="true"></i>
              <span class="flex-grow-1"><?= escapar($pendiente['texto']) ?></span>
              <a class="btn btn-sm btn-outline-primary text-nowrap" href="<?= escapar(url($pendiente['accion'][1])) ?>"><?= escapar($pendiente['accion'][0]) ?></a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>

  <div class="col-lg-5">
    <section class="tarjeta mb-4" aria-labelledby="tituloProduccion">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0" id="tituloProduccion">Producción de hoy y mañana</h2>
        <a class="small fw-normal" href="<?= escapar(url('modules/produccion/index.php')) ?>">Ver agenda</a>
      </div>
      <?php if ($producciones === []) : ?>
        <div class="estado-vacio">
          <i class="bi bi-calendar3" aria-hidden="true"></i>
          <p class="mb-2">No hay producción planificada para hoy ni mañana.</p>
          <a class="btn btn-sm btn-primary" href="<?= escapar(url('modules/produccion/plan.php')) ?>">Planificar</a>
        </div>
      <?php else : ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($producciones as $plan) : ?>
            <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
              <div>
                <a class="fw-semibold link-body-emphasis text-decoration-none" href="<?= escapar(url('modules/produccion/detalle.php?id=' . $plan['id'])) ?>">
                  <?= escapar($plan['receta']) ?>
                </a>
                <div class="small text-body-secondary">
                  <?= escapar(describirDia($plan['fecha_planeada'])) ?> ·
                  <?= escapar(pluralizar((int) ($plan['cantidad_producida'] ?? $plan['cantidad_planeada']), 'unidad', 'unidades')) ?>
                </div>
              </div>
              <div class="text-end">
                <?= $plan['analisis'] !== null
                    ? generarInsigniaAbastecimiento($plan['analisis']['estado'])
                    : generarInsigniaProduccion($plan['estado'], $plan['esta_atrasada']) ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section class="tarjeta" aria-labelledby="tituloPosibles">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0" id="tituloPosibles">Se puede hornear ahora</h2>
        <a class="small fw-normal" href="<?= escapar(url('modules/recetas/index.php')) ?>">Ver recetas</a>
      </div>
      <?php if ($recetasPosibles === []) : ?>
        <p class="estado-vacio mb-0">Con la existencia actual no alcanza para ninguna receta.</p>
      <?php else : ?>
        <ul class="list-group list-group-flush">
          <?php foreach (array_slice($recetasPosibles, 0, 5) as $receta) : ?>
            <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
              <a class="link-body-emphasis text-decoration-none" href="<?= escapar(url('modules/recetas/detalle.php?id=' . $receta['id'])) ?>">
                <?= escapar($receta['nombre']) ?>
              </a>
              <span class="small text-body-secondary text-end">
                hasta <strong class="text-body"><?= escapar(formatearCantidad($receta['capacidad']['unidades_maximas'])) ?></strong>
                · limita <?= escapar(mb_strtolower($receta['capacidad']['limitante']['nombre'])) ?>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
</div>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
