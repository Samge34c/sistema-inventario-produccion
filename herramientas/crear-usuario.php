<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Esta herramienta solo se utiliza desde la terminal local.');
}
require __DIR__ . '/../config/database.php';
$correo = strtolower(trim($argv[1] ?? ''));
$nombre = trim($argv[2] ?? '');
$clave = getenv('INVENTARIO_CLAVE_INICIAL');
if (filter_var($correo, FILTER_VALIDATE_EMAIL) === false || mb_strlen($correo) > 190
    || $nombre === '' || mb_strlen($nombre) > 100 || !is_string($clave) || strlen($clave) < 12) {
    fwrite(STDERR, "Indique correo y nombre; configure INVENTARIO_CLAVE_INICIAL localmente con al menos 12 caracteres.\n");
    exit(1);
}
$consulta = $conexion->prepare('INSERT INTO usuarios (nombre, correo, clave_hash) VALUES (?, ?, ?)');
try {
    $consulta->execute([$nombre, $correo, password_hash($clave, PASSWORD_DEFAULT)]);
    echo "Cuenta creada; no se muestra ni se almacena la contraseña original.\n";
} catch (PDOException $error) {
    fwrite(STDERR, "No se pudo crear la cuenta. Compruebe la migración y que el correo no esté registrado.\n");
    exit(1);
}
