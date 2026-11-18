<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

// Crear (sin id) o editar (con ?id=) una materia prima.

$inventario = new GestorInventario($conexion);
$id = leerParametroEntero('id') ?: null;
$materia = $id === null ? null : $inventario->obtenerMateriaPrima($id);

if ($id !== null && $materia === null) {
    agregarMensaje('error', 'La materia prima que busca no existe o fue eliminada.');
    redirigir('modules/inventario/index.php');
}

$datos = $materia ?? ['nombre' => '', 'unidad_medida' => 'g', 'stock_minimo' => '', 'cantidad_inicial' => ''];
$errores = [];

if (esPeticionPost()) {
    verificarTokenCsrf('modules/inventario/index.php');
    $datos = array_merge($datos, $_POST);

    try {
        $inventario->guardarMateriaPrima($_POST, $id);
        agregarMensaje('exito', $id === null
            ? sprintf('Se registró "%s".', trim((string) $_POST['nombre']))
            : sprintf('Se guardaron los cambios de "%s".', trim((string) $_POST['nombre'])));
        redirigir('modules/inventario/index.php');
    } catch (ErrorValidacion $error) {
        $errores = $error->obtenerErrores();
    }
}

$esEdicion = $materia !== null;
$tituloPagina = $esEdicion ? 'Editar materia prima' : 'Nueva materia prima';
$seccionActiva = 'inventario';
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<nav aria-label="Ruta de navegación">
  <ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="<?= escapar(url('modules/inventario/index.php')) ?>">Inventario</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= escapar($tituloPagina) ?></li>
  </ol>
</nav>

<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="encabezado-pagina">
      <div>
        <h1><?= escapar($esEdicion ? $materia['nombre'] : 'Nueva materia prima') ?></h1>
      </div>
    </div>

    <?php if (isset($errores['general'])) : ?>
      <div class="alert alert-danger"><?= escapar($errores['general']) ?></div>
    <?php endif; ?>

    <form method="post" class="tarjeta p-4" novalidate>
      <?= generarCampoCsrf() ?>

      <div class="mb-3">
        <label class="form-label" for="nombre">Nombre</label>
        <input type="text" class="form-control<?= generarClaseError($errores, 'nombre') ?>" id="nombre" name="nombre"
          maxlength="100" required autofocus value="<?= escapar($datos['nombre']) ?>" placeholder="Ej.: Harina de trigo">
        <?= generarMensajeError($errores, 'nombre') ?>
      </div>

      <div class="mb-3">
        <label class="form-label" for="unidad_medida">Unidad de medida</label>
        <select class="form-select<?= generarClaseError($errores, 'unidad_medida') ?>" id="unidad_medida" name="unidad_medida"
          required aria-describedby="ayudaUnidad">
          <?php foreach (GestorInventario::UNIDADES as $valor => $texto) : ?>
            <option value="<?= escapar($valor) ?>" <?= $datos['unidad_medida'] === $valor ? 'selected' : '' ?>><?= escapar($texto) ?></option>
          <?php endforeach; ?>
        </select>
        <?= generarMensajeError($errores, 'unidad_medida') ?>
        <div class="form-text" id="ayudaUnidad">Las recetas usan esta misma unidad.</div>
      </div>

      <div class="row g-3">
        <?php if (!$esEdicion) : ?>
          <div class="col-sm-6">
            <label class="form-label" for="cantidad_inicial">Existencia actual</label>
            <input type="number" class="form-control<?= generarClaseError($errores, 'cantidad_inicial') ?>" id="cantidad_inicial"
              name="cantidad_inicial" min="0" step="0.01" inputmode="decimal" placeholder="0"
              value="<?= escapar(formatearValorCampo($datos['cantidad_inicial'])) ?>" aria-describedby="ayudaInicial">
            <?= generarMensajeError($errores, 'cantidad_inicial') ?>
            <div class="form-text" id="ayudaInicial">Queda registrada como entrada "Inventario inicial".</div>
          </div>
        <?php else : ?>
          <div class="col-sm-6">
            <span class="form-label d-block">Existencia actual</span>
            <div class="form-control-plaintext fw-semibold">
              <?= escapar(formatearCantidadConUnidad($materia['cantidad_disponible'], $materia['unidad_medida'])) ?>
            </div>
            <a class="small" href="<?= escapar(url('modules/inventario/movimiento.php?id=' . $materia['id'])) ?>">Registrar entrada o salida</a>
          </div>
        <?php endif; ?>

        <div class="col-sm-6">
          <label class="form-label" for="stock_minimo">Stock mínimo</label>
          <input type="number" class="form-control<?= generarClaseError($errores, 'stock_minimo') ?>" id="stock_minimo"
            name="stock_minimo" min="0" step="0.01" inputmode="decimal" required placeholder="0"
            value="<?= escapar(formatearValorCampo($datos['stock_minimo'])) ?>" aria-describedby="ayudaMinimo">
          <?= generarMensajeError($errores, 'stock_minimo') ?>
          <div class="form-text" id="ayudaMinimo">Se avisa cuando la existencia baja de este valor.</div>
        </div>
      </div>

      <div class="d-flex gap-2 justify-content-end mt-4">
        <a class="btn btn-outline-secondary" href="<?= escapar(url('modules/inventario/index.php')) ?>">Cancelar</a>
        <button class="btn btn-primary" type="submit">
          <?= $esEdicion ? 'Guardar cambios' : 'Registrar materia prima' ?>
        </button>
      </div>
    </form>
  </div>
</div>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
