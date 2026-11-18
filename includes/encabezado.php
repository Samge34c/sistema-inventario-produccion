<?php

declare(strict_types=1);

/** @var string $tituloPagina */
/** @var string $seccionActiva */
$seccionesMenu = [
    'inicio' => ['Inicio', 'bi-house-door', 'index.php'],
    'inventario' => ['Inventario', 'bi-box-seam', 'modules/inventario/index.php'],
    'recetas' => ['Recetas', 'bi-journal-text', 'modules/recetas/index.php'],
    'produccion' => ['Producción', 'bi-fire', 'modules/produccion/index.php'],
    'ventas' => ['Caja', 'bi-shop', 'modules/ventas/index.php'],
    'reportes' => ['Reportes', 'bi-bar-chart-line', 'modules/ventas/reportes.php'],
];

$clasesMensaje = [
    'exito' => ['success', 'bi-check-circle-fill'],
    'error' => ['danger', 'bi-exclamation-octagon-fill'],
    'aviso' => ['warning', 'bi-exclamation-triangle-fill'],
    'info' => ['info', 'bi-info-circle-fill'],
];
$nombreNegocio = (string) obtenerConfiguracion('nombre_negocio');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= escapar($tituloPagina) ?> · <?= escapar($nombreNegocio) ?></title>
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E%F0%9F%A5%96%3C/text%3E%3C/svg%3E">
  <link rel="stylesheet" href="<?= escapar(url('assets/lib/bootstrap/bootstrap.min.css')) ?>">
  <link rel="stylesheet" href="<?= escapar(url('assets/lib/bootstrap-icons/bootstrap-icons.min.css')) ?>">
  <link rel="stylesheet" href="<?= escapar(url('assets/css/estilos.css')) ?>">
</head>
<body>
  <a class="visually-hidden-focusable saltar-contenido" href="#contenido">Saltar al contenido</a>

  <nav class="navbar navbar-expand-lg barra-principal sticky-top" data-bs-theme="dark" aria-label="Menú principal">
    <div class="container-xl">
      <a class="navbar-brand marca" href="<?= escapar(url('index.php')) ?>">
        <span class="marca-icono" aria-hidden="true"><i class="bi bi-basket2-fill"></i></span>
        <?= escapar($nombreNegocio) ?>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal"
        aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir el menú">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="menuPrincipal">
        <ul class="navbar-nav ms-auto gap-lg-1">
          <?php foreach ($seccionesMenu as $clave => [$texto, $icono, $ruta]) : ?>
            <li class="nav-item">
              <a class="nav-link<?= $clave === $seccionActiva ? ' active' : '' ?>"
                <?= $clave === $seccionActiva ? 'aria-current="page"' : '' ?>
                href="<?= escapar(url($ruta)) ?>">
                <i class="bi <?= escapar($icono) ?>" aria-hidden="true"></i> <?= escapar($texto) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
        <?php if (isset($_SESSION['usuario_nombre'])) : ?>
          <div class="d-flex align-items-center gap-2 ms-lg-3 mt-2 mt-lg-0 sesion-usuario">
            <span class="small text-white-50"><i class="bi bi-person-circle" aria-hidden="true"></i> <?= escapar($_SESSION['usuario_nombre']) ?></span>
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
      <?php [$claseAlerta, $iconoAlerta] = $clasesMensaje[$mensaje['tipo']] ?? $clasesMensaje['info']; ?>
      <div class="alert alert-<?= $claseAlerta ?> alert-dismissible fade show d-flex gap-2 align-items-start" role="alert">
        <i class="bi <?= $iconoAlerta ?> flex-shrink-0 mt-1" aria-hidden="true"></i>
        <div><?= escapar($mensaje['texto']) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
      </div>
    <?php endforeach; ?>
