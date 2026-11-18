<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

// Registrar un pedido de materia prima que llegará después.

$inventario = new GestorInventario($conexion);
$materias = $inventario->listarMateriasPrimas();

if ($materias === []) {
    agregarMensaje('info', 'Primero registre la materia prima que va a pedir.');
    redirigir('modules/inventario/materia-prima.php');
}

$datos = [
    'materia_prima_id' => (string) leerParametroEntero('materia_prima_id'),
    'cantidad' => '',
    'proveedor' => '',
    'fecha_estimada_llegada' => sumarDias(obtenerFechaHoy(), 1),
    'observacion' => '',
];
$errores = [];

if (esPeticionPost()) {
    verificarTokenCsrf('modules/inventario/pedidos.php');
    $datos = array_merge($datos, $_POST);

    try {
        (new GestorPedidos($conexion))->crearPedido($_POST);
        agregarMensaje('exito', 'Pedido registrado. Ya se tiene en cuenta en la proyección de recetas y en la planificación.');
        redirigir('modules/inventario/pedidos.php');
    } catch (ErrorValidacion $error) {
        $errores = $error->obtenerErrores();
    }
}

$proveedores = $conexion
    ->query('SELECT DISTINCT proveedor FROM materiales_pendientes WHERE proveedor IS NOT NULL ORDER BY proveedor')
    ->fetchAll(PDO::FETCH_COLUMN);

$tituloPagina = 'Registrar pedido';
$seccionActiva = 'inventario';
$scriptsPagina = ['assets/js/inventario-pedido.js'];
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<nav aria-label="Ruta de navegación">
  <ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="<?= escapar(url('modules/inventario/index.php')) ?>">Inventario</a></li>
    <li class="breadcrumb-item"><a href="<?= escapar(url('modules/inventario/pedidos.php')) ?>">Pedidos por llegar</a></li>
    <li class="breadcrumb-item active" aria-current="page">Registrar pedido</li>
  </ol>
</nav>

<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="encabezado-pagina">
      <div>
        <h1>Registrar pedido</h1>
      </div>
    </div>

    <form method="post" class="tarjeta p-4" novalidate>
      <?= generarCampoCsrf() ?>

      <div class="mb-3">
        <label class="form-label" for="materia_prima_id">Materia prima</label>
        <select class="form-select<?= generarClaseError($errores, 'materia_prima_id') ?>" id="materia_prima_id" name="materia_prima_id" required>
          <option value="">Elija una materia prima</option>
          <?php foreach ($materias as $materia) : ?>
            <?php $sugerencia = max(0, $materia['stock_minimo'] * 2 - $materia['cantidad_disponible'] - $materia['cantidad_por_llegar']); ?>
            <option value="<?= $materia['id'] ?>" <?= (string) $materia['id'] === (string) $datos['materia_prima_id'] ? 'selected' : '' ?>
              data-unidad="<?= escapar(obtenerNombreUnidad($materia['unidad_medida'])) ?>"
              data-existencia="<?= escapar(formatearCantidadConUnidad($materia['cantidad_disponible'], $materia['unidad_medida'])) ?>"
              data-minimo="<?= escapar(formatearCantidadConUnidad($materia['stock_minimo'], $materia['unidad_medida'])) ?>"
              data-por-llegar="<?= escapar(formatearCantidadConUnidad($materia['cantidad_por_llegar'], $materia['unidad_medida'])) ?>"
              data-sugerencia="<?= escapar(formatearValorCampo(ceil($sugerencia))) ?>">
              <?= escapar($materia['nombre']) ?><?= $materia['estado'] !== GestorInventario::DISPONIBLE ? ' - por reabastecer' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?= generarMensajeError($errores, 'materia_prima_id') ?>
        <div class="form-text" id="contextoMateria" aria-live="polite"></div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-sm-6">
          <label class="form-label" for="cantidad">Cantidad pedida</label>
          <div class="input-group has-validation">
            <input type="number" class="form-control<?= generarClaseError($errores, 'cantidad') ?>" id="cantidad" name="cantidad"
              min="0.01" step="0.01" inputmode="decimal" required value="<?= escapar(formatearValorCampo($datos['cantidad'])) ?>">
            <span class="input-group-text" id="unidadCantidad">-</span>
            <?= generarMensajeError($errores, 'cantidad') ?>
          </div>
          <button type="button" class="btn btn-link btn-sm px-0" id="usarSugerencia" hidden></button>
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="fecha_estimada_llegada">Llegada estimada</label>
          <input type="date" class="form-control<?= generarClaseError($errores, 'fecha_estimada_llegada') ?>" id="fecha_estimada_llegada"
            name="fecha_estimada_llegada" min="<?= escapar(obtenerFechaHoy()) ?>" required value="<?= escapar($datos['fecha_estimada_llegada']) ?>">
          <?= generarMensajeError($errores, 'fecha_estimada_llegada') ?>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="proveedor">Proveedor <span class="text-body-secondary fw-normal">(opcional)</span></label>
        <input type="text" class="form-control<?= generarClaseError($errores, 'proveedor') ?>" id="proveedor" name="proveedor" maxlength="100"
          list="proveedoresAnteriores" value="<?= escapar($datos['proveedor']) ?>">
        <?= generarMensajeError($errores, 'proveedor') ?>
        <datalist id="proveedoresAnteriores">
          <?php foreach ($proveedores as $proveedor) : ?>
            <option value="<?= escapar($proveedor) ?>">
          <?php endforeach; ?>
        </datalist>
      </div>

      <div class="mb-3">
        <label class="form-label" for="observacion">Observación <span class="text-body-secondary fw-normal">(opcional)</span></label>
        <input type="text" class="form-control<?= generarClaseError($errores, 'observacion') ?>" id="observacion" name="observacion"
          maxlength="255" value="<?= escapar($datos['observacion']) ?>" placeholder="Ej.: número de factura, condiciones de pago">
        <?= generarMensajeError($errores, 'observacion') ?>
      </div>

      <div class="d-flex gap-2 justify-content-end mt-4">
        <a class="btn btn-outline-secondary" href="<?= escapar(url('modules/inventario/pedidos.php')) ?>">Cancelar</a>
        <button class="btn btn-primary" type="submit">Registrar pedido</button>
      </div>
    </form>
  </div>
</div>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
