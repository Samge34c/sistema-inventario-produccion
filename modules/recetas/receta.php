<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

// Crear (sin id) o editar (con ?id=) una receta y sus ingredientes.

$gestorRecetas = new GestorRecetas($conexion);
$inventario = new GestorInventario($conexion);
$id = leerParametroEntero('id') ?: null;
$receta = $id === null ? null : $gestorRecetas->obtenerReceta($id);

if ($id !== null && $receta === null) {
    agregarMensaje('error', 'La receta que busca no existe o fue eliminada.');
    redirigir('modules/recetas/index.php');
}

$materias = $inventario->listarOpcionesMateriasPrimas();

if ($materias === []) {
    agregarMensaje('info', 'Para crear una receta, primero registre las materias primas que usa.');
    redirigir('modules/inventario/materia-prima.php');
}

$datos = [
    'nombre' => $receta['nombre'] ?? '',
    'descripcion' => $receta['descripcion'] ?? '',
    'cantidad_producir' => (string) ($receta['cantidad_producir'] ?? ''),
    'ingredientes' => array_map(static fn (array $ingrediente): array => [
        'materia_prima_id' => (string) $ingrediente['materia_prima_id'],
        'cantidad' => formatearValorCampo($ingrediente['cantidad']),
    ], $receta['ingredientes'] ?? []),
];
$errores = [];

if (esPeticionPost()) {
    verificarTokenCsrf('modules/recetas/index.php');
    $datos = array_merge($datos, $_POST);
    $datos['ingredientes'] = is_array($_POST['ingredientes'] ?? null) ? array_values($_POST['ingredientes']) : [];
    $_POST['ingredientes'] = $datos['ingredientes'];

    try {
        $idGuardado = $gestorRecetas->guardarReceta($_POST, $id);
        agregarMensaje('exito', $id === null ? 'Receta creada.' : 'Cambios guardados.');
        redirigir('modules/recetas/detalle.php?id=' . $idGuardado);
    } catch (ErrorValidacion $error) {
        $errores = $error->obtenerErrores();
    }
}

if ($datos['ingredientes'] === []) {
    $datos['ingredientes'] = [['materia_prima_id' => '', 'cantidad' => '']];
}

$datosMaterias = array_map(static fn (array $materia): array => [
    'id' => $materia['id'],
    'unidad' => obtenerNombreUnidad($materia['unidad_medida']),
    'disponible' => $materia['cantidad_disponible'],
], $materias);

$esEdicion = $receta !== null;
$tituloPagina = $esEdicion ? 'Editar ' . $receta['nombre'] : 'Nueva receta';
$seccionActiva = 'recetas';
$scriptsPagina = ['assets/js/recetas-formulario.js'];

/**
 * Fila de ingrediente del formulario. Se usa también como plantilla (índice «__indice__»).
 *
 * @param list<array{id: int, nombre: string, unidad_medida: string, cantidad_disponible: float}> $materias
 * @param array<string, string> $errores
 */
function generarFilaIngrediente(string $indice, string $materiaSeleccionada, string $cantidad, array $materias, array $errores): string
{
    $opciones = '<option value="">Elija la materia prima</option>';

    foreach ($materias as $materia) {
        $opciones .= '<option value="' . $materia['id'] . '"' . ((string) $materia['id'] === $materiaSeleccionada ? ' selected' : '') . '>'
            . escapar($materia['nombre']) . '</option>';
    }

    $claveMateria = 'ingredientes_' . $indice . '_materia';
    $claveCantidad = 'ingredientes_' . $indice . '_cantidad';

    return '<div class="row g-2 fila-ingrediente" data-fila-ingrediente>'
        . '<div class="col-sm-7"><label class="visually-hidden" for="materia_' . $indice . '">Materia prima</label>'
        . '<select class="form-select' . generarClaseError($errores, $claveMateria) . '" id="materia_' . $indice . '" name="ingredientes[' . $indice . '][materia_prima_id]" data-campo-materia>'
        . $opciones . '</select>' . generarMensajeError($errores, $claveMateria) . '</div>'
        . '<div class="col-9 col-sm-4"><label class="visually-hidden" for="cantidad_' . $indice . '">Cantidad por tanda</label>'
        . '<div class="input-group has-validation"><input type="number" class="form-control' . generarClaseError($errores, $claveCantidad) . '" id="cantidad_' . $indice . '" name="ingredientes[' . $indice . '][cantidad]" min="0.01" step="0.01" inputmode="decimal" placeholder="Cantidad" value="' . escapar($cantidad) . '" data-campo-cantidad>'
        . '<span class="input-group-text" data-unidad>—</span>' . generarMensajeError($errores, $claveCantidad) . '</div></div>'
        . '<div class="col-3 col-sm-1 text-end"><button type="button" class="btn btn-outline-secondary" data-quitar-ingrediente title="Quitar ingrediente">'
        . '<i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Quitar ingrediente</span></button></div>'
        . '</div>';
}

require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<nav aria-label="Ruta de navegación">
  <ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="<?= escapar(url('modules/recetas/index.php')) ?>">Recetas</a></li>
    <?php if ($esEdicion) : ?>
      <li class="breadcrumb-item"><a href="<?= escapar(url('modules/recetas/detalle.php?id=' . $receta['id'])) ?>"><?= escapar($receta['nombre']) ?></a></li>
    <?php endif; ?>
    <li class="breadcrumb-item active" aria-current="page"><?= $esEdicion ? 'Editar' : 'Nueva receta' ?></li>
  </ol>
</nav>

<div class="row justify-content-center">
  <div class="col-xl-9">
    <div class="encabezado-pagina">
      <div>
        <h1><?= $esEdicion ? 'Editar receta' : 'Nueva receta' ?></h1>
        <p>Escriba las cantidades de una tanda y cuántas unidades salen de ella. El sistema escala el resto.</p>
      </div>
    </div>

    <form method="post" novalidate id="formularioReceta">
      <?= generarCampoCsrf() ?>
      <script type="application/json" id="datosMaterias"><?= json_encode($datosMaterias, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

      <div class="tarjeta p-4 mb-3">
        <h2 class="h6 text-uppercase text-body-secondary mb-3">Producto</h2>
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label" for="nombre">Nombre del producto</label>
            <input type="text" class="form-control<?= generarClaseError($errores, 'nombre') ?>" id="nombre" name="nombre" maxlength="100"
              required value="<?= escapar($datos['nombre']) ?>" placeholder="Ej.: Pan francés" <?= $esEdicion ? '' : 'autofocus' ?>>
            <?= generarMensajeError($errores, 'nombre') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="cantidad_producir">Rendimiento por tanda</label>
            <div class="input-group has-validation">
              <input type="number" class="form-control<?= generarClaseError($errores, 'cantidad_producir') ?>" id="cantidad_producir"
                name="cantidad_producir" min="1" step="1" required value="<?= escapar($datos['cantidad_producir']) ?>" placeholder="40">
              <span class="input-group-text">unidades</span>
              <?= generarMensajeError($errores, 'cantidad_producir') ?>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label" for="descripcion">Descripción <span class="text-body-secondary fw-normal">(opcional)</span></label>
            <input type="text" class="form-control<?= generarClaseError($errores, 'descripcion') ?>" id="descripcion" name="descripcion"
              maxlength="255" value="<?= escapar($datos['descripcion']) ?>">
            <?= generarMensajeError($errores, 'descripcion') ?>
          </div>
        </div>
      </div>

      <div class="tarjeta p-4 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <h2 class="h6 text-uppercase text-body-secondary mb-0">Ingredientes de una tanda</h2>
          <a class="small" href="<?= escapar(url('modules/inventario/materia-prima.php')) ?>" target="_blank" rel="noopener">
            ¿Falta una materia prima? Regístrela <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
          </a>
        </div>
        <p class="texto-ayuda">La cantidad va en la unidad de cada materia prima, la misma del inventario.</p>

        <?php if (isset($errores['ingredientes'])) : ?>
          <div class="alert alert-danger py-2"><?= escapar($errores['ingredientes']) ?></div>
        <?php endif; ?>

        <div id="listaIngredientes">
          <?php foreach ($datos['ingredientes'] as $indice => $fila) : ?>
            <?= generarFilaIngrediente(
                (string) $indice,
                (string) ($fila['materia_prima_id'] ?? ''),
                (string) ($fila['cantidad'] ?? ''),
                $materias,
                $errores
            ) ?>
          <?php endforeach; ?>
        </div>

        <template id="plantillaIngrediente">
          <?= generarFilaIngrediente('__indice__', '', '', $materias, []) ?>
        </template>

        <button type="button" class="btn btn-outline-primary btn-sm mt-3" id="agregarIngrediente">
          <i class="bi bi-plus-lg" aria-hidden="true"></i> Agregar ingrediente
        </button>

        <div class="alert alert-light border mt-3 mb-0 small" id="vistaCapacidad" aria-live="polite" hidden></div>
      </div>

      <div class="d-flex gap-2 justify-content-end">
        <a class="btn btn-outline-secondary" href="<?= escapar(url($esEdicion ? 'modules/recetas/detalle.php?id=' . $receta['id'] : 'modules/recetas/index.php')) ?>">Cancelar</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg" aria-hidden="true"></i> <?= $esEdicion ? 'Guardar cambios' : 'Crear receta' ?></button>
      </div>
    </form>

    <?php if ($esEdicion) : ?>
      <div class="tarjeta p-4 mt-4 border-danger-subtle">
        <h2 class="h6 text-uppercase text-body-secondary">Archivar o eliminar</h2>
        <p class="small text-body-secondary">
          Archivar oculta la receta de la caja y de la planificación, pero conserva sus ventas y producciones.
          Eliminar solo es posible si nunca se ha producido ni vendido.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <form method="post" action="<?= escapar(url('modules/recetas/cambiar-estado.php')) ?>">
            <?= generarCampoCsrf() ?>
            <input type="hidden" name="id" value="<?= $receta['id'] ?>">
            <input type="hidden" name="activa" value="<?= $receta['esta_activa'] ? '0' : '1' ?>">
            <button class="btn btn-outline-secondary" type="submit">
              <i class="bi <?= $receta['esta_activa'] ? 'bi-archive' : 'bi-arrow-counterclockwise' ?>" aria-hidden="true"></i>
              <?= $receta['esta_activa'] ? 'Archivar receta' : 'Reactivar receta' ?>
            </button>
          </form>
          <form method="post" action="<?= escapar(url('modules/recetas/eliminar.php')) ?>"
            data-confirmar="¿Eliminar la receta «<?= escapar($receta['nombre']) ?>» y sus ingredientes? Esta acción no se puede deshacer."
            data-confirmar-boton="Eliminar receta">
            <?= generarCampoCsrf() ?>
            <input type="hidden" name="id" value="<?= $receta['id'] ?>">
            <button class="btn btn-outline-danger" type="submit"><i class="bi bi-trash" aria-hidden="true"></i> Eliminar</button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
