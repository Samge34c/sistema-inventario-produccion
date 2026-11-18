<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

// Recetas con su capacidad de producción actual y proyectada.

$gestorRecetas = new GestorRecetas($conexion);
$filtro = leerParametro('filtro');
$todas = $gestorRecetas->listarRecetas(true);
$activas = array_values(array_filter($todas, static fn (array $receta): bool => $receta['esta_activa']));
$recetas = match ($filtro) {
    'posibles' => array_filter($activas, static fn (array $receta): bool => $receta['puede_producirse']),
    'no-posibles' => array_filter($activas, static fn (array $receta): bool => !$receta['puede_producirse']),
    'archivadas' => array_filter($todas, static fn (array $receta): bool => !$receta['esta_activa']),
    default => $activas,
};
$totalPosibles = count(array_filter($activas, static fn (array $receta): bool => $receta['puede_producirse']));
$totalArchivadas = count($todas) - count($activas);

$tituloPagina = 'Recetas';
$seccionActiva = 'recetas';
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<div class="encabezado-pagina">
  <div>
    <h1>Recetas</h1>
  </div>
  <div class="acciones">
    <a class="btn btn-primary" href="<?= escapar(url('modules/recetas/receta.php')) ?>">
      Nueva receta
    </a>
  </div>
</div>

<?php if ($todas === []) : ?>
  <div class="tarjeta estado-vacio">
    
    <p class="mb-3">Todavía no hay recetas. Cree la primera para calcular cuánto puede producir.</p>
    <a class="btn btn-primary" href="<?= escapar(url('modules/recetas/receta.php')) ?>">Crear receta</a>
  </div>
<?php else : ?>
  <div class="btn-group flex-wrap mb-3" role="group" aria-label="Filtrar recetas">
    <?php
    $opcionesFiltro = [
        '' => 'Activas (' . count($activas) . ')',
        'posibles' => 'Se pueden producir (' . $totalPosibles . ')',
        'no-posibles' => 'No alcanzan (' . (count($activas) - $totalPosibles) . ')',
    ];

    if ($totalArchivadas > 0) {
        $opcionesFiltro['archivadas'] = 'Archivadas (' . $totalArchivadas . ')';
    }
    ?>
    <?php foreach ($opcionesFiltro as $valor => $texto) : ?>
      <a class="btn btn-sm <?= $filtro === $valor ? 'btn-primary' : 'btn-outline-primary' ?>"
        href="<?= escapar(url('modules/recetas/index.php' . ($valor === '' ? '' : '?filtro=' . $valor))) ?>"><?= escapar($texto) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if ($recetas === []) : ?>
    <div class="tarjeta estado-vacio">
      
      No hay recetas en este grupo.
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <?php foreach ($recetas as $receta) : ?>
      <?php
      $capacidad = $receta['capacidad'];
        $proyeccion = $receta['proyeccion'];
        $unidadesMaximas = $capacidad['unidades_maximas'];
        $mejoraConPedidos = ($proyeccion['unidades_maximas'] ?? 0) > ($unidadesMaximas ?? 0);
        ?>
      <div class="col-md-6 col-xl-4">
        <article class="tarjeta h-100 d-flex flex-column">
          <div class="p-3 pb-0 d-flex justify-content-between gap-2">
            <div>
              <h2 class="h5 mb-1">
                <a class="link-body-emphasis text-decoration-none" href="<?= escapar(url('modules/recetas/detalle.php?id=' . $receta['id'])) ?>">
                  <?= escapar($receta['nombre']) ?>
                </a>
              </h2>
              <div class="small text-body-secondary">
                Rinde <?= escapar(pluralizar($receta['cantidad_producir'], 'unidad', 'unidades')) ?> por tanda
                - <?= escapar(pluralizar(count($receta['ingredientes']), 'ingrediente', 'ingredientes')) ?>
              </div>
            </div>
            <?php if (!$receta['esta_activa']) : ?>
              <span class="badge bg-secondary-subtle text-secondary-emphasis align-self-start">Archivada</span>
            <?php elseif ($unidadesMaximas === null) : ?>
              <span class="badge text-bg-secondary align-self-start">Sin ingredientes</span>
            <?php elseif ($receta['puede_producirse']) : ?>
              <span class="badge bg-success-subtle text-success-emphasis align-self-start">Se puede producir</span>
            <?php else : ?>
              <span class="badge text-bg-danger align-self-start">No alcanza</span>
            <?php endif; ?>
          </div>

          <div class="p-3 flex-grow-1">
            <?php if ($unidadesMaximas !== null) : ?>
              <div class="d-flex align-items-baseline gap-2">
                <span class="indicador-valor <?= $unidadesMaximas === 0 ? 'text-danger' : '' ?>"><?= escapar(formatearCantidad($unidadesMaximas)) ?></span>
                <span class="text-body-secondary"><?= $unidadesMaximas === 1 ? 'unidad posible ahora' : 'unidades posibles ahora' ?></span>
              </div>
              <div class="small mt-1">
                
                Limita: <strong><?= escapar($capacidad['limitante']['nombre']) ?></strong>
                (hay <?= escapar(formatearCantidadConUnidad($capacidad['limitante']['cantidad_disponible'], $capacidad['limitante']['unidad_medida'])) ?>)
              </div>
              <?php if ($mejoraConPedidos) : ?>
                <div class="small mt-1 texto-pedido">
                  
                  Con los pedidos por llegar: <?= escapar(pluralizar((int) $proyeccion['unidades_maximas'], 'unidad', 'unidades')) ?>
                </div>
              <?php endif; ?>
            <?php else : ?>
              <p class="small text-body-secondary mb-0">Agregue ingredientes para calcular la producción posible.</p>
            <?php endif; ?>
          </div>

          <div class="px-3 pb-3 d-flex gap-2">
            <a class="btn btn-sm btn-outline-primary" href="<?= escapar(url('modules/recetas/detalle.php?id=' . $receta['id'])) ?>">Ver detalle</a>
            <?php if ($receta['esta_activa'] && $unidadesMaximas !== null) : ?>
              <a class="btn btn-sm btn-primary" href="<?= escapar(url('modules/produccion/plan.php?receta_id=' . $receta['id'])) ?>">
                Planificar
              </a>
            <?php endif; ?>
            <a class="btn btn-sm btn-link text-body-secondary ms-auto" href="<?= escapar(url('modules/recetas/receta.php?id=' . $receta['id'])) ?>">
              Editar
            </a>
          </div>
        </article>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
