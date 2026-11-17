<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

// Cancela un plan pendiente (solo por POST).

if (!esPeticionPost()) {
    redirigir('modules/produccion/index.php');
}

verificarTokenCsrf('modules/produccion/index.php');
$id = (int) ($_POST['id'] ?? 0);

try {
    (new GestorProduccion($conexion))->cancelarProduccion($id, $_POST);
    agregarMensaje('exito', 'Plan cancelado. Sus materiales ya no están comprometidos.');
} catch (ErrorValidacion $error) {
    agregarMensaje('error', $error->getMessage());
}

redirigir('modules/produccion/detalle.php?id=' . $id);
