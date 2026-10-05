<?php

declare(strict_types=1);

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
$sentencia->execute(['id' => $id]);

header('Location: index.php?estado=eliminada');
exit;
