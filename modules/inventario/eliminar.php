<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    exit('Identificador de materia prima inválido.');
}

$sentencia = $conexion->prepare('DELETE FROM materias_primas WHERE id = :id');
try {
    $sentencia->execute(['id' => $id]);
} catch (PDOException $error) {
    error_log('No se pudo eliminar la materia prima: ' . $error->getMessage());
    $_SESSION['errores_materia_prima'] = ['No se pudo eliminar. Una materia prima con movimientos o materiales pendientes debe conservarse para mantener su historial.'];
    header('Location: index.php', true, 303);
    exit;
}

header('Location: index.php?estado=eliminada');
exit;
