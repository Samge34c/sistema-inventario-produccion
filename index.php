<?php

declare(strict_types=1);

require_once __DIR__ . '/config/acceso.php';
exigirAcceso();
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Inventario y producción</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-4">
<h1>Sistema de inventario, recetas y producción</h1>
<p>Bienvenido, <?= htmlspecialchars($_SESSION['usuario_nombre'], ENT_QUOTES, 'UTF-8') ?>.</p>
<nav class="d-flex flex-wrap gap-3 mb-4" aria-label="Módulos">
<a class="btn btn-primary" href="modules/inventario/index.php">Materias primas</a>
<a class="btn btn-primary" href="modules/inventario/movimientos.php">Movimientos</a>
<a class="btn btn-primary" href="modules/recetas/index.php">Recetas</a>
<a class="btn btn-primary" href="modules/produccion/index.php">Producción máxima</a>
<a class="btn btn-primary" href="modules/produccion/recetas-posibles.php">Recetas posibles</a>
<a class="btn btn-primary" href="modules/produccion/proyeccion.php">Proyección con pendientes</a>
</nav>
<form action="cerrar-sesion.php" method="post"><input type="hidden" name="token" value="<?= obtenerTokenAcceso() ?>">
<button class="btn btn-outline-secondary" type="submit">Cerrar sesión</button></form>
</main></body></html>
