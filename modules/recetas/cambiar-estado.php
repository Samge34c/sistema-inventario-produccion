<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

// Archiva o reactiva una receta (solo por POST).

if (!esPeticionPost()) {
    redirigir('modules/recetas/index.php');
}

verificarTokenCsrf('modules/recetas/index.php');
$id = (int) ($_POST['id'] ?? 0);
$estaActiva = ($_POST['activa'] ?? '') === '1';
(new GestorRecetas($conexion))->cambiarEstadoReceta($id, $estaActiva);

agregarMensaje('exito', $estaActiva
    ? 'Receta reactivada. Vuelve a aparecer en la caja y en la planificación.'
    : 'Receta archivada. Ya no aparece en la caja ni en la planificación; su historial se conserva.');
redirigir('modules/recetas/detalle.php?id=' . $id);
