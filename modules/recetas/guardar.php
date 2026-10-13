<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/acceso.php';
exigirAcceso();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Utilice el formulario para registrar una receta.');
}
exigirTokenAcceso();
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/funciones.php';
$filas = $_POST['ingredientes'] ?? null;
$ingredientes = [];
if (is_array($filas)) {
    foreach ($filas as $fila) {
        if (is_array($fila) && ($fila['materia_prima_id'] ?? '') === '' && ($fila['cantidad'] ?? '') === '') {
            continue;
        }
        $ingredientes[] = $fila;
    }
}
$datos = ['nombre' => $_POST['nombre'] ?? null, 'descripcion' => $_POST['descripcion'] ?? '', 'ingredientes' => $ingredientes];
try {
    $recetaId = registrarReceta($conexion, $datos);
    header('Location: consultar.php?id=' . $recetaId, true, 303);
    exit;
} catch (InvalidArgumentException | DomainException $error) {
    $_SESSION['errores_receta'] = [$error->getMessage()];
    $_SESSION['datos_receta'] = $datos;
} catch (Throwable $error) {
    error_log('No se pudo guardar la receta: ' . $error->getMessage());
    $_SESSION['errores_receta'] = ['No se pudo guardar la receta. No se guardaron ingredientes parcialmente.'];
    $_SESSION['datos_receta'] = $datos;
}
header('Location: index.php', true, 303);
exit;
