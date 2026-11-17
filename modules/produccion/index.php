<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

// Agenda de producción agrupada por fecha, con el estado de materiales de cada plan.

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
    <p>Qué se va a hornear cada día, si alcanzan los materiales y qué ya se produjo.</p>
  </div>
  <div class="acciones">
    <a class="btn btn-outline-primary" href="<?= escapar(url('modules/produccion/materiales.php?fecha=' . $hoy)) ?>">
      <i class="bi bi-list-check" aria-hidden="true"></i> Materiales de hoy
    </a>
    <a class="btn btn-primary" href="<?= escapar(url('modules/produccion/plan.php')) ?>">
      <i class="bi bi-calendar-plus" aria-hidden="true"></i> Planificar producción
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
      <i class="bi bi-cart-x" aria-hidden="true"></i>
      <?= escapar(pluralizar(count($conFaltantes), 'plan tiene', 'planes tienen')) ?> materiales faltantes
    </span>
  <?php elseif ($planesPendientes !== []) : ?>
    <span class="small text-success"><i class="bi bi-check2-circle" aria-hidden="true"></i> Ningún plan tiene materiales faltantes</span>
  <?php endif; ?>
</div>

<?php if ($porFecha === []) : ?>
  <div class="tarjeta estado-vacio">
    <i class="bi bi-calendar3" aria-hidden="true"></i>
    <p class="mb-3"><?= $rango === 'historial' ? 'No hubo producción en los últimos 30 días.' : 'No hay producción planificada para estos días.' ?></p>
    <a class="btn btn-primary" href="<?= escapar(url('modules/produccion/plan.php')) ?>">Planificar producción</a>
  </div>
<?php endif; ?>

<?php foreach ($porFecha as $fecha => $planesDelDia) : ?>
  <section class="tarjeta mb-3" aria-labelledby="dia-<?= escapar($fecha) ?>">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
      <h2 class="h6 mb-0" id="dia-<?= escapar($fecha) ?>">
        <?php if ($fecha === 'atrasadas') : ?>
          <i class="bi bi-clock-history text-danger" aria-hidden="true"></i> Planes atrasados
          <span class="fw-normal text-body-secondary small">— eran para días anteriores y no se registraron</span>
        <?php else : ?>
          <?= escapar(describirDia($fecha)) ?>
          <span class="fw-normal text-body-secondary"><?= escapar(formatearFecha($fecha)) ?></span>
        <?php endif; ?>
      </h2>
      <?php if ($fecha !== 'atrasadas' && array_filter($planesDelDia, static fn (array $plan): bool => $plan['estado'] === 'PLANEADA') !== []) : ?>
        <a class="small fw-normal" href="<?= escapar(url('modules/produccion/materiales.php?fecha=' . $fecha)) ?>">
          <i class="bi bi-list-check" aria-hidden="true"></i> Materiales del día
        </a>
      <?php endif; ?>
    </div>
    <ul class="list-group list-group-flush">
      <?php foreach ($planesDelDia as $plan) : ?>
        <li class="list-group-item py-3">
          <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="flex-grow-1" style="min-width: 220px">
              <a class="fw-semibold link-body-emphasis text-decoration-none" href="<?= escapar(url('modules/produccion/detalle.php?id=' . $plan['id'])) ?>">
                <?= escapar($plan['receta']) ?>
              </a>
              <div class="small text-body-secondary">
                <?php if ($plan['estado'] === 'REALIZADA') : ?>
                  <?= escapar(pluralizar((int) $plan['cantidad_producida'], 'unidad producida', 'unidades producidas')) ?>
                  <?= $plan['cantidad_producida'] !== $plan['cantidad_planeada'] ? '(de ' . escapar(formatearCantidad($plan['cantidad_planeada'])) . ' planeadas)' : '' ?>
                <?php else : ?>
                  <?= escapar(pluralizar($plan['cantidad_planeada'], 'unidad', 'unidades')) ?>
                  <?= $fecha === 'atrasadas' ? '· era para ' . escapar(mb_strtolower(describirDia($plan['fecha_planeada']))) : '' ?>
                <?php endif; ?>
                <?= $plan['observacion'] ? '· ' . escapar($plan['observacion']) : '' ?>
              </div>
              <?php if ($plan['analisis'] !== null && $plan['analisis']['estado'] === CalculadoraProduccion::FALTAN_MATERIALES) : ?>
                <div class="small texto-faltante mt-1">Faltan <?= escapar(describirFaltantes($plan['analisis'])) ?></div>
              <?php elseif ($plan['analisis'] !== null && $plan['analisis']['estado'] === CalculadoraProduccion::DEPENDE_DE_PEDIDOS) : ?>
                <div class="small texto-pedido mt-1">Si no llegan los pedidos faltarían <?= escapar(describirFaltantes($plan['analisis'], 'faltante_sin_pedidos')) ?></div>
              <?php endif; ?>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
              <?= generarInsigniaProduccion($plan['estado'], $plan['esta_atrasada']) ?>
              <?= $plan['analisis'] !== null ? generarInsigniaAbastecimiento($plan['analisis']['estado']) : '' ?>
            </div>
            <div class="ms-auto">
              <?php if ($plan['estado'] === 'PLANEADA') : ?>
                <a class="btn btn-sm btn-success" href="<?= escapar(url('modules/produccion/detalle.php?id=' . $plan['id'] . '#registrar')) ?>">
                  <i class="bi bi-check2-square" aria-hidden="true"></i> Registrar
                </a>
              <?php else : ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= escapar(url('modules/produccion/detalle.php?id=' . $plan['id'])) ?>">Ver</a>
              <?php endif; ?>
            </div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endforeach; ?>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
