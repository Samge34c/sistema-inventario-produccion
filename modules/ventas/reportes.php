<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

// Reportes de ventas: diario, semanal y hora pico.

$gestorVentas = new GestorVentas($conexion);
$hoy = obtenerFechaHoy();
$tipo = in_array(leerParametro('tipo'), ['diario', 'semanal', 'hora-pico'], true) ? leerParametro('tipo') : 'diario';
$fecha = min(leerParametroFecha('fecha', $hoy), $hoy);

/**
 * @param array<int, array<string, int|float>> $porHora
 * @return list<array{etiqueta: string, valor: float, texto: string, titulo: string, esDestacada: bool}>
 */
function construirColumnasPorHora(array $porHora, string $campo, ?int $horaPico, int $decimales = 0): array
{
    if ($porHora === []) {
        return [];
    }

    $columnas = [];

    for ($hora = min(array_keys($porHora)); $hora <= max(array_keys($porHora)); $hora++) {
        $valor = (float) ($porHora[$hora][$campo] ?? 0);
        $unidades = (int) ($porHora[$hora]['unidades'] ?? 0);
        $ventas = (int) ($porHora[$hora]['transacciones'] ?? 0);
        $columnas[] = [
            'etiqueta' => formatearFranjaHoraria($hora),
            'valor' => $valor,
            'texto' => $valor > 0 ? formatearCantidad($valor, $decimales) : '',
            'titulo' => sprintf('%s: %s, %s', formatearFranjaHoraria($hora), pluralizar($ventas, 'venta', 'ventas'), pluralizar($unidades, 'unidad', 'unidades')),
            'esDestacada' => $hora === $horaPico,
        ];
    }

    return $columnas;
}

if ($tipo === 'diario') {
    $reporte = $gestorVentas->generarReporteDiario($fecha);
} elseif ($tipo === 'semanal') {
    $reporte = $gestorVentas->generarReporteSemanal($fecha);
} else {
    $diasHoraPico = in_array(leerParametroEntero('dias', 14), [7, 14, 30, 90], true) ? leerParametroEntero('dias', 14) : 14;
    $reporte = $gestorVentas->generarReporteHoraPico(sumarDias($hoy, -($diasHoraPico - 1)), $hoy);
}

$pestanasReporte = [
    'diario' => ['Diario', 'bi-calendar3', 'modules/ventas/reportes.php?tipo=diario&fecha=' . $fecha],
    'semanal' => ['Semanal', 'bi-calendar-week', 'modules/ventas/reportes.php?tipo=semanal&fecha=' . $fecha],
    'hora-pico' => ['Hora pico', 'bi-clock', 'modules/ventas/reportes.php?tipo=hora-pico'],
];

$tituloPagina = 'Reportes de ventas';
$seccionActiva = 'reportes';
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<div class="encabezado-pagina">
  <div>
    <h1>Reportes de ventas</h1>
    <p>«Ventas» cuenta clientes atendidos en caja; «unidades» cuenta productos vendidos.</p>
  </div>
  <div class="acciones">
    <button type="button" class="btn btn-outline-primary" data-imprimir><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</button>
  </div>
</div>

<?= generarPestanas($pestanasReporte, $tipo) ?>

<?php if ($tipo === 'diario') : ?>
  <?php
  $variacionVentas = calcularVariacion($reporte['resumen']['transacciones'], $reporte['resumen_comparacion']['transacciones']);
    $variacionUnidades = calcularVariacion($reporte['resumen']['unidades'], $reporte['resumen_comparacion']['unidades']);
    $periodoComparacion = 'el ' . DIAS_SEMANA_LARGOS[(int) date('w', (int) strtotime($fecha))] . ' anterior';
    ?>
  <div class="d-flex align-items-center gap-2 mb-3 no-imprimir">
    <a class="btn btn-sm btn-outline-primary" href="<?= escapar(url('modules/ventas/reportes.php?tipo=diario&fecha=' . sumarDias($fecha, -1))) ?>" aria-label="Día anterior">
      <i class="bi bi-chevron-left" aria-hidden="true"></i>
    </a>
    <form method="get">
      <input type="hidden" name="tipo" value="diario">
      <label class="visually-hidden" for="fecha">Fecha</label>
      <input type="date" class="form-control form-control-sm" id="fecha" name="fecha" value="<?= escapar($fecha) ?>" max="<?= escapar($hoy) ?>" data-enviar-al-cambiar>
    </form>
    <a class="btn btn-sm btn-outline-primary <?= $fecha >= $hoy ? 'disabled' : '' ?>"
      href="<?= escapar(url('modules/ventas/reportes.php?tipo=diario&fecha=' . sumarDias($fecha, 1))) ?>" aria-label="Día siguiente">
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
    </a>
    <span class="fw-semibold ms-1"><?= escapar(ucfirst(formatearFechaLarga($fecha))) ?></span>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="tarjeta indicador">
        <span class="indicador-icono"><i class="bi bi-people" aria-hidden="true"></i></span>
        <div>
          <div class="indicador-valor"><?= escapar(formatearCantidad($reporte['resumen']['transacciones'])) ?></div>
          <div class="indicador-etiqueta">ventas</div>
          <?= generarTextoVariacion($variacionVentas, $periodoComparacion) ?>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="tarjeta indicador">
        <span class="indicador-icono"><i class="bi bi-basket" aria-hidden="true"></i></span>
        <div>
          <div class="indicador-valor"><?= escapar(formatearCantidad($reporte['resumen']['unidades'])) ?></div>
          <div class="indicador-etiqueta">unidades vendidas</div>
          <?= generarTextoVariacion($variacionUnidades, $periodoComparacion) ?>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="tarjeta indicador">
        <span class="indicador-icono aviso"><i class="bi bi-clock" aria-hidden="true"></i></span>
        <div>
          <?php if ($reporte['hora_pico'] !== null) : ?>
            <div class="indicador-valor"><?= escapar(formatearFranjaHoraria($reporte['hora_pico']['hora'])) ?></div>
            <div class="indicador-etiqueta">
              hora pico: <?= escapar(pluralizar($reporte['hora_pico']['transacciones'], 'venta', 'ventas')) ?>,
              <?= escapar(pluralizar($reporte['hora_pico']['unidades'], 'unidad', 'unidades')) ?>
            </div>
          <?php else : ?>
            <div class="indicador-valor">—</div>
            <div class="indicador-etiqueta">sin ventas este día</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <?php if ($reporte['resumen']['transacciones'] === 0) : ?>
    <div class="tarjeta estado-vacio"><i class="bi bi-bar-chart" aria-hidden="true"></i> No hay ventas registradas este día.</div>
  <?php else : ?>
    <div class="row g-4">
      <div class="col-xl-7">
        <div class="tarjeta p-3 h-100">
          <h2 class="h6">Ventas por hora</h2>
          <?= generarGraficaColumnas(
              construirColumnasPorHora($reporte['por_hora'], 'transacciones', $reporte['hora_pico']['hora'] ?? null),
              'Número de ventas por hora del día'
          ) ?>
        </div>
      </div>
      <div class="col-xl-5">
        <?php require __DIR__ . '/tabla-productos.php'; ?>
      </div>
    </div>
  <?php endif; ?>

<?php elseif ($tipo === 'semanal') : ?>
  <?php
  $variacionVentas = calcularVariacion($reporte['resumen']['transacciones'], $reporte['resumen_anterior']['transacciones']);
    $variacionUnidades = calcularVariacion($reporte['resumen']['unidades'], $reporte['resumen_anterior']['unidades']);
    $columnasDias = [];

    foreach ($reporte['por_dia'] as $dia => $totales) {
        $columnasDias[] = [
            'etiqueta' => formatearFecha($dia),
            'valor' => (float) $totales['unidades'],
            'texto' => $totales['unidades'] > 0 ? formatearCantidad($totales['unidades']) : '',
            'titulo' => sprintf('%s: %s, %s', formatearFecha($dia), pluralizar($totales['transacciones'], 'venta', 'ventas'), pluralizar($totales['unidades'], 'unidad', 'unidades')),
            'esDestacada' => $dia === $reporte['mejor_dia'],
        ];
    }
    ?>
  <div class="d-flex align-items-center gap-2 mb-3 no-imprimir">
    <a class="btn btn-sm btn-outline-primary" href="<?= escapar(url('modules/ventas/reportes.php?tipo=semanal&fecha=' . sumarDias($reporte['inicio'], -7))) ?>" aria-label="Semana anterior">
      <i class="bi bi-chevron-left" aria-hidden="true"></i>
    </a>
    <a class="btn btn-sm btn-outline-primary <?= $reporte['fin'] >= $hoy ? 'disabled' : '' ?>"
      href="<?= escapar(url('modules/ventas/reportes.php?tipo=semanal&fecha=' . sumarDias($reporte['inicio'], 7))) ?>" aria-label="Semana siguiente">
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
    </a>
    <span class="fw-semibold ms-1">
      Semana del <?= escapar(formatearFecha($reporte['inicio'])) ?> al <?= escapar(formatearFecha($reporte['fin'], true)) ?>
    </span>
    <?php if ($reporte['fin'] >= $hoy) : ?>
      <span class="badge bg-primary-subtle text-primary-emphasis">En curso</span>
    <?php endif; ?>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="tarjeta indicador">
        <span class="indicador-icono"><i class="bi bi-people" aria-hidden="true"></i></span>
        <div>
          <div class="indicador-valor"><?= escapar(formatearCantidad($reporte['resumen']['transacciones'])) ?></div>
          <div class="indicador-etiqueta">ventas en la semana</div>
          <?= generarTextoVariacion($variacionVentas, 'la semana anterior') ?>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="tarjeta indicador">
        <span class="indicador-icono"><i class="bi bi-basket" aria-hidden="true"></i></span>
        <div>
          <div class="indicador-valor"><?= escapar(formatearCantidad($reporte['resumen']['unidades'])) ?></div>
          <div class="indicador-etiqueta">unidades vendidas</div>
          <?= generarTextoVariacion($variacionUnidades, 'la semana anterior') ?>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="tarjeta indicador">
        <span class="indicador-icono bien"><i class="bi bi-trophy" aria-hidden="true"></i></span>
        <div>
          <div class="indicador-valor"><?= $reporte['mejor_dia'] ? escapar(ucfirst(formatearFecha($reporte['mejor_dia']))) : '—' ?></div>
          <div class="indicador-etiqueta">
            <?= $reporte['mejor_dia']
                  ? 'mejor día: ' . escapar(pluralizar($reporte['por_dia'][$reporte['mejor_dia']]['unidades'], 'unidad', 'unidades'))
                  : 'sin ventas esta semana' ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if ($reporte['resumen']['transacciones'] === 0) : ?>
    <div class="tarjeta estado-vacio"><i class="bi bi-bar-chart" aria-hidden="true"></i> No hay ventas registradas esta semana.</div>
  <?php else : ?>
    <div class="row g-4">
      <div class="col-xl-7">
        <div class="tarjeta p-3 h-100">
          <h2 class="h6">Unidades vendidas por día</h2>
          <?= generarGraficaColumnas($columnasDias, 'Unidades vendidas cada día de la semana') ?>
        </div>
      </div>
      <div class="col-xl-5">
        <?php require __DIR__ . '/tabla-productos.php'; ?>
      </div>
    </div>
  <?php endif; ?>

<?php else : ?>
  <?php
    $horaPico = $reporte['hora_pico'];
    $promedioPico = $horaPico === null ? 0 : $reporte['por_hora'][$horaPico['hora']]['promedio_transacciones'];
    $maximoMapa = max(array_merge([0], ...array_map('array_values', array_values($reporte['mapa']))));
    $horasMapa = $reporte['por_hora'] === [] ? [] : range(min(array_keys($reporte['por_hora'])), max(array_keys($reporte['por_hora'])));
    ?>
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-imprimir">
    <span class="small text-body-secondary">Periodo:</span>
    <div class="btn-group" role="group" aria-label="Periodo del reporte">
      <?php foreach ([7, 14, 30, 90] as $dias) : ?>
        <a class="btn btn-sm <?= $reporte['dias'] === $dias ? 'btn-primary' : 'btn-outline-primary' ?>"
          href="<?= escapar(url('modules/ventas/reportes.php?tipo=hora-pico&dias=' . $dias)) ?>">Últimos <?= $dias ?> días</a>
      <?php endforeach; ?>
    </div>
    <span class="small text-body-secondary">
      <?= escapar(formatearFecha($reporte['desde'])) ?> – <?= escapar(formatearFecha($reporte['hasta'])) ?>
    </span>
  </div>

  <?php if ($horaPico === null) : ?>
    <div class="tarjeta estado-vacio"><i class="bi bi-clock" aria-hidden="true"></i> No hay ventas en este periodo.</div>
  <?php else : ?>
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="tarjeta indicador">
          <span class="indicador-icono aviso"><i class="bi bi-clock-fill" aria-hidden="true"></i></span>
          <div>
            <div class="indicador-valor">
              <?= escapar(formatearFranjaHoraria($horaPico['hora'])) ?> – <?= escapar(formatearFranjaHoraria(($horaPico['hora'] + 1) % 24)) ?>
            </div>
            <div class="indicador-etiqueta">
              es la hora pico: <?= escapar(pluralizar($horaPico['transacciones'], 'venta', 'ventas')) ?> en el periodo,
              unas <?= escapar(formatearCantidad($promedioPico, 1)) ?> por día
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="tarjeta indicador">
          <span class="indicador-icono"><i class="bi bi-people" aria-hidden="true"></i></span>
          <div>
            <div class="indicador-valor"><?= escapar(formatearCantidad($reporte['resumen']['transacciones'] / $reporte['dias'], 1)) ?></div>
            <div class="indicador-etiqueta">
              ventas por día en promedio (<?= escapar(formatearCantidad($reporte['resumen']['unidades'] / $reporte['dias'], 1)) ?> unidades)
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="tarjeta p-3 mb-4">
      <h2 class="h6">Promedio de ventas por día en cada franja horaria</h2>
      <?= generarGraficaColumnas(
          construirColumnasPorHora($reporte['por_hora'], 'promedio_transacciones', $horaPico['hora'], 1),
          'Promedio diario de ventas por franja horaria'
      ) ?>
      <p class="texto-ayuda mt-3 mb-0">
        La hora pico es la franja con más ventas en el periodo; si hay empate, gana la de más unidades.
        Úsela para tener el producto listo y más personal en caja.
      </p>
    </div>

    <div class="tarjeta p-3">
      <h2 class="h6">Ventas por día de la semana y hora</h2>
      <p class="small text-body-secondary">Total de ventas del periodo. Más oscuro, más movimiento.</p>
      <div class="table-responsive">
        <table class="mapa-calor">
          <thead>
            <tr>
              <th scope="col"><span class="visually-hidden">Día</span></th>
              <?php foreach ($horasMapa as $hora) : ?>
                <th scope="col"><?= escapar(formatearFranjaHoraria($hora)) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ([1, 2, 3, 4, 5, 6, 0] as $indiceDia) : ?>
              <?php $diaIso = $indiceDia === 0 ? 7 : $indiceDia; ?>
              <tr>
                <th scope="row" class="text-start"><?= escapar(ucfirst(DIAS_SEMANA_CORTOS[$indiceDia])) ?></th>
                <?php foreach ($horasMapa as $hora) : ?>
                  <?php
                  $valor = $reporte['mapa'][$diaIso][$hora] ?? 0;
                    $intensidad = $maximoMapa > 0 ? $valor / $maximoMapa : 0;
                    ?>
                  <td style="background-color: rgba(156, 90, 26, <?= round(0.06 + $intensidad * 0.88, 2) ?>); color: <?= $intensidad > 0.55 ? '#fff' : 'inherit' ?>"
                    title="<?= escapar(ucfirst(DIAS_SEMANA_LARGOS[$indiceDia]) . ', ' . formatearFranjaHoraria($hora) . ': ' . pluralizar($valor, 'venta', 'ventas')) ?>">
                    <?= $valor > 0 ? $valor : '' ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
