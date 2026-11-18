<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/inicio.php';

$conexion = obtenerConexion();

if (!esPeticionPost()) {
    redirigir('modules/ventas/index.php');
}

verificarTokenCsrf('modules/ventas/index.php');
$id = (int) ($_POST['id'] ?? 0);
$regreso = (string) ($_POST['regreso'] ?? 'caja');

try {
    (new GestorVentas($conexion))->anularVenta($id);
    agregarMensaje('exito', sprintf('Venta #%d anulada.', $id));
} catch (ErrorValidacion $error) {
    agregarMensaje('error', $error->getMessage());
}

redirigir(esFechaValida($regreso) ? 'modules/ventas/historial.php?fecha=' . $regreso : 'modules/ventas/index.php');
