<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

$gestorProduccion = new GestorProduccion($conexion);
$hoy = obtenerFechaHoy();
$rangos = [
    'semana' => ['Próximos 7 días', $hoy, sumarDias($hoy, 6)],
    'quincena' => ['Próximos 14 días', $hoy, sumarDias($hoy, 13)],
    'historial' => ['Últimos 30 días', sumarDias($hoy, -30), $hoy],
];
$rango = array_key_exists(leerParametro('rango'), $rangos) ? leerParametro('rango') : 'semana';
[, $desde, $hasta] = $rangos[$rango];
$producciones = $gestorProduccion->listarProducciones($desde, $hasta, '', $rango !== 'historial');

if ($rango === 'historial') {
    $producciones = array_reverse($producciones);
}

$porFecha = [];

foreach ($producciones as $produccion) {
    $clave = $produccion['esta_atrasada'] ? 'atrasadas' : $produccion['fecha_planeada'];
    $porFecha[$clave][] = $produccion;
}

if (isset($porFecha['atrasadas'])) {
    $porFecha = ['atrasadas' => $porFecha['atrasadas']] + $porFecha;
}

$planesPendientes = array_filter($producciones, static fn (array $produccion): bool => $produccion['estado'] === 'PLANEADA');
$conFaltantes = array_filter(
    $planesPendientes,
    static fn (array $produccion): bool => ($produccion['analisis']['estado'] ?? '') === CalculadoraProduccion::FALTAN_MATERIALES
);

$tituloPagina = 'Producción';
$seccionActiva = 'produccion';
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<div class="encabezado-pagina">
  <div>
    <h1>Producción</h1>
  </div>
  <div class="acciones">
    <a class="btn btn-outline-primary" href="<?= escapar(url('modules/produccion/materiales.php?fecha=' . $hoy)) ?>">
      Materiales de hoy
    </a>
    <a class="btn btn-primary" href="<?= escapar(url('modules/produccion/plan.php')) ?>">
      Planificar producción
    </a>
  </div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div class="btn-group" role="group" aria-label="Rango de fechas">
    <?php foreach ($rangos as $clave => [$texto]) : ?>
      <a class="btn btn-sm <?= $rango === $clave ? 'btn-primary' : 'btn-outline-primary' ?>"
        href="<?= escapar(url('modules/produccion/index.php' . ($clave === 'semana' ? '' : '?rango=' . $clave))) ?>"><?= escapar($texto) ?></a>
    <?php endforeach; ?>
  </div>
  <?php if ($conFaltantes !== []) : ?>
    <span class="small text-danger fw-semibold">
      
      <?= escapar(pluralizar(count($conFaltantes), 'plan tiene', 'planes tienen')) ?> materiales faltantes
    </span>
  <?php elseif ($planesPendientes !== []) : ?>
    <span class="small text-success">Ningún plan tiene materiales faltantes</span>
  <?php endif; ?>
</div>

<?php if ($porFecha === []) : ?>
  <div class="tarjeta estado-vacio">
    
    <p class="mb-3"><?= $rango === 'historial' ? 'No hubo producción en los últimos 30 días.' : 'No hay producción planificada para estos días.' ?></p>
    <a class="btn btn-primary" href="<?= escapar(url('modules/produccion/plan.php')) ?>">Planificar producción</a>
  </div>
<?php endif; ?>

<?php foreach ($porFecha as $fecha => $planesDelDia) : ?>
  <h2 class="titulo-seccion" id="dia-<?= escapar($fecha) ?>">
    <?php if ($fecha === 'atrasadas') : ?>
      Planes atrasados <span class="fw-normal small">(eran para días anteriores y no se registraron)</span>
    <?php else : ?>
      <?= escapar(describirDia($fecha)) ?> <span class="fw-normal"><?= escapar(formatearFecha($fecha)) ?></span>
    <?php endif; ?>
    <?php if ($fecha !== 'atrasadas' && array_filter($planesDelDia, static fn (array $plan): bool => $plan['estado'] === 'PLANEADA') !== []) : ?>
      <a class="small fw-normal" href="<?= escapar(url('modules/produccion/materiales.php?fecha=' . $fecha)) ?>">(materiales del día)</a>
    <?php endif; ?>
  </h2>
  <table class="table table-bordered mb-4">
    <thead>
      <tr>
        <th scope="col">Producto</th>
        <th scope="col" class="numero">Unidades</th>
        <th scope="col">Estado</th>
        <th scope="col">Materiales</th>
        <th scope="col">Acción</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($planesDelDia as $plan) : ?>
        <tr>
          <td>
            <a href="<?= escapar(url('modules/produccion/detalle.php?id=' . $plan['id'])) ?>"><?= escapar($plan['receta']) ?></a>
            <?= $fecha === 'atrasadas' ? '<span class="small">(era para ' . escapar(mb_strtolower(describirDia($plan['fecha_planeada']))) . ')</span>' : '' ?>
            <?php if ($plan['observacion']) : ?>
              <div class="small text-body-secondary"><?= escapar($plan['observacion']) ?></div>
            <?php endif; ?>
            <?php if ($plan['analisis'] !== null && $plan['analisis']['estado'] === CalculadoraProduccion::FALTAN_MATERIALES) : ?>
              <div class="small texto-faltante">Faltan <?= escapar(describirFaltantes($plan['analisis'])) ?></div>
            <?php elseif ($plan['analisis'] !== null && $plan['analisis']['estado'] === CalculadoraProduccion::DEPENDE_DE_PEDIDOS) : ?>
              <div class="small texto-pedido">Si no llegan los pedidos faltarían <?= escapar(describirFaltantes($plan['analisis'], 'faltante_sin_pedidos')) ?></div>
            <?php endif; ?>
          </td>
          <td class="numero">
            <?php if ($plan['estado'] === 'REALIZADA') : ?>
              <?= escapar(formatearCantidad((int) $plan['cantidad_producida'])) ?>
              <?= $plan['cantidad_producida'] !== $plan['cantidad_planeada'] ? '<div class="small">de ' . escapar(formatearCantidad($plan['cantidad_planeada'])) . ' planeadas</div>' : '' ?>
            <?php else : ?>
              <?= escapar(formatearCantidad($plan['cantidad_planeada'])) ?>
            <?php endif; ?>
          </td>
          <td><?= generarInsigniaProduccion($plan['estado'], $plan['esta_atrasada']) ?></td>
          <td><?= $plan['analisis'] !== null ? generarInsigniaAbastecimiento($plan['analisis']['estado']) : '-' ?></td>
          <td class="text-nowrap">
            <?php if ($plan['estado'] === 'PLANEADA') : ?>
              <a class="btn btn-sm btn-primary" href="<?= escapar(url('modules/produccion/detalle.php?id=' . $plan['id'] . '#registrar')) ?>">Registrar</a>
            <?php else : ?>
              <a href="<?= escapar(url('modules/produccion/detalle.php?id=' . $plan['id'])) ?>">Ver</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endforeach; ?>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
