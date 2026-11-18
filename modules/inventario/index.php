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
      Registrar pedido
    </a>
    <a class="btn btn-primary" href="<?= escapar(url('modules/inventario/materia-prima.php')) ?>">
      Nueva materia prima
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
    <div class="input-group input-group-sm" style="max-width: 280px">
      <span class="input-group-text"></span>
      <input type="search" class="form-control" placeholder="Buscar materia prima" aria-label="Buscar materia prima"
        data-filtrar="#tablaMaterias">
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
            <th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th>
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
              $porcentajeBarra = $materia['stock_minimo'] > 0
                  ? min(100, $materia['cantidad_disponible'] / ($materia['stock_minimo'] * 2) * 100)
                  : 100;
              $claseBarra = match ($materia['estado']) {
                  GestorInventario::SIN_EXISTENCIAS => 'peligro',
                  GestorInventario::BAJO_MINIMO => 'aviso',
                  default => 'bien',
              };
              $urlMovimiento = 'modules/inventario/movimiento.php?id=' . $materia['id'];
              ?>
            <tr class="<?= $claseFila ?>">
              <td>
                <div class="fw-semibold"><?= escapar($materia['nombre']) ?></div>
                <div class="small text-body-secondary">
                  <?= $materia['total_recetas'] > 0
                        ? 'Se usa en ' . escapar(pluralizar($materia['total_recetas'], 'receta', 'recetas'))
                        : 'No se usa en recetas' ?>
                </div>
              </td>
              <td class="numero" style="min-width: 140px">
                <div class="fw-semibold"><?= escapar(formatearCantidadConUnidad($materia['cantidad_disponible'], $materia['unidad_medida'])) ?></div>
                <div class="barra-horizontal <?= $claseBarra ?> mt-1" aria-hidden="true">
                  <span style="width: <?= round($porcentajeBarra, 1) ?>%"></span>
                </div>
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
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-success" href="<?= escapar(url($urlMovimiento . '&tipo=ENTRADA')) ?>"
                  title="Registrar entrada de <?= escapar($materia['nombre']) ?>">
                  Entrada
                </a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= escapar(url($urlMovimiento . '&tipo=SALIDA')) ?>"
                  title="Registrar salida de <?= escapar($materia['nombre']) ?>">
                  Salida
                </a>
                <div class="dropdown d-inline-block">
                  <button class="btn btn-sm btn-link text-body-secondary" type="button" data-bs-toggle="dropdown"
                    data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false"
                    aria-label="Más acciones para <?= escapar($materia['nombre']) ?>">
                    <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="<?= escapar(url('modules/inventario/materia-prima.php?id=' . $materia['id'])) ?>">
                        Editar
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="<?= escapar(url('modules/inventario/movimientos.php?materia_prima_id=' . $materia['id'])) ?>">
                        Ver movimientos
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="<?= escapar(url('modules/inventario/pedido.php?materia_prima_id=' . $materia['id'])) ?>">
                        Registrar pedido
                      </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <form method="post" action="<?= escapar(url('modules/inventario/eliminar-materia-prima.php')) ?>"
                        data-confirmar="¿Eliminar <?= escapar($materia['nombre']) ?>? Solo se puede si no tiene recetas ni historial."
                        data-confirmar-boton="Eliminar">
                        <?= generarCampoCsrf() ?>
                        <input type="hidden" name="id" value="<?= $materia['id'] ?>">
                        <button class="dropdown-item text-danger" type="submit">
                          Eliminar
                        </button>
                      </form>
                    </li>
                  </ul>
                </div>
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
