<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

// Crear (sin id) o modificar (con ?id=) un plan de producción.

$gestorProduccion = new GestorProduccion($conexion);
$gestorRecetas = new GestorRecetas($conexion);
$id = leerParametroEntero('id') ?: null;
$produccion = $id === null ? null : $gestorProduccion->obtenerProduccion($id);

if ($id !== null && ($produccion === null || $produccion['estado'] !== 'PLANEADA')) {
    agregarMensaje('error', 'Solo se pueden modificar los planes pendientes.');
    redirigir('modules/produccion/index.php');
}

$recetas = array_values(array_filter(
    $gestorRecetas->listarRecetas(true),
    static fn (array $receta): bool => $receta['ingredientes'] !== []
        && ($receta['esta_activa'] || $receta['id'] === ($produccion['receta_id'] ?? null))
));

if ($recetas === []) {
    agregarMensaje('info', 'Para planificar la producción, primero cree una receta con sus ingredientes.');
    redirigir('modules/recetas/receta.php');
}

$promedios = (new GestorVentas($conexion))->calcularPromedioDiarioPorProducto();
$datos = [
    'receta_id' => (string) ($produccion['receta_id'] ?? leerParametroEntero('receta_id')),
    'fecha_planeada' => $produccion['fecha_planeada'] ?? leerParametroFecha('fecha', obtenerFechaHoy()),
    'cantidad_planeada' => (string) ($produccion['cantidad_planeada'] ?? (leerParametroEntero('cantidad') ?: '')),
    'observacion' => $produccion['observacion'] ?? '',
];
$errores = [];

if (esPeticionPost()) {
    verificarTokenCsrf('modules/produccion/index.php');
    $datos = array_merge($datos, $_POST);

    try {
        $idGuardado = $gestorProduccion->planificarProduccion($_POST, $id);
        $guardado = $gestorProduccion->obtenerProduccion($idGuardado);
        $estado = $guardado['analisis']['estado'] ?? CalculadoraProduccion::COMPLETO;
        agregarMensaje(
            $estado === CalculadoraProduccion::FALTAN_MATERIALES ? 'aviso' : 'exito',
            match ($estado) {
                CalculadoraProduccion::FALTAN_MATERIALES => 'Plan guardado, pero faltan materiales. Revise el detalle abajo.',
                CalculadoraProduccion::DEPENDE_DE_PEDIDOS => 'Plan guardado. Los materiales alcanzan si llegan los pedidos pendientes.',
                default => 'Plan guardado. Los materiales alcanzan.',
            }
        );
        redirigir('modules/produccion/detalle.php?id=' . $idGuardado);
    } catch (ErrorValidacion $error) {
        $errores = $error->obtenerErrores();
    }
}

$datosRecetas = array_map(static fn (array $receta): array => [
    'id' => $receta['id'],
    'rendimiento' => $receta['cantidad_producir'],
    'maximo' => $receta['capacidad']['unidades_maximas'],
    'promedio' => isset($promedios[$receta['id']]) ? (int) ceil($promedios[$receta['id']]) : null,
    'ingredientes' => array_map(static fn (array $ingrediente): array => [
        'nombre' => $ingrediente['nombre'],
        'unidad' => obtenerNombreUnidad($ingrediente['unidad_medida']),
        'cantidad' => $ingrediente['cantidad'],
        'disponible' => $ingrediente['cantidad_disponible'],
    ], $receta['ingredientes']),
], $recetas);

$esEdicion = $produccion !== null;
$tituloPagina = $esEdicion ? 'Modificar plan' : 'Planificar producción';
$seccionActiva = 'produccion';
$scriptsPagina = ['assets/js/produccion-plan.js'];
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<nav aria-label="Ruta de navegación">
  <ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="<?= escapar(url('modules/produccion/index.php')) ?>">Producción</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= escapar($tituloPagina) ?></li>
  </ol>
</nav>

<div class="encabezado-pagina">
  <div>
    <h1><?= escapar($tituloPagina) ?></h1>
    <p>Elija qué producto hornear, cuándo y cuántas unidades. A la derecha verá si alcanzan los materiales.</p>
  </div>
</div>

<?php if (isset($errores['general'])) : ?>
  <div class="alert alert-danger"><?= escapar($errores['general']) ?></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-6">
    <form method="post" class="tarjeta p-4" novalidate id="formularioPlan">
      <?= generarCampoCsrf() ?>
      <script type="application/json" id="datosRecetas"><?= json_encode($datosRecetas, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

      <div class="mb-3">
        <label class="form-label" for="receta_id">Producto</label>
        <select class="form-select<?= generarClaseError($errores, 'receta_id') ?>" id="receta_id" name="receta_id" required>
          <option value="">Elija una receta</option>
          <?php foreach ($recetas as $receta) : ?>
            <option value="<?= $receta['id'] ?>" <?= (string) $receta['id'] === (string) $datos['receta_id'] ? 'selected' : '' ?>>
              <?= escapar($receta['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?= generarMensajeError($errores, 'receta_id') ?>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-sm-6">
          <label class="form-label" for="fecha_planeada">Fecha</label>
          <input type="date" class="form-control<?= generarClaseError($errores, 'fecha_planeada') ?>" id="fecha_planeada" name="fecha_planeada"
            required value="<?= escapar($datos['fecha_planeada']) ?>" min="<?= escapar(min(obtenerFechaHoy(), (string) $datos['fecha_planeada'])) ?>">
          <?= generarMensajeError($errores, 'fecha_planeada') ?>
          <div class="d-flex gap-2 mt-1">
            <button type="button" class="btn btn-link btn-sm p-0" data-fecha="<?= escapar(obtenerFechaHoy()) ?>">Hoy</button>
            <button type="button" class="btn btn-link btn-sm p-0" data-fecha="<?= escapar(sumarDias(obtenerFechaHoy(), 1)) ?>">Mañana</button>
          </div>
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="cantidad_planeada">Unidades a producir</label>
          <input type="number" class="form-control<?= generarClaseError($errores, 'cantidad_planeada') ?>" id="cantidad_planeada"
            name="cantidad_planeada" min="1" step="1" required value="<?= escapar($datos['cantidad_planeada']) ?>">
          <?= generarMensajeError($errores, 'cantidad_planeada') ?>
          <div class="d-flex flex-wrap gap-2 mt-1" id="atajosCantidad"></div>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="observacion">Observación <span class="text-body-secondary fw-normal">(opcional)</span></label>
        <input type="text" class="form-control<?= generarClaseError($errores, 'observacion') ?>" id="observacion" name="observacion"
          maxlength="255" value="<?= escapar((string) $datos['observacion']) ?>" placeholder="Ej.: pedido especial, tanda de la tarde">
        <?= generarMensajeError($errores, 'observacion') ?>
      </div>

      <div class="d-flex gap-2 justify-content-end mt-4">
        <a class="btn btn-outline-secondary" href="<?= escapar(url($esEdicion ? 'modules/produccion/detalle.php?id=' . $produccion['id'] : 'modules/produccion/index.php')) ?>">Cancelar</a>
        <button class="btn btn-primary" type="submit">
          <i class="bi bi-check-lg" aria-hidden="true"></i> <?= $esEdicion ? 'Guardar cambios' : 'Guardar plan' ?>
        </button>
      </div>
    </form>
  </div>

  <div class="col-lg-6">
    <div class="tarjeta">
      <div class="card-header">Materiales necesarios</div>
      <div class="p-3" id="vistaMateriales" aria-live="polite">
        <p class="text-body-secondary mb-0">Elija el producto y la cantidad para ver los materiales.</p>
      </div>
    </div>
    <p class="texto-ayuda mt-2">
      Esta vista compara con la existencia de hoy. Al guardar, el sistema también descuenta lo que necesitan
      los planes anteriores y suma los pedidos que llegan antes de la fecha.
    </p>
  </div>
</div>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
