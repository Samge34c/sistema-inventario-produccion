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
      <i class="bi bi-plus-lg" aria-hidden="true"></i> Nueva receta
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
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th scope="col">Receta</th>
            <th scope="col" class="numero">Se pueden hacer</th>
            <th scope="col">Qué lo limita</th>
            <th scope="col"><span class="visually-hidden">Acción</span></th>
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
                <a class="receta-nombre" href="<?= escapar(url('modules/recetas/detalle.php?id=' . $receta['id'])) ?>">
                  <img class="miniatura" src="<?= escapar(urlImagenProducto($receta['nombre'])) ?>" alt="" width="40" height="40">
                  <?= escapar($receta['nombre']) ?>
                </a>
              </td>
              <?php if ($unidadesMaximas !== null) : ?>
                <td class="numero">
                  <span class="cantidad-grande <?= $unidadesMaximas === 0 ? 'estado-mal' : '' ?>"><?= escapar(formatearCantidad($unidadesMaximas)) ?></span>
                  <?php if ($mejoraConPedidos) : ?>
                    <div class="small texto-pedido">Con los pedidos por llegar: <?= escapar(pluralizar((int) $proyeccion['unidades_maximas'], 'unidad', 'unidades')) ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <?= escapar($capacidad['limitante']['nombre']) ?>
                  <span class="small text-body-secondary">(hay <?= escapar(formatearCantidadConUnidad($capacidad['limitante']['cantidad_disponible'], $capacidad['limitante']['unidad_medida'])) ?>)</span>
                </td>
                <td class="text-end">
                  <?php if ($receta['esta_activa']) : ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= escapar(url('modules/produccion/plan.php?receta_id=' . $receta['id'])) ?>"><i class="bi bi-calendar-plus" aria-hidden="true"></i> Planificar</a>
                  <?php endif; ?>
                </td>
              <?php else : ?>
                <td colspan="3" class="text-body-secondary">
                  Sin ingredientes. <a href="<?= escapar(url('modules/recetas/receta.php?id=' . $receta['id'])) ?>">Agregarlos</a>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
