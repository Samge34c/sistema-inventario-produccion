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

  <nav class="navbar navbar-expand-lg bg-dark" data-bs-theme="dark" aria-label="Menú principal">
    <div class="container-xl">
      <a class="navbar-brand" href="<?= escapar(url('index.php')) ?>"><?= escapar($nombreNegocio) ?></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal"
        aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir el menú">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="menuPrincipal">
        <ul class="navbar-nav ms-auto gap-lg-1">
          <?php foreach ($seccionesMenu as $clave => [$texto, $ruta]) : ?>
            <li class="nav-item">
              <a class="nav-link<?= $clave === $seccionActiva ? ' active' : '' ?>"
                <?= $clave === $seccionActiva ? 'aria-current="page"' : '' ?>
                href="<?= escapar(url($ruta)) ?>">
                <?= escapar($texto) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
        <?php if (isset($_SESSION['usuario_nombre'])) : ?>
          <div class="d-flex align-items-center gap-2 ms-lg-3 mt-2 mt-lg-0 sesion-usuario">
            <span class="small text-white-50"><?= escapar($_SESSION['usuario_nombre']) ?></span>
            <form method="post" action="<?= escapar(url('cerrar-sesion.php')) ?>">
              <?= generarCampoCsrf() ?>
              <button class="btn btn-sm btn-outline-light" type="submit">Cerrar sesión</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <main id="contenido" class="container-xl py-4">
    <?php foreach (obtenerMensajes() as $mensaje) : ?>
      <div class="alert alert-<?= $clasesMensaje[$mensaje['tipo']] ?? 'info' ?> alert-dismissible fade show" role="alert">
        <?= escapar($mensaje['texto']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
      </div>
    <?php endforeach; ?>
