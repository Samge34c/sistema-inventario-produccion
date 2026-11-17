<?php

declare(strict_types=1);

/*
 * Tabla de productos más vendidos, compartida por los reportes diario y
 * semanal. Espera $reporte['por_producto'] de GestorVentas.
 */

/** @var array<string, mixed> $reporte */
?>
<div class="tarjeta h-100">
  <div class="card-header">Productos más vendidos</div>
  <table class="table align-middle">
    <thead>
      <tr>
        <th scope="col">Producto</th>
        <th scope="col" class="text-end">Unidades</th>
        <th scope="col" style="width: 35%"><span class="visually-hidden">Participación</span></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($reporte['por_producto'] as $indice => $producto) : ?>
        <tr>
          <td>
            <?= $indice === 0 ? '<i class="bi bi-star-fill text-warning" aria-label="Más vendido"></i> ' : '' ?>
            <?= escapar($producto['nombre']) ?>
          </td>
          <td class="numero fw-semibold"><?= escapar(formatearCantidad($producto['unidades'])) ?></td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="barra-horizontal flex-grow-1" aria-hidden="true"><span style="width: <?= round($producto['porcentaje'], 1) ?>%"></span></div>
              <span class="small text-body-secondary" style="min-width: 3rem"><?= escapar(formatearCantidad($producto['porcentaje'], 0)) ?> %</span>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
