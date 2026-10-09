<?php

declare(strict_types=1);

require_once __DIR__ . '/../modules/inventario/movimientos-funciones.php';

function conectarPrueba(): PDO
{
    $dsn = getenv('HU02_PRUEBA_DSN');
    if ($dsn === false || $dsn === '') {
        throw new RuntimeException('Defina HU02_PRUEBA_DSN hacia un servidor de pruebas. El script crea bases temporales propias.');
    }

    return new PDO($dsn, getenv('HU02_PRUEBA_USUARIO') ?: 'root', getenv('HU02_PRUEBA_CLAVE') ?: '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

// Dos procesos reales comprueban que no se puede gastar dos veces el mismo saldo.
if (($argv[1] ?? '') === 'salida-paralela') {
    $nombreBase = $argv[2] ?? '';
    if (!preg_match('/^prueba_hu02_[a-f0-9]+$/D', $nombreBase)) {
        exit(2);
    }
    $conexion = conectarPrueba();
    $conexion->exec('USE ' . $nombreBase);
    try {
        registrarMovimiento($conexion, ['materia_prima_id' => $argv[3], 'tipo' => 'SALIDA', 'cantidad' => '80', 'observacion' => 'Prueba concurrente']);
        echo 'aceptada';
    } catch (DomainException $error) {
        echo 'rechazada';
    }
    exit;
}

$numeroPruebas = 0;
function comprobar(bool $esCorrecto, string $caso): void
{
    global $numeroPruebas;
    if (!$esCorrecto) {
        throw new RuntimeException('FALLO: ' . $caso);
    }
    $numeroPruebas++;
    echo 'OK ' . $numeroPruebas . ': ' . $caso . PHP_EOL;
}

function comprobarRechazo(callable $accion, string $caso): void
{
    $esRechazada = false;
    try {
        $accion();
    } catch (InvalidArgumentException | DomainException | PDOException $error) {
        $esRechazada = true;
    }
    comprobar($esRechazada, $caso);
}

$conexion = conectarPrueba();
$basesTemporales = [];
try {
    $nombreBase = 'prueba_hu02_' . bin2hex(random_bytes(6));
    $basesTemporales[] = $nombreBase;
    $sql = str_replace('sistema_inventario', $nombreBase, file_get_contents(__DIR__ . '/../database/schema.sql'));
    $conexion->exec($sql);
    comprobar((int) $conexion->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')->fetchColumn() === 5, 'schema nuevo crea las cinco tablas');
    comprobar($conexion->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'movimientos_inventario'")->fetchColumn() === 'InnoDB', 'movimientos usa InnoDB');
    $conexion->exec("INSERT INTO materias_primas (nombre, unidad_medida, cantidad_disponible) VALUES ('Harina de prueba', 'g', 10000)");
    $id = (string) $conexion->lastInsertId();
    $datos = ['materia_prima_id' => $id, 'tipo' => 'ENTRADA', 'cantidad' => '2000', 'observacion' => 'Caso Jira'];
    registrarMovimiento($conexion, $datos);
    comprobar($conexion->query('SELECT cantidad_disponible FROM materias_primas WHERE id = ' . (int) $id)->fetchColumn() === '12000.00', 'entrada 2000 deja 12000 g');
    registrarMovimiento($conexion, array_replace($datos, ['tipo' => 'SALIDA', 'cantidad' => '1000']));
    comprobar($conexion->query('SELECT cantidad_disponible FROM materias_primas WHERE id = ' . (int) $id)->fetchColumn() === '11000.00', 'salida 1000 deja 11000 g');
    registrarMovimiento($conexion, array_replace($datos, ['tipo' => 'PENDIENTE', 'cantidad' => '5000']));
    comprobar($conexion->query('SELECT cantidad_disponible FROM materias_primas WHERE id = ' . (int) $id)->fetchColumn() === '11000.00', 'pendiente 5000 conserva 11000 disponibles');
    comprobar($conexion->query('SELECT cantidad FROM materiales_pendientes')->fetchColumn() === '5000.00', 'pendiente queda en tabla separada');
    comprobar((int) $conexion->query('SELECT COUNT(*) FROM movimientos_inventario')->fetchColumn() === 2, 'pendiente no aparece como entrada/salida');
    comprobarRechazo(fn () => registrarMovimiento($conexion, array_replace($datos, ['tipo' => 'SALIDA', 'cantidad' => '11000.01'])), 'salida excesiva rechazada');
    comprobar($conexion->query('SELECT cantidad_disponible FROM materias_primas WHERE id = ' . (int) $id)->fetchColumn() === '11000.00', 'rechazo conserva saldo');
    foreach (['0', '-1', '1.001', '100000000', '1e2', 'NaN', 'INF', '', '1,5', ['1']] as $cantidadInvalida) {
        comprobarRechazo(fn () => registrarMovimiento($conexion, array_replace($datos, ['cantidad' => $cantidadInvalida])), 'cantidad inválida rechazada: ' . json_encode($cantidadInvalida));
    }
    comprobarRechazo(fn () => registrarMovimiento($conexion, array_replace($datos, ['tipo' => 'ENTRADA; DROP TABLE materias_primas'])), 'tipo no permitido rechazado');
    comprobarRechazo(fn () => registrarMovimiento($conexion, array_replace($datos, ['materia_prima_id' => '0'])), 'identificador cero rechazado');
    comprobarRechazo(fn () => registrarMovimiento($conexion, array_replace($datos, ['materia_prima_id' => '99999999'])), 'materia inexistente rechazada');
    comprobarRechazo(fn () => registrarMovimiento($conexion, array_replace($datos, ['observacion' => str_repeat('á', 256)])), 'observación mayor de 255 caracteres rechazada');
    comprobarRechazo(fn () => registrarMovimiento($conexion, array_replace($datos, ['observacion' => ['texto']])), 'observación con estructura inesperada rechazada');

    // Fallo de BD después del UPDATE: debe revertir saldo e historial.
    $conexion->exec("CREATE TRIGGER provocar_fallo BEFORE INSERT ON movimientos_inventario FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fallo de prueba'");
    comprobarRechazo(fn () => registrarMovimiento($conexion, $datos), 'fallo simulado de INSERT propagado');
    comprobar($conexion->query('SELECT cantidad_disponible FROM materias_primas WHERE id = ' . (int) $id)->fetchColumn() === '11000.00', 'rollback revierte el UPDATE');
    comprobar((int) $conexion->query('SELECT COUNT(*) FROM movimientos_inventario')->fetchColumn() === 2, 'rollback conserva dos movimientos');
    $conexion->exec('DROP TRIGGER provocar_fallo');

    comprobarRechazo(fn () => $conexion->exec('DELETE FROM materias_primas WHERE id = ' . (int) $id), 'FK impide borrar la materia y perder su historial');
    $datosEdicion = ['nombre' => 'Harina de prueba', 'unidad_medida' => 'g', 'cantidad_disponible' => '11000.00', 'stock_minimo' => '10.00'];
    comprobarRechazo(fn () => actualizarMateriaPrimaProtegida($conexion, (int) $id, array_replace($datosEdicion, ['cantidad_disponible' => '9000'])), 'edición directa de saldo con historial bloqueada');
    comprobarRechazo(fn () => actualizarMateriaPrimaProtegida($conexion, (int) $id, array_replace($datosEdicion, ['unidad_medida' => 'kg'])), 'cambio de unidad con historial bloqueado');
    actualizarMateriaPrimaProtegida($conexion, (int) $id, $datosEdicion);
    comprobar($conexion->query('SELECT stock_minimo FROM materias_primas WHERE id = ' . (int) $id)->fetchColumn() === '10.00', 'edición permitida de stock mínimo conserva saldo');
    registrarMovimiento($conexion, array_replace($datos, ['cantidad' => '0.01']));
    comprobar($conexion->query('SELECT cantidad_disponible FROM materias_primas WHERE id = ' . (int) $id)->fetchColumn() === '11000.01', 'centésima exacta sin error de flotantes');
    registrarMovimiento($conexion, array_replace($datos, ['tipo' => 'SALIDA', 'cantidad' => '11000.01']));
    comprobar($conexion->query('SELECT cantidad_disponible FROM materias_primas WHERE id = ' . (int) $id)->fetchColumn() === '0.00', 'salida exacta permite saldo cero');

    $conexion->exec("INSERT INTO materias_primas (nombre, unidad_medida, cantidad_disponible) VALUES ('Límite', 'g', 99999999.99)");
    $idLimite = (string) $conexion->lastInsertId();
    comprobarRechazo(fn () => registrarMovimiento($conexion, array_replace($datos, ['materia_prima_id' => $idLimite, 'cantidad' => '0.01'])), 'desbordamiento de saldo rechazado');

    $conexion->exec("INSERT INTO materias_primas (nombre, unidad_medida, cantidad_disponible) VALUES ('Concurrencia', 'g', 100)");
    $idConcurrente = (string) $conexion->lastInsertId();
    $procesos = [];
    for ($i = 0; $i < 2; $i++) {
        $tuberias = [];
        $proceso = proc_open([PHP_BINARY, __FILE__, 'salida-paralela', $nombreBase, $idConcurrente], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias);
        $procesos[] = [$proceso, $tuberias];
    }
    $resultados = [];
    foreach ($procesos as [$proceso, $tuberias]) {
        $resultados[] = stream_get_contents($tuberias[1]);
        $errorProceso = stream_get_contents($tuberias[2]);
        fclose($tuberias[1]);
        fclose($tuberias[2]);
        comprobar(proc_close($proceso) === 0 && $errorProceso === '', 'proceso concurrente completa sin error técnico');
    }
    sort($resultados);
    comprobar($resultados === ['aceptada', 'rechazada'], 'dos salidas de 80 sobre 100: una aceptada y otra rechazada');
    comprobar($conexion->query('SELECT cantidad_disponible FROM materias_primas WHERE id = ' . (int) $idConcurrente)->fetchColumn() === '20.00', 'saldo concurrente final 20');

    $nombreMigracion = 'prueba_hu02_' . bin2hex(random_bytes(6));
    $basesTemporales[] = $nombreMigracion;
    $rutaAnterior = getenv('HU02_SCHEMA_ANTERIOR');
    if ($rutaAnterior === false || !is_file($rutaAnterior)) {
        throw new RuntimeException('Defina HU02_SCHEMA_ANTERIOR con el SQL del develop anterior para probar migración.');
    }
    $conexion->exec(str_replace('sistema_inventario', $nombreMigracion, file_get_contents($rutaAnterior)));
    $conexion->exec("INSERT INTO materias_primas (nombre, unidad_medida, cantidad_disponible) VALUES ('Dato anterior', 'g', 25)");
    $conexion->exec("INSERT INTO movimientos_inventario (materia_prima_id, tipo, cantidad) VALUES (1, 'ENTRADA', 25)");
    $conexion->exec(str_replace('sistema_inventario', $nombreMigracion, file_get_contents(__DIR__ . '/../database/migraciones/002-movimientos-pendientes.sql')));
    comprobar($conexion->query('SELECT cantidad_disponible FROM materias_primas WHERE id = 1')->fetchColumn() === '25.00', 'migración conserva existencia anterior');
    comprobar((int) $conexion->query('SELECT COUNT(*) FROM movimientos_inventario')->fetchColumn() === 1, 'migración conserva historial anterior');
    registrarMovimiento($conexion, ['materia_prima_id' => '1', 'tipo' => 'PENDIENTE', 'cantidad' => '5', 'observacion' => 'Migración']);
    comprobar($conexion->query('SELECT cantidad_disponible FROM materias_primas WHERE id = 1')->fetchColumn() === '25.00', 'pendiente funciona después de migrar sin modificar saldo');
    comprobarRechazo(fn () => $conexion->exec('DELETE FROM materias_primas WHERE id = 1'), 'migración protege el historial de borrados');

    echo 'RESULTADO: ' . $numeroPruebas . ' comprobaciones correctas.' . PHP_EOL;
} finally {
    foreach ($basesTemporales as $baseTemporal) {
        // Solo nombres generados por este proceso; nunca una base suministrada por el usuario.
        $conexion->exec('DROP DATABASE IF EXISTS ' . $baseTemporal);
    }
}
