<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

$inventario = new GestorInventario($conexion);
$filtroEstado = leerParametro('estado') === 'ALERTA' ? 'ALERTA' : '';
$todasLasMaterias = $inventario->listarMateriasPrimas();
$materias = $filtroEstado === '' ? $todasLasMaterias : $inventario->listarMateriasPrimas('', $filtroEstado);
$totalEnAlerta = count(array_filter(
    $todasLasMaterias,
    static fn (array $materia): bool => $materia['estado'] !== GestorInventario::DISPONIBLE
));

$tituloPagina = 'Inventario';
$seccionActiva = 'inventario';
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<div class="encabezado-pagina">
  <div>
    <h1>Inventario</h1>
  </div>
  <div class="acciones">
    <a class="btn btn-outline-primary" href="<?= escapar(url('modules/inventario/pedido.php')) ?>">
      <i class="bi bi-truck" aria-hidden="true"></i> Registrar pedido
    </a>
    <a class="btn btn-primary" href="<?= escapar(url('modules/inventario/materia-prima.php')) ?>">
      <i class="bi bi-plus-lg" aria-hidden="true"></i> Nueva materia prima
    </a>
  </div>
</div>

<?= generarPestanas(require __DIR__ . '/pestanas.php', 'materias') ?>

<?php if ($todasLasMaterias === []) : ?>
  <div class="tarjeta estado-vacio">
    
    <p class="mb-3">Todavía no hay materias primas registradas.</p>
    <a class="btn btn-primary" href="<?= escapar(url('modules/inventario/materia-prima.php')) ?>">Registrar la primera</a>
  </div>
<?php else : ?>
  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <div class="btn-group" role="group" aria-label="Filtrar por estado">
      <a class="btn btn-sm <?= $filtroEstado === '' ? 'btn-primary' : 'btn-outline-primary' ?>"
        href="<?= escapar(url('modules/inventario/index.php')) ?>">Todas (<?= count($todasLasMaterias) ?>)</a>
      <a class="btn btn-sm <?= $filtroEstado === 'ALERTA' ? 'btn-primary' : 'btn-outline-primary' ?>"
        href="<?= escapar(url('modules/inventario/index.php?estado=ALERTA')) ?>">
        Por reabastecer (<?= $totalEnAlerta ?>)
      </a>
    </div>
    <div class="d-flex align-items-center gap-2">
      <label class="mb-0" for="buscarMateria">Buscar:</label>
      <input type="search" class="form-control form-control-sm" id="buscarMateria" style="max-width: 220px"
        placeholder="Nombre de la materia prima" data-filtrar="#tablaMaterias">
    </div>
  </div>

  <div class="tarjeta">
    <div class="table-responsive">
      <table class="table table-hover align-middle" id="tablaMaterias">
        <thead>
          <tr>
            <th scope="col">Materia prima</th>
            <th scope="col" class="text-end">Existencia</th>
            <th scope="col" class="text-end">Mínimo</th>
            <th scope="col">Por llegar</th>
            <th scope="col">Estado</th>
            <th scope="col">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($materias as $materia) : ?>
            <?php
            $claseFila = match ($materia['estado']) {
                GestorInventario::SIN_EXISTENCIAS => 'fila-peligro',
                GestorInventario::BAJO_MINIMO => 'fila-alerta',
                default => '',
            };
              $urlMovimiento = 'modules/inventario/movimiento.php?id=' . $materia['id'];
              ?>
            <tr class="<?= $claseFila ?>">
              <td>
                <?= escapar($materia['nombre']) ?>
                <div class="small text-body-secondary">
                  <?= $materia['total_recetas'] > 0
                        ? 'Se usa en ' . escapar(pluralizar($materia['total_recetas'], 'receta', 'recetas'))
                        : 'No se usa en recetas' ?>
                </div>
              </td>
              <td class="numero">
                <strong><?= escapar(formatearCantidadConUnidad($materia['cantidad_disponible'], $materia['unidad_medida'])) ?></strong>
              </td>
              <td class="numero text-body-secondary">
                <?= escapar(formatearCantidadConUnidad($materia['stock_minimo'], $materia['unidad_medida'])) ?>
              </td>
              <td>
                <?php if ($materia['cantidad_por_llegar'] > 0) : ?>
                  <?php $diasLlegada = calcularDiferenciaDias(obtenerFechaHoy(), $materia['proxima_llegada']); ?>
                  <div><?= escapar(formatearCantidadConUnidad($materia['cantidad_por_llegar'], $materia['unidad_medida'])) ?></div>
                  <div class="small <?= $diasLlegada < 0 ? 'text-danger fw-semibold' : 'text-body-secondary' ?>">
                    <?= $diasLlegada < 0
                          ? 'Atrasado desde ' . escapar(mb_strtolower(describirDia($materia['proxima_llegada'])))
                          : 'Llega ' . escapar(mb_strtolower(describirDia($materia['proxima_llegada']))) ?>
                  </div>
                <?php else : ?>
                  <span class="text-body-secondary">-</span>
                <?php endif; ?>
              </td>
              <td><?= generarInsigniaExistencia($materia['estado']) ?></td>
              <td class="text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="<?= escapar(url($urlMovimiento . '&tipo=ENTRADA')) ?>"><i class="bi bi-box-arrow-in-down" aria-hidden="true"></i> Entrada</a>
                <a class="btn btn-sm btn-outline-primary" href="<?= escapar(url($urlMovimiento . '&tipo=SALIDA')) ?>"><i class="bi bi-box-arrow-up" aria-hidden="true"></i> Salida</a>
                <details class="mas-acciones">
                  <summary>Más</summary>
                  <div>
                    <a href="<?= escapar(url('modules/inventario/materia-prima.php?id=' . $materia['id'])) ?>">Editar</a>
                    <a href="<?= escapar(url('modules/inventario/movimientos.php?materia_prima_id=' . $materia['id'])) ?>">Movimientos</a>
                    <a href="<?= escapar(url('modules/inventario/pedido.php?materia_prima_id=' . $materia['id'])) ?>">Pedir</a>
                    <form method="post" action="<?= escapar(url('modules/inventario/eliminar-materia-prima.php')) ?>"
                      data-confirmar="¿Eliminar <?= escapar($materia['nombre']) ?>? Solo se puede si no tiene recetas ni historial."
                      data-confirmar-boton="Eliminar">
                      <?= generarCampoCsrf() ?>
                      <input type="hidden" name="id" value="<?= $materia['id'] ?>">
                      <button class="btn btn-link btn-sm p-0 text-danger" type="submit">Eliminar</button>
                    </form>
                  </div>
                </details>
              </td>
            </tr>
          <?php endforeach; ?>
          <tr data-sin-resultados <?= $materias === [] ? '' : 'hidden' ?>>
            <td colspan="6" class="estado-vacio">
              <?= $filtroEstado === 'ALERTA'
                    ? 'Todo está por encima del mínimo. No hay nada por reabastecer.'
                    : 'Ninguna materia prima coincide con la búsqueda.' ?>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
