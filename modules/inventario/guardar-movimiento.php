<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/movimientos-funciones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Use el formulario para registrar un movimiento.');
}
if (!validarTokenMovimiento($_POST['token'] ?? null)) {
    http_response_code(403);
    exit('El formulario ha vencido. Vuelva a abrir Movimientos.');
}

$datos = [];
foreach (['materia_prima_id', 'tipo', 'cantidad', 'observacion'] as $campo) {
    // Rechazar estructuras inesperadas sin warnings ni coerciones a Array.
    $datos[$campo] = is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : null;
}
$errores = validarMovimiento($datos);
if ($errores === []) {
    require_once __DIR__ . '/../../config/database.php';
    try {
        registrarMovimiento($conexion, $datos);
        header('Location: movimientos.php?estado=registrado', true, 303);
        exit;
    } catch (InvalidArgumentException | DomainException $error) {
        $errores[] = $error->getMessage();
    } catch (Throwable $error) {
        error_log('No se pudo registrar el movimiento: ' . $error->getMessage());
        $errores[] = 'No se pudo guardar. No se modificó el inventario. Revise la conexión y la migración HU02.';
    }
}

$_SESSION['errores_movimiento'] = $errores;
$_SESSION['datos_movimiento'] = $datos;
header('Location: movimientos.php', true, 303);
exit;
