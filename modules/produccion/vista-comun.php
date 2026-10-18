<?php

declare(strict_types=1);

function escaparProduccion(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function mostrarInicioProduccion(string $titulo): void
{
    ?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= escaparProduccion($titulo) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-4">
<a href="../../index.php">Inicio</a><h1 class="h3 mt-3"><?= escaparProduccion($titulo) ?></h1>
<nav class="d-flex flex-wrap gap-3 mb-4" aria-label="Consultas de producción">
<a href="index.php">Producción máxima</a><a href="recetas-posibles.php">Recetas posibles</a>
<a href="proyeccion.php">Proyección con pendientes</a></nav>
    <?php
}

function mostrarSelectorReceta(array $recetas, mixed $recetaId, string $boton): void
{
    if ($recetas === []) {
        echo '<p role="status">No hay recetas registradas. <a href="../recetas/index.php">Registrar una receta</a>.</p>';
        return;
    }
    ?>
<form method="get" class="row g-2 align-items-end mb-4">
<div class="col-sm-8"><label class="form-label" for="receta_id">Receta</label>
<select class="form-select" id="receta_id" name="receta_id" required><option value="">Seleccione</option>
    <?php foreach ($recetas as $receta): ?>
<option value="<?= (int) $receta['id'] ?>" <?= (int) $recetaId === (int) $receta['id'] ? 'selected' : '' ?>><?= escaparProduccion($receta['nombre']) ?></option>
    <?php endforeach; ?>
</select></div><div class="col-sm-4"><button class="btn btn-primary" type="submit"><?= escaparProduccion($boton) ?></button></div></form>
    <?php
}
