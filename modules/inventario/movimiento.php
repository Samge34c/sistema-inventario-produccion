<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

$inventario = new GestorInventario($conexion);
$materia = $inventario->obtenerMateriaPrima(leerParametroEntero('id'));

if ($materia === null) {
    agregarMensaje('error', 'Elija la materia prima a la que quiere registrar el movimiento.');
    redirigir('modules/inventario/index.php');
}

$datos = [
    'tipo' => leerParametro('tipo') === 'SALIDA' ? 'SALIDA' : 'ENTRADA',
    'cantidad' => '',
    'observacion' => '',
];
$errores = [];

if (esPeticionPost()) {
    verificarTokenCsrf('modules/inventario/index.php');
    $datos = array_merge($datos, $_POST);

    try {
        $inventario->registrarMovimientoDesdeFormulario($materia['id'], $_POST);
        $materiaActualizada = $inventario->obtenerMateriaPrima($materia['id']) ?? $materia;
        agregarMensaje('exito', sprintf(
            '%s de %s de %s registrada. Ahora hay %s.',
            $_POST['tipo'] === 'SALIDA' ? 'Salida' : 'Entrada',
            formatearCantidadConUnidad((float) convertirTextoANumero((string) $_POST['cantidad']), $materia['unidad_medida']),
            mb_strtolower($materia['nombre']),
            formatearCantidadConUnidad($materiaActualizada['cantidad_disponible'], $materia['unidad_medida'])
        ));
        redirigir('modules/inventario/index.php');
    } catch (ErrorValidacion $error) {
        $errores = $error->obtenerErrores();
    }
}

$ultimosMovimientos = $inventario->listarMovimientos(['materia_prima_id' => $materia['id']])['movimientos'];
$ultimosMovimientos = array_slice($ultimosMovimientos, 0, 6);
$unidad = obtenerNombreUnidad($materia['unidad_medida']);

$tituloPagina = 'Movimiento de ' . $materia['nombre'];
$seccionActiva = 'inventario';
$scriptsPagina = ['assets/js/inventario-movimiento.js'];
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<nav aria-label="Ruta de navegación">
  <ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="<?= escapar(url('modules/inventario/index.php')) ?>">Inventario</a></li>
    <li class="breadcrumb-item active" aria-current="page">Entrada o salida</li>
  </ol>
</nav>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="encabezado-pagina">
      <div>
        <h1><?= escapar($materia['nombre']) ?></h1>
        <p>
          Hay <strong><?= escapar(formatearCantidadConUnidad($materia['cantidad_disponible'], $materia['unidad_medida'])) ?></strong>
          · mínimo <?= escapar(formatearCantidadConUnidad($materia['stock_minimo'], $materia['unidad_medida'])) ?>
          · <?= generarInsigniaExistencia($materia['estado']) ?>
        </p>
      </div>
    </div>

    <?php if (isset($errores['general'])) : ?>
      <div class="alert alert-danger"><?= escapar($errores['general']) ?></div>
    <?php endif; ?>

    <form method="post" class="tarjeta p-4" novalidate id="formularioMovimiento"
      data-existencia="<?= escapar(formatearValorCampo($materia['cantidad_disponible'])) ?>"
      data-minimo="<?= escapar(formatearValorCampo($materia['stock_minimo'])) ?>"
      data-unidad="<?= escapar($unidad) ?>">
      <?= generarCampoCsrf() ?>

      <fieldset class="mb-3">
        <legend class="form-label fs-6">¿Qué pasó?</legend>
        <div class="btn-group w-100" role="group">
          <input type="radio" class="btn-check" name="tipo" id="tipoEntrada" value="ENTRADA" <?= $datos['tipo'] === 'ENTRADA' ? 'checked' : '' ?>>
          <label class="btn btn-outline-success" for="tipoEntrada"><i class="bi bi-box-arrow-in-down" aria-hidden="true"></i> Entró (compra, ajuste)</label>
          <input type="radio" class="btn-check" name="tipo" id="tipoSalida" value="SALIDA" <?= $datos['tipo'] === 'SALIDA' ? 'checked' : '' ?>>
          <label class="btn btn-outline-danger" for="tipoSalida"><i class="bi bi-box-arrow-up" aria-hidden="true"></i> Salió (merma, daño, uso)</label>
        </div>
        <?= isset($errores['tipo']) ? '<div class="text-danger small mt-1">' . escapar($errores['tipo']) . '</div>' : '' ?>
      </fieldset>

      <div class="mb-3">
        <label class="form-label" for="cantidad">Cantidad</label>
        <div class="input-group has-validation">
          <input type="number" class="form-control form-control-lg<?= generarClaseError($errores, 'cantidad') ?>" id="cantidad"
            name="cantidad" min="0.01" step="0.01" inputmode="decimal" required autofocus
            value="<?= escapar(formatearValorCampo($datos['cantidad'])) ?>" aria-describedby="vistaPrevia">
          <span class="input-group-text"><?= escapar($unidad) ?></span>
          <?= generarMensajeError($errores, 'cantidad') ?>
        </div>
        <div class="form-text" id="vistaPrevia" aria-live="polite"></div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="observacion">Motivo u observación <span class="text-body-secondary fw-normal">(opcional)</span></label>
        <input type="text" class="form-control<?= generarClaseError($errores, 'observacion') ?>" id="observacion" name="observacion"
          maxlength="255" list="motivosFrecuentes" value="<?= escapar($datos['observacion']) ?>"
          placeholder="Ej.: compra en el supermercado, conteo físico, producto vencido">
        <?= generarMensajeError($errores, 'observacion') ?>
        <datalist id="motivosFrecuentes">
          <option value="Compra">
          <option value="Ajuste por conteo físico">
          <option value="Merma">
          <option value="Producto vencido">
          <option value="Producto dañado">
          <option value="Devolución al proveedor">
        </datalist>
      </div>

      <p class="texto-ayuda mb-0">
        Para lo que se gasta al hornear no hace falta registrar salidas: el sistema las descuenta solo al
        <a href="<?= escapar(url('modules/produccion/index.php')) ?>">registrar la producción</a>.
      </p>

      <div class="d-flex gap-2 justify-content-end mt-4">
        <a class="btn btn-outline-secondary" href="<?= escapar(url('modules/inventario/index.php')) ?>">Cancelar</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg" aria-hidden="true"></i> Registrar movimiento</button>
      </div>
    </form>
  </div>

  <div class="col-lg-5">
    <div class="tarjeta">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Últimos movimientos</span>
        <a class="small fw-normal" href="<?= escapar(url('modules/inventario/movimientos.php?materia_prima_id=' . $materia['id'])) ?>">Ver todos</a>
      </div>
      <?php if ($ultimosMovimientos === []) : ?>
        <p class="estado-vacio mb-0">Sin movimientos todavía.</p>
      <?php else : ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($ultimosMovimientos as $movimiento) : ?>
            <li class="list-group-item d-flex justify-content-between gap-3">
              <div>
                <div class="small text-body-secondary"><?= escapar(formatearFechaHora($movimiento['fecha'])) ?></div>
                <div><?= escapar($movimiento['observacion'] ?? ($movimiento['tipo'] === 'ENTRADA' ? 'Entrada' : 'Salida')) ?></div>
              </div>
              <div class="numero fw-semibold <?= $movimiento['tipo'] === 'ENTRADA' ? 'text-success' : 'text-danger' ?>">
                <?= $movimiento['tipo'] === 'ENTRADA' ? '+' : '−' ?><?= escapar(formatearCantidad($movimiento['cantidad'])) ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
