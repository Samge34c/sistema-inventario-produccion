<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

if (!esPeticionPost()) {
    redirigir('modules/inventario/pedidos.php');
}

verificarTokenCsrf('modules/inventario/pedidos.php');
$inventario = new GestorInventario($conexion);
$gestorPedidos = new GestorPedidos($conexion);
$id = (int) ($_POST['id'] ?? 0);

try {
    $gestorPedidos->recibirPedido($id, $_POST);
    $pedido = $gestorPedidos->obtenerPedido($id);
    $materia = $pedido === null ? null : $inventario->obtenerMateriaPrima((int) $pedido['materia_prima_id']);
    agregarMensaje('exito', $materia === null ? 'Pedido recibido.' : sprintf(
        'Pedido #%d recibido. %s ahora tiene %s.',
        $id,
        $materia['nombre'],
        formatearCantidadConUnidad($materia['cantidad_disponible'], $materia['unidad_medida'])
    ));
} catch (ErrorValidacion $error) {
    agregarMensaje('error', $error->getMessage());
}

redirigir('modules/inventario/pedidos.php');
