<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

// Cancela un pedido pendiente (solo por POST).

if (!esPeticionPost()) {
    redirigir('modules/inventario/pedidos.php');
}

verificarTokenCsrf('modules/inventario/pedidos.php');
$gestorPedidos = new GestorPedidos($conexion);

try {
    $gestorPedidos->cancelarPedido((int) ($_POST['id'] ?? 0));
    agregarMensaje('exito', 'Pedido cancelado. Ya no se cuenta en la proyección ni en la planificación.');
} catch (ErrorValidacion $error) {
    agregarMensaje('error', $error->getMessage());
}

redirigir('modules/inventario/pedidos.php');
