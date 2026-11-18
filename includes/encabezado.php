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
      <a class="cabecera-nombre" href="<?= escapar(url('index.php')) ?>"><?= escapar($nombreNegocio) ?></a>
      <span class="cabecera-lema">Inventario, recetas y producción</span>
    </div>
  </header>
  <nav class="menu" aria-label="Menú principal">
    <div class="container-xl">
      <?php foreach ($seccionesMenu as $clave => [$texto, $ruta]) : ?>
        <a class="<?= $clave === $seccionActiva ? 'activo' : '' ?>"
          <?= $clave === $seccionActiva ? 'aria-current="page"' : '' ?>
          href="<?= escapar(url($ruta)) ?>"><?= escapar($texto) ?></a>
      <?php endforeach; ?>
    </div>
  </nav>

  <main id="contenido" class="container-xl py-3">
    <?php foreach (obtenerMensajes() as $mensaje) : ?>
      <div class="alert alert-<?= $clasesMensaje[$mensaje['tipo']] ?? 'info' ?> alert-dismissible fade show" role="alert">
        <?= escapar($mensaje['texto']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
      </div>
    <?php endforeach; ?>
