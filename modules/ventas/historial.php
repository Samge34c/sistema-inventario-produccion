<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

$gestorVentas = new GestorVentas($conexion);
$hoy = obtenerFechaHoy();
$fecha = min(leerParametroFecha('fecha', $hoy), $hoy);
$ventas = $gestorVentas->listarVentasDelDia($fecha);
$resumen = $gestorVentas->obtenerResumenDia($fecha);

$tituloPagina = 'Ventas de ' . describirDia($fecha);
$seccionActiva = 'ventas';
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<div class="encabezado-pagina">
  <div>
    <h1>Caja</h1>
    <p>Consulte las ventas de cualquier día y anule las que se registraron por error.</p>
  </div>
</div>

<?= generarPestanas(require __DIR__ . '/pestanas.php', 'historial') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div class="d-flex align-items-center gap-2">
    <a class="btn btn-sm btn-outline-primary" href="<?= escapar(url('modules/ventas/historial.php?fecha=' . sumarDias($fecha, -1))) ?>"
      aria-label="Día anterior"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
    <form method="get">
      <label class="visually-hidden" for="fecha">Fecha</label>
      <input type="date" class="form-control form-control-sm" id="fecha" name="fecha" value="<?= escapar($fecha) ?>"
        max="<?= escapar($hoy) ?>" data-enviar-al-cambiar>
    </form>
    <a class="btn btn-sm btn-outline-primary <?= $fecha >= $hoy ? 'disabled' : '' ?>"
      href="<?= escapar(url('modules/ventas/historial.php?fecha=' . sumarDias($fecha, 1))) ?>"
      aria-label="Día siguiente"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
    <span class="fw-semibold ms-1"><?= escapar(describirDia($fecha)) ?></span>
  </div>
  <div class="small text-body-secondary">
    <?= escapar(pluralizar($resumen['transacciones'], 'venta', 'ventas')) ?> ·
    <?= escapar(pluralizar($resumen['unidades'], 'unidad', 'unidades')) ?> ·
    <a href="<?= escapar(url('modules/ventas/reportes.php?tipo=diario&fecha=' . $fecha)) ?>">ver reporte</a>
  </div>
</div>

<div class="tarjeta">
  <?php if ($ventas === []) : ?>
    <p class="estado-vacio mb-0"><i class="bi bi-receipt" aria-hidden="true"></i> No hay ventas registradas este día.</p>
  <?php else : ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th scope="col">Hora</th>
            <th scope="col">Productos</th>
            <th scope="col" class="text-end">Unidades</th>
            <th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ventas as $venta) : ?>
            <tr>
              <td class="text-nowrap"><?= escapar(formatearHora($venta['fecha_hora'])) ?> <span class="small text-body-secondary">#<?= $venta['id'] ?></span></td>
              <td>
                <?= escapar(implode(', ', array_map(
                    static fn (array $producto): string => $producto['cantidad'] . ' × ' . $producto['nombre'],
                    $venta['productos']
                ))) ?>
                <?= $venta['observacion'] ? '<div class="small text-body-secondary">' . escapar($venta['observacion']) . '</div>' : '' ?>
              </td>
              <td class="numero fw-semibold"><?= $venta['unidades'] ?></td>
              <td class="text-end">
                <form method="post" action="<?= escapar(url('modules/ventas/anular.php')) ?>"
                  data-confirmar="¿Anular la venta #<?= $venta['id'] ?>? Se borra de los reportes." data-confirmar-boton="Anular venta">
                  <?= generarCampoCsrf() ?>
                  <input type="hidden" name="id" value="<?= $venta['id'] ?>">
                  <input type="hidden" name="regreso" value="<?= escapar($fecha) ?>">
                  <button class="btn btn-sm btn-link text-danger" type="submit">Anular</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
