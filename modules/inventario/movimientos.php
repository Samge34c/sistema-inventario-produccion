<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

// Historial de entradas y salidas con filtros.

$inventario = new GestorInventario($conexion);
$filtros = [
    'materia_prima_id' => leerParametroEntero('materia_prima_id'),
    'tipo' => leerParametro('tipo'),
    'desde' => leerParametroFecha('desde', ''),
    'hasta' => leerParametroFecha('hasta', ''),
];
$pagina = max(1, leerParametroEntero('pagina', 1));
$resultado = $inventario->listarMovimientos($filtros, $pagina);
$totalPaginas = max(1, (int) ceil($resultado['total'] / GestorInventario::MOVIMIENTOS_POR_PAGINA));
$materias = $inventario->listarOpcionesMateriasPrimas();
$hayFiltros = $filtros['materia_prima_id'] > 0 || $filtros['tipo'] !== '' || $filtros['desde'] !== '' || $filtros['hasta'] !== '';

$etiquetasOrigen = [
    'MANUAL' => ['Manual', 'bi-person'],
    'PEDIDO' => ['Pedido', 'bi-truck'],
    'PRODUCCION' => ['Producción', 'bi-fire'],
];

$construirUrlPagina = static function (int $numero) use ($filtros): string {
    return url('modules/inventario/movimientos.php?' . http_build_query(array_filter($filtros) + ['pagina' => $numero]));
};

$tituloPagina = 'Movimientos de inventario';
$seccionActiva = 'inventario';
require RAIZ_PROYECTO . '/includes/encabezado.php';
?>
<div class="encabezado-pagina">
  <div>
    <h1>Inventario</h1>
    <p>Cada entrada y salida queda registrada, con su origen: manual, pedido recibido o producción.</p>
  </div>
</div>

<?= generarPestanas(require __DIR__ . '/pestanas.php', 'movimientos') ?>

<form class="tarjeta p-3 mb-3" method="get">
  <div class="row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label small" for="materia_prima_id">Materia prima</label>
      <select class="form-select form-select-sm" id="materia_prima_id" name="materia_prima_id">
        <option value="">Todas</option>
        <?php foreach ($materias as $opcion) : ?>
          <option value="<?= $opcion['id'] ?>" <?= $filtros['materia_prima_id'] === $opcion['id'] ? 'selected' : '' ?>>
            <?= escapar($opcion['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small" for="tipo">Tipo</label>
      <select class="form-select form-select-sm" id="tipo" name="tipo">
        <option value="">Todos</option>
        <option value="ENTRADA" <?= $filtros['tipo'] === 'ENTRADA' ? 'selected' : '' ?>>Entradas</option>
        <option value="SALIDA" <?= $filtros['tipo'] === 'SALIDA' ? 'selected' : '' ?>>Salidas</option>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small" for="desde">Desde</label>
      <input type="date" class="form-control form-control-sm" id="desde" name="desde" value="<?= escapar($filtros['desde']) ?>">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small" for="hasta">Hasta</label>
      <input type="date" class="form-control form-control-sm" id="hasta" name="hasta" value="<?= escapar($filtros['hasta']) ?>">
    </div>
    <div class="col-6 col-md-2 d-flex gap-2">
      <button class="btn btn-sm btn-primary flex-grow-1" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i> Filtrar</button>
      <?php if ($hayFiltros) : ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= escapar(url('modules/inventario/movimientos.php')) ?>" title="Quitar filtros">
          <i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">Quitar filtros</span>
        </a>
      <?php endif; ?>
    </div>
  </div>
</form>

<div class="tarjeta">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th scope="col">Fecha</th>
          <th scope="col">Materia prima</th>
          <th scope="col" class="text-end">Cantidad</th>
          <th scope="col">Origen</th>
          <th scope="col">Observación</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($resultado['movimientos'] as $movimiento) : ?>
          <?php
          [$textoOrigen, $iconoOrigen] = $etiquetasOrigen[$movimiento['origen']] ?? $etiquetasOrigen['MANUAL'];
            $esEntrada = $movimiento['tipo'] === 'ENTRADA';
            ?>
          <tr>
            <td class="text-nowrap">
              <div><?= escapar(describirDia(substr($movimiento['fecha'], 0, 10))) ?></div>
              <div class="small text-body-secondary"><?= escapar(formatearHora($movimiento['fecha'])) ?></div>
            </td>
            <td><?= escapar($movimiento['materia_prima']) ?></td>
            <td class="numero fw-semibold <?= $esEntrada ? 'text-success' : 'text-danger' ?>">
              <?= $esEntrada ? '+' : '−' ?><?= escapar(formatearCantidadConUnidad($movimiento['cantidad'], $movimiento['unidad_medida'])) ?>
            </td>
            <td class="text-nowrap">
              <?php if ($movimiento['origen'] === 'PRODUCCION' && $movimiento['referencia_id'] !== null) : ?>
                <a href="<?= escapar(url('modules/produccion/detalle.php?id=' . $movimiento['referencia_id'])) ?>">
                  <i class="bi <?= $iconoOrigen ?>" aria-hidden="true"></i> <?= $textoOrigen ?> #<?= (int) $movimiento['referencia_id'] ?>
                </a>
              <?php else : ?>
                <i class="bi <?= $iconoOrigen ?> text-body-secondary" aria-hidden="true"></i> <?= $textoOrigen ?>
                <?= $movimiento['referencia_id'] !== null ? '#' . (int) $movimiento['referencia_id'] : '' ?>
              <?php endif; ?>
            </td>
            <td class="text-body-secondary"><?= escapar($movimiento['observacion'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($resultado['movimientos'] === []) : ?>
          <tr>
            <td colspan="5" class="estado-vacio">
              <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
              <?= $hayFiltros ? 'No hay movimientos con esos filtros.' : 'Todavía no hay movimientos registrados.' ?>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($totalPaginas > 1) : ?>
  <nav class="d-flex justify-content-between align-items-center mt-3" aria-label="Páginas de movimientos">
    <span class="small text-body-secondary">
      <?= escapar(pluralizar($resultado['total'], 'movimiento', 'movimientos')) ?> · página <?= $pagina ?> de <?= $totalPaginas ?>
    </span>
    <ul class="pagination pagination-sm mb-0">
      <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= escapar($construirUrlPagina($pagina - 1)) ?>">Anterior</a>
      </li>
      <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= escapar($construirUrlPagina($pagina + 1)) ?>">Siguiente</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<?php require RAIZ_PROYECTO . '/includes/pie.php'; ?>
