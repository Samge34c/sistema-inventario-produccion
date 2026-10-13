<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/proteccion-formulario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!validarTokenMateriaPrima($_POST['token'] ?? null)) {
    http_response_code(403);
    echo 'Solicitud rechazada: código de seguridad del formulario no válido.';
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/funciones.php';

$datos = [
    'nombre' => trim((string) ($_POST['nombre'] ?? '')),
    'unidad_medida' => trim((string) ($_POST['unidad_medida'] ?? '')),
    'cantidad_disponible' => (string) ($_POST['cantidad_disponible'] ?? ''),
    'stock_minimo' => (string) ($_POST['stock_minimo'] ?? ''),
];

$errores = validarMateriaPrima($datos);

if ($errores !== []) {
    $_SESSION['errores_materia_prima'] = $errores;
    $_SESSION['datos_materia_prima'] = $datos;
    header('Location: index.php');
    exit;
}

$sentencia = $conexion->prepare(
    'INSERT INTO materias_primas (nombre, unidad_medida, cantidad_disponible, stock_minimo)
     VALUES (:nombre, :unidad_medida, :cantidad_disponible, :stock_minimo)'
);
$sentencia->execute([
    'nombre' => $datos['nombre'],
    'unidad_medida' => $datos['unidad_medida'],
    'cantidad_disponible' => (float) $datos['cantidad_disponible'],
    'stock_minimo' => (float) $datos['stock_minimo'],
]);

header('Location: index.php?estado=creada');
exit;
