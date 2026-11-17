<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

// Registra lo producido y descuenta los insumos (solo por POST).

if (!esPeticionPost()) {
    redirigir('modules/produccion/index.php');
}

verificarTokenCsrf('modules/produccion/index.php');
$gestorProduccion = new GestorProduccion($conexion);
$id = (int) ($_POST['id'] ?? 0);

try {
    $gestorProduccion->registrarProduccion($id, $_POST);
    $produccion = $gestorProduccion->obtenerProduccion($id);
    agregarMensaje('exito', sprintf(
        'Producción registrada: %s de %s. Los insumos ya se descontaron del inventario.',
        pluralizar((int) ($produccion['cantidad_producida'] ?? 0), 'unidad', 'unidades'),
        $produccion['receta'] ?? 'la receta'
    ));
} catch (ErrorValidacion $error) {
    agregarMensaje('error', $error->getMessage());
}

redirigir('modules/produccion/detalle.php?id=' . $id);
