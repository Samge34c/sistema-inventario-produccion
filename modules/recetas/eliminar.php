<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

// Elimina una receta sin historial (solo por POST).

if (!esPeticionPost()) {
    redirigir('modules/recetas/index.php');
}

verificarTokenCsrf('modules/recetas/index.php');
$id = (int) ($_POST['id'] ?? 0);

try {
    (new GestorRecetas($conexion))->eliminarReceta($id);
    agregarMensaje('exito', 'Receta eliminada.');
    redirigir('modules/recetas/index.php');
} catch (ErrorValidacion $error) {
    agregarMensaje('error', $error->getMessage());
    redirigir('modules/recetas/receta.php?id=' . $id);
}
