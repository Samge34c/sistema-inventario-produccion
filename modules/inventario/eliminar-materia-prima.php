<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

// Elimina una materia prima sin historial (solo por POST).

if (!esPeticionPost()) {
    redirigir('modules/inventario/index.php');
}

verificarTokenCsrf('modules/inventario/index.php');
$inventario = new GestorInventario($conexion);
$id = (int) ($_POST['id'] ?? 0);
$materia = $inventario->obtenerMateriaPrima($id);

try {
    $inventario->eliminarMateriaPrima($id);
    agregarMensaje('exito', sprintf('Se eliminó "%s".', $materia['nombre'] ?? 'la materia prima'));
} catch (ErrorValidacion $error) {
    agregarMensaje('error', $error->getMessage());
}

redirigir('modules/inventario/index.php');
