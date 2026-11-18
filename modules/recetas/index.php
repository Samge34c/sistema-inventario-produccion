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

  <?php if ($recetas !== []) : ?>
    <div class="table-responsive">
      <table class="table table-bordered table-hover">
        <thead>
          <tr>
            <th scope="col">Receta</th>
            <th scope="col" class="numero">Rinde por tanda</th>
            <th scope="col" class="numero">Se pueden hacer ahora</th>
            <th scope="col">Ingrediente que limita</th>
            <th scope="col">Con los pedidos por llegar</th>
            <th scope="col">Estado</th>
            <th scope="col">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recetas as $receta) : ?>
            <?php
            $capacidad = $receta['capacidad'];
              $proyeccion = $receta['proyeccion'];
              $unidadesMaximas = $capacidad['unidades_maximas'];
              $mejoraConPedidos = ($proyeccion['unidades_maximas'] ?? 0) > ($unidadesMaximas ?? 0);
              ?>
            <tr>
              <td>
                <a href="<?= escapar(url('modules/recetas/detalle.php?id=' . $receta['id'])) ?>"><?= escapar($receta['nombre']) ?></a>
                <div class="small text-body-secondary"><?= escapar(pluralizar(count($receta['ingredientes']), 'ingrediente', 'ingredientes')) ?></div>
              </td>
              <td class="numero"><?= escapar(pluralizar($receta['cantidad_producir'], 'unidad', 'unidades')) ?></td>
              <?php if ($unidadesMaximas !== null) : ?>
                <td class="numero <?= $unidadesMaximas === 0 ? 'estado-mal' : '' ?>"><strong><?= escapar(formatearCantidad($unidadesMaximas)) ?></strong></td>
                <td>
                  <?= escapar($capacidad['limitante']['nombre']) ?>
                  (hay <?= escapar(formatearCantidadConUnidad($capacidad['limitante']['cantidad_disponible'], $capacidad['limitante']['unidad_medida'])) ?>)
                </td>
                <td class="texto-pedido">
                  <?= $mejoraConPedidos ? escapar(pluralizar((int) $proyeccion['unidades_maximas'], 'unidad', 'unidades')) : '-' ?>
                </td>
              <?php else : ?>
                <td colspan="3" class="text-body-secondary">Agregue ingredientes para calcular la producción posible.</td>
              <?php endif; ?>
              <td>
                <?php if (!$receta['esta_activa']) : ?>
                  <span class="estado">Archivada</span>
                <?php elseif ($unidadesMaximas === null) : ?>
                  <span class="estado">Sin ingredientes</span>
                <?php elseif ($receta['puede_producirse']) : ?>
                  <span class="estado estado-bien">Se puede producir</span>
                <?php else : ?>
                  <span class="estado estado-mal">No alcanza</span>
                <?php endif; ?>
              </td>
              <td class="text-nowrap">
                <a href="<?= escapar(url('modules/recetas/detalle.php?id=' . $receta['id'])) ?>">Ver</a>
                <?php if ($receta['esta_activa'] && $unidadesMaximas !== null) : ?>
                  | <a href="<?= escapar(url('modules/produccion/plan.php?receta_id=' . $receta['id'])) ?>">Planificar</a>
                <?php endif; ?>
                | <a href="<?= escapar(url('modules/recetas/receta.php?id=' . $receta['id'])) ?>">Editar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
