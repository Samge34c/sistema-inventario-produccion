<?php

declare(strict_types=1);

/** @var string $tituloPagina */
/** @var string $seccionActiva */
$seccionesMenu = [
    'inicio' => ['Inicio', 'index.php'],
    'inventario' => ['Inventario', 'modules/inventario/index.php'],
    'recetas' => ['Recetas', 'modules/recetas/index.php'],
    'produccion' => ['Producción', 'modules/produccion/index.php'],
    'ventas' => ['Caja', 'modules/ventas/index.php'],
    'reportes' => ['Reportes', 'modules/ventas/reportes.php'],
];

$iconosMenu = [
    'inicio' => 'bi-house-door',
    'inventario' => 'bi-box-seam',
    'recetas' => 'bi-journal-text',
    'produccion' => 'bi-fire',
    'ventas' => 'bi-cart3',
    'reportes' => 'bi-bar-chart-line',
];
$clasesMensaje = ['exito' => 'success', 'error' => 'danger', 'aviso' => 'warning', 'info' => 'info'];
$nombreNegocio = (string) obtenerConfiguracion('nombre_negocio');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= escapar($tituloPagina) ?> - <?= escapar($nombreNegocio) ?></title>
  <link rel="stylesheet" href="<?= escapar(url('assets/lib/bootstrap/bootstrap.min.css')) ?>">
  <link rel="stylesheet" href="<?= escapar(url('assets/lib/bootstrap-icons/bootstrap-icons.min.css')) ?>">
  <link rel="stylesheet" href="<?= escapar(url('assets/css/estilos.css')) ?>">
</head>
<body>
  <a class="visually-hidden-focusable saltar-contenido" href="#contenido">Saltar al contenido</a>

  <header class="cabecera">
    <div class="container-xl">
      <a class="marca" href="<?= escapar(url('index.php')) ?>">
        <span class="marca-logo"><img src="<?= escapar(url('assets/img/logo.svg')) ?>" alt="" width="34" height="34"></span>
        <span>
          <span class="marca-nombre"><?= escapar($nombreNegocio) ?></span>
          <span class="marca-lema">Inventario, recetas y producción</span>
        </span>
      </a>
      <nav class="menu" aria-label="Menú principal">
        <?php foreach ($seccionesMenu as $clave => [$texto, $ruta]) : ?>
          <a class="<?= $clave === $seccionActiva ? 'activo' : '' ?>"
            <?= $clave === $seccionActiva ? 'aria-current="page"' : '' ?>
            href="<?= escapar(url($ruta)) ?>"><i class="bi <?= $iconosMenu[$clave] ?>" aria-hidden="true"></i><?= escapar($texto) ?></a>
        <?php endforeach; ?>
      </nav>
    </div>
  </header>

  <main id="contenido" class="container-xl py-3">
    <?php foreach (obtenerMensajes() as $mensaje) : ?>
      <div class="alert alert-<?= $clasesMensaje[$mensaje['tipo']] ?? 'info' ?> alert-dismissible fade show" role="alert">
        <?= escapar($mensaje['texto']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
      </div>
    <?php endforeach; ?>
