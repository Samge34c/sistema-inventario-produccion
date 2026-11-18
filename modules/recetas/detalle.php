<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

$gestorRecetas = new GestorRecetas($conexion);
$receta = $gestorRecetas->obtenerReceta(leerParametroEntero('id'));

if ($receta === null) {
    agregarMensaje('error', 'La receta que busca no existe o fue eliminada.');
    redirigir('modules/recetas/index.php');
}

$cantidadesPorLlegar = (new GestorPedidos($conexion))->obtenerCantidadesPorLlegar();
$ultimasProducciones = (new GestorProduccion($conexion))->listarUltimasProduccionesDeReceta($receta['id']);
$promedioVentaDiaria = (new GestorVentas($conexion))->calcularPromedioDiarioPorProducto()[$receta['id']] ?? 0.0;
$capacidad = $receta['capacidad'];
$proyeccion = $receta['proyeccion'];
$unidadesMaximas = $capacidad['unidades_maximas'];
$idLimitante = $capacidad['limitante']['materia_prima_id'] ?? null;
$hayPedidos = array_intersect_key($cantidadesPorLlegar, array_flip(array_column($receta['ingredientes'], 'materia_prima_id'))) !== [];
$proyeccionPorMateria = array_column($proyeccion['ingredientes'], 'unidades_posibles', 'materia_prima_id');

$datosCalculadora = [
    'rendimiento' => $receta['cantidad_producir'],
    'ingredientes' => array_map(static fn (array $ingrediente): array => [
        'nombre' => $ingrediente['nombre'],
        'unidad' => obtenerNombreUnidad($ingrediente['unidad_medida']),
        'cantidad' => $ingrediente['cantidad'],
        'disponible' => $ingrediente['cantidad_disponible'],
    ], $receta['ingredientes']),
    'urlPlan' => url('modules/produccion/plan.php?receta_id=' . $receta['id']),
];
$unidadesSugeridas = $promedioVentaDiaria > 0 ? (int) ceil($promedioVentaDiaria) : $receta['cantidad_producir'];

$tituloPagina = $receta['nombre'];
$seccionActiva = 'recetas';
$scriptsPagina = ['assets/js/recetas-calculadora.js'];
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<nav aria-label="Ruta de navegación">
  <ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="<?= escapar(url('modules/recetas/index.php')) ?>">Recetas</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= escapar($receta['nombre']) ?></li>
  </ol>
</nav>

<div class="encabezado-pagina">
  <div>
    <h1>
      <?= escapar($receta['nombre']) ?>
      <?php if (!$receta['esta_activa']) : ?>
        <span class="badge bg-secondary-subtle text-secondary-emphasis fs-6 align-middle">Archivada</span>
      <?php endif; ?>
    </h1>
    <p>
      <?= $receta['descripcion'] ? escapar($receta['descripcion']) . ' - ' : '' ?>
      Una tanda rinde <?= escapar(pluralizar($receta['cantidad_producir'], 'unidad', 'unidades')) ?>.
    </p>
  </div>
  <div class="acciones">
    <a class="btn btn-outline-primary" href="<?= escapar(url('modules/recetas/receta.php?id=' . $receta['id'])) ?>">
      Editar
    </a>
    <?php if ($receta['esta_activa'] && $unidadesMaximas !== null) : ?>
      <a class="btn btn-primary" href="<?= escapar(url('modules/produccion/plan.php?receta_id=' . $receta['id'])) ?>">
        Planificar producción
      </a>
    <?php endif; ?>
  </div>
</div>

<?php if ($unidadesMaximas === null) : ?>
  <div class="alert alert-warning">
    Esta receta no tiene ingredientes.
    <a href="<?= escapar(url('modules/recetas/receta.php?id=' . $receta['id'])) ?>">Agréguelos</a> para calcular cuánto se puede producir.
  </div>
<?php else : ?>
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="tarjeta indicador">
        <div>
          <div class="indicador-valor"><?= escapar(formatearCantidad($unidadesMaximas)) ?></div>
          <div class="indicador-etiqueta">unidades se pueden producir ahora</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="tarjeta indicador">
        <div>
          <div class="fw-semibold fs-5"><?= escapar($capacidad['limitante']['nombre']) ?></div>
          <div class="indicador-etiqueta">es el ingrediente que limita la producción</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="tarjeta indicador">
        <div>
          <div class="indicador-valor"><?= escapar(formatearCantidad((int) $proyeccion['unidades_maximas'])) ?></div>
          <div class="indicador-etiqueta">
            unidades al llegar los pedidos<?= $hayPedidos ? '' : ' (no hay pedidos pendientes de estos ingredientes)' ?>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-xl-7">
    <div class="tarjeta">
      <div class="card-header">Ingredientes y capacidad</div>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th scope="col">Ingrediente</th>
              <th scope="col" class="text-end">Por tanda</th>
              <th scope="col" class="text-end">En existencia</th>
              <th scope="col" class="text-end">Alcanza para</th>
              <?php if ($hayPedidos) : ?>
                <th scope="col" class="text-end">Con pedidos</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($capacidad['ingredientes'] as $ingrediente) : ?>
              <?php $esLimitante = $ingrediente['materia_prima_id'] === $idLimitante; ?>
              <tr class="<?= $esLimitante ? 'fila-alerta' : '' ?>">
                <td>
                  <?= escapar($ingrediente['nombre']) ?>
                  <?php if ($esLimitante) : ?>
                    <span class="badge text-bg-warning ms-1">Limitante</span>
                  <?php endif; ?>
                </td>
                <td class="numero"><?= escapar(formatearCantidadConUnidad($ingrediente['cantidad'], $ingrediente['unidad_medida'])) ?></td>
                <td class="numero"><?= escapar(formatearCantidadConUnidad($ingrediente['cantidad_disponible'], $ingrediente['unidad_medida'])) ?></td>
                <td class="numero fw-semibold <?= $ingrediente['unidades_posibles'] === 0 ? 'text-danger' : '' ?>">
                  <?= escapar(pluralizar($ingrediente['unidades_posibles'], 'unidad', 'unidades')) ?>
                </td>
                <?php if ($hayPedidos) : ?>
                  <td class="numero <?= ($proyeccionPorMateria[$ingrediente['materia_prima_id']] ?? 0) > $ingrediente['unidades_posibles'] ? 'texto-pedido fw-semibold' : 'text-body-secondary' ?>">
                    <?= escapar(formatearCantidad((int) ($proyeccionPorMateria[$ingrediente['materia_prima_id']] ?? 0))) ?>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-xl-5">
    <?php if ($receta['ingredientes'] !== []) : ?>
      <div class="tarjeta mb-4" id="calculadora">
        <div class="card-header">¿Qué necesito para producir...?</div>
        <div class="p-3">
          <script type="application/json" id="datosCalculadora"><?= json_encode($datosCalculadora, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
          <label class="form-label" for="unidadesCalculadora">Unidades a producir</label>
          <div class="input-group mb-1">
            <input type="number" class="form-control" id="unidadesCalculadora" min="1" step="1" value="<?= $unidadesSugeridas ?>">
            <span class="input-group-text">unidades</span>
          </div>
          <?php if ($promedioVentaDiaria > 0) : ?>
            <div class="form-text mb-3">
              En promedio se venden <?= escapar(formatearCantidad($promedioVentaDiaria, 1)) ?> unidades al día (últimos 7 días).
            </div>
          <?php endif; ?>
          <div id="resultadoCalculadora" aria-live="polite"></div>
          <a class="btn btn-primary w-100 mt-3" id="planificarCalculadora" href="<?= escapar($datosCalculadora['urlPlan']) ?>">
            Planificar esta cantidad
          </a>
        </div>
      </div>
    <?php endif; ?>

    <div class="tarjeta">
      <div class="card-header">Últimas producciones</div>
      <?php if ($ultimasProducciones === []) : ?>
        <p class="estado-vacio mb-0">Todavía no se ha planificado esta receta.</p>
      <?php else : ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($ultimasProducciones as $produccion) : ?>
            <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
              <a href="<?= escapar(url('modules/produccion/detalle.php?id=' . $produccion['id'])) ?>">
                <?= escapar(describirDia($produccion['fecha_planeada'])) ?>
              </a>
              <span class="text-body-secondary small">
                <?= escapar(formatearCantidad((int) ($produccion['cantidad_producida'] ?? $produccion['cantidad_planeada']))) ?> unidades
              </span>
              <?= generarInsigniaProduccion(
                  $produccion['estado'],
                  $produccion['estado'] === 'PLANEADA' && $produccion['fecha_planeada'] < obtenerFechaHoy()
              ) ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
