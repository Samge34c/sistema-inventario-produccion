<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

// Caja: registro rápido de ventas tocando los productos.

$gestorVentas = new GestorVentas($conexion);
$productos = (new GestorRecetas($conexion))->listarOpcionesRecetas();
$cantidades = [];
$datos = ['fecha_hora' => '', 'observacion' => ''];
$errores = [];

if (esPeticionPost()) {
    verificarTokenCsrf('modules/ventas/index.php');
    $datos = array_merge($datos, $_POST);
    $cantidades = is_array($_POST['cantidades'] ?? null) ? $_POST['cantidades'] : [];

    try {
        $ventaId = $gestorVentas->registrarVenta($_POST);
        $totalUnidades = array_sum(array_map('intval', $cantidades));
        agregarMensaje('exito', sprintf('Venta #%d registrada: %s.', $ventaId, pluralizar($totalUnidades, 'unidad', 'unidades')));
        redirigir('modules/ventas/index.php');
    } catch (ErrorValidacion $error) {
        $errores = $error->obtenerErrores();
    }
}

$hoy = obtenerFechaHoy();
$ventasDeHoy = $gestorVentas->listarVentasDelDia($hoy);
$resumenHoy = $gestorVentas->obtenerResumenDia($hoy);

$tituloPagina = 'Caja';
$seccionActiva = 'ventas';
$scriptsPagina = ['assets/js/ventas-caja.js'];
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<div class="encabezado-pagina">
  <div>
    <h1>Caja</h1>
    <p>Toque un producto para sumarlo a la venta. Hoy van <?= escapar(pluralizar($resumenHoy['transacciones'], 'venta', 'ventas')) ?>
      y <?= escapar(pluralizar($resumenHoy['unidades'], 'unidad vendida', 'unidades vendidas')) ?>.</p>
  </div>
  <div class="acciones">
    <a class="btn btn-outline-primary" href="<?= escapar(url('modules/ventas/reportes.php')) ?>">
      <i class="bi bi-bar-chart-line" aria-hidden="true"></i> Ver reportes
    </a>
  </div>
</div>

<?= generarPestanas(require __DIR__ . '/pestanas.php', 'caja') ?>

<?php if ($productos === []) : ?>
  <div class="tarjeta estado-vacio">
    <i class="bi bi-shop" aria-hidden="true"></i>
    <p class="mb-3">No hay productos para vender. Los productos salen de las recetas activas.</p>
    <a class="btn btn-primary" href="<?= escapar(url('modules/recetas/receta.php')) ?>">Crear receta</a>
  </div>
<?php else : ?>
  <form method="post" id="formularioCaja" novalidate>
    <?= generarCampoCsrf() ?>
    <div class="row g-4">
      <div class="col-lg-8">
        <?php if (isset($errores['cantidades'])) : ?>
          <div class="alert alert-danger"><?= escapar($errores['cantidades']) ?></div>
        <?php endif; ?>
        <div class="row g-3" id="productosCaja">
          <?php foreach ($productos as $producto) : ?>
            <?php $cantidad = (string) ($cantidades[$producto['id']] ?? ''); ?>
            <div class="col-6 col-xl-4">
              <div class="producto-caja<?= (int) $cantidad > 0 ? ' seleccionado' : '' ?>" data-producto
                data-nombre="<?= escapar($producto['nombre']) ?>">
                <span class="insignia-cantidad" data-insignia <?= (int) $cantidad > 0 ? '' : 'hidden' ?>><?= escapar((string) (int) $cantidad) ?></span>
                <button type="button" class="boton-agregar" data-agregar>
                  <?= escapar($producto['nombre']) ?>
                  <span class="d-none d-sm-block small fw-normal text-body-secondary">Toque para sumar 1</span>
                </button>
                <div class="input-group input-group-sm control-cantidad">
                  <button class="btn btn-outline-secondary" type="button" data-restar aria-label="Restar una unidad de <?= escapar($producto['nombre']) ?>">
                    <i class="bi bi-dash-lg" aria-hidden="true"></i>
                  </button>
                  <input type="number" class="form-control" name="cantidades[<?= $producto['id'] ?>]" min="0" max="10000" step="1"
                    inputmode="numeric" value="<?= escapar($cantidad) ?>" placeholder="0" data-cantidad
                    aria-label="Cantidad de <?= escapar($producto['nombre']) ?>">
                  <button class="btn btn-outline-secondary" type="button" data-sumar aria-label="Sumar una unidad de <?= escapar($producto['nombre']) ?>">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="tarjeta p-3 resumen-venta">
          <h2 class="h5 mb-3">Venta actual</h2>
          <ul class="list-unstyled mb-2" id="resumenProductos">
            <li class="text-body-secondary small">Todavía no hay productos.</li>
          </ul>
          <div class="d-flex justify-content-between border-top pt-2 mb-3">
            <span class="fw-semibold">Total</span>
            <span class="fw-bold" id="totalUnidades">0 unidades</span>
          </div>

          <details class="mb-3" <?= $datos['fecha_hora'] !== '' || isset($errores['fecha_hora']) ? 'open' : '' ?>>
            <summary class="small text-body-secondary">Registrar con otra fecha u hora</summary>
            <label class="form-label small mt-2" for="fecha_hora">Fecha y hora de la venta</label>
            <input type="datetime-local" class="form-control form-control-sm<?= generarClaseError($errores, 'fecha_hora') ?>" id="fecha_hora"
              name="fecha_hora" max="<?= escapar(date('Y-m-d\TH:i')) ?>" value="<?= escapar((string) $datos['fecha_hora']) ?>">
            <?= generarMensajeError($errores, 'fecha_hora') ?>
            <div class="form-text">Déjelo vacío para usar la hora actual.</div>
          </details>

          <label class="form-label small" for="observacion">Observación <span class="text-body-secondary fw-normal">(opcional)</span></label>
          <input type="text" class="form-control form-control-sm mb-3<?= generarClaseError($errores, 'observacion') ?>" id="observacion"
            name="observacion" maxlength="255" value="<?= escapar((string) $datos['observacion']) ?>" placeholder="Ej.: domicilio, pedido por encargo">
          <?= generarMensajeError($errores, 'observacion') ?>

          <button class="btn btn-primary btn-lg w-100" type="submit" id="botonRegistrarVenta">
            <i class="bi bi-check2-circle" aria-hidden="true"></i> Registrar venta
          </button>
          <button class="btn btn-link btn-sm w-100 mt-1 text-body-secondary" type="button" id="limpiarVenta">Empezar de nuevo</button>
        </div>
      </div>
    </div>

    <div class="barra-venta-movil d-lg-none">
      <span class="fw-semibold" data-total-movil>0 unidades</span>
      <button class="btn btn-primary" type="submit" data-registrar-movil>
        <i class="bi bi-check2-circle" aria-hidden="true"></i> Registrar venta
      </button>
    </div>
  </form>
<?php endif; ?>

<section class="tarjeta mt-4" aria-labelledby="tituloVentasHoy">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h2 class="h6 mb-0" id="tituloVentasHoy">Ventas de hoy</h2>
    <a class="small fw-normal" href="<?= escapar(url('modules/ventas/reportes.php?tipo=diario')) ?>">Ver reporte del día</a>
  </div>
  <?php if ($ventasDeHoy === []) : ?>
    <p class="estado-vacio mb-0">Aún no hay ventas registradas hoy.</p>
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
          <?php foreach (array_slice($ventasDeHoy, 0, 15) as $venta) : ?>
            <tr>
              <td class="text-nowrap">
                <?= escapar(formatearHora($venta['fecha_hora'])) ?>
                <div class="small text-body-secondary">#<?= $venta['id'] ?></div>
              </td>
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
                  <input type="hidden" name="regreso" value="caja">
                  <button class="btn btn-sm btn-link text-danger" type="submit">Anular</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (count($ventasDeHoy) > 15) : ?>
      <div class="px-3 py-2 border-top small">
        <a href="<?= escapar(url('modules/ventas/historial.php')) ?>">Ver las <?= count($ventasDeHoy) ?> ventas de hoy</a>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</section>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
