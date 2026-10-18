<?php

declare(strict_types=1);

require_once __DIR__ . '/../modules/produccion/calculo-funciones.php';
require_once __DIR__ . '/../modules/recetas/funciones.php';

$conexion = new PDO(getenv('INVENTARIO_DSN'), getenv('INVENTARIO_USUARIO'), getenv('INVENTARIO_CLAVE'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$nombreBase = $conexion->query('SELECT DATABASE()')->fetchColumn();
if (!is_string($nombreBase) || !preg_match('/^prueba_recuperacion_[a-f0-9]{24}$/D', $nombreBase)) {
    throw new RuntimeException('Utilice únicamente la base temporal del preparador.');
}
$casos = 0;
function comprobarProduccion(bool $esCorrecto, string $nombre): void
{
    global $casos;
    if (!$esCorrecto) {
        throw new RuntimeException('FALLO PRODUCCION: ' . $nombre);
    }
    echo 'OK PRODUCCION ' . ++$casos . ': ' . $nombre . PHP_EOL;
}
function crearMateriaCalculo(PDO $conexion, string $nombre, string $cantidadUnidad): int
{
    $consulta = $conexion->prepare('INSERT INTO materias_primas (nombre,unidad_medida,cantidad_disponible) VALUES (?, ?, ?)');
    $consulta->execute([$nombre, 'g', $cantidadUnidad]);
    return (int) $conexion->lastInsertId();
}
function guardarRecetaCalculo(PDO $conexion, string $nombre, array $ingredientes): int
{
    return registrarReceta($conexion, ['nombre' => $nombre, 'descripcion' => '', 'ingredientes' => $ingredientes]);
}
function esperarRechazoCalculo(callable $operacion, string $nombre): void
{
    $esRechazado = false;
    try {
        $operacion();
    } catch (DomainException $error) {
        $esRechazado = true;
    }
    comprobarProduccion($esRechazado, $nombre);
}
function obtenerHuellaInventario(PDO $conexion): string
{
    $datos = [];
    foreach (['materias_primas', 'movimientos_inventario', 'materiales_pendientes', 'recetas', 'receta_ingredientes'] as $tabla) {
        $datos[$tabla] = $conexion->query('SELECT * FROM ' . $tabla . ' ORDER BY id')->fetchAll();
    }
    return hash('sha256', json_encode($datos, JSON_THROW_ON_ERROR));
}

$harina = crearMateriaCalculo($conexion, 'Harina cálculo', '5000');
$azucar = crearMateriaCalculo($conexion, 'Azúcar cálculo', '1000');
$receta = guardarRecetaCalculo($conexion, 'Pan de prueba', [
    ['materia_prima_id' => $harina, 'cantidad' => '500'], ['materia_prima_id' => $azucar, 'cantidad' => '200'],
]);
$resultado = obtenerProduccionMaxima($conexion, $receta);
comprobarProduccion($resultado['maximo_unidades'] === 5, 'HU04: 5000/500 y 1000/200 permiten cinco unidades');
comprobarProduccion(array_column($resultado['limitantes'], 'materia_prima_id') === [$azucar], 'HU04: Azúcar es el ingrediente limitante');
$consulta = $conexion->prepare('UPDATE materias_primas SET cantidad_disponible=? WHERE id=?');
$consulta->execute(['1100', $azucar]);
comprobarProduccion(obtenerProduccionMaxima($conexion, $receta)['maximo_unidades'] === 5, 'HU04: redondeo hacia abajo de 5,5 unidades');
$consulta->execute(['2000', $azucar]);
comprobarProduccion(count(obtenerProduccionMaxima($conexion, $receta)['limitantes']) === 2, 'HU04: se muestran ambos ingredientes en un empate');
$consulta->execute(['0', $azucar]);
comprobarProduccion(obtenerProduccionMaxima($conexion, $receta)['maximo_unidades'] === 0, 'HU04: un ingrediente sin existencia impide producir');
$consulta->execute(['1000', $azucar]);
$pendiente = $conexion->prepare('INSERT INTO materiales_pendientes (materia_prima_id,cantidad) VALUES (?,?)');
$pendiente->execute([$azucar, '9000']);
comprobarProduccion(obtenerProduccionMaxima($conexion, $receta)['maximo_unidades'] === 5, 'HU04: los pendientes no aumentan producción actual');
$decimal = crearMateriaCalculo($conexion, 'Ingrediente decimal', '0.30');
$recetaDecimal = guardarRecetaCalculo($conexion, 'Receta decimal', [['materia_prima_id' => $decimal, 'cantidad' => '0.10']]);
comprobarProduccion(obtenerProduccionMaxima($conexion, $recetaDecimal)['maximo_unidades'] === 3, 'HU04: 0,30/0,10 produce tres unidades exactas');
esperarRechazoCalculo(fn () => obtenerProduccionMaxima($conexion, 2147483647), 'HU04: receta inexistente rechazada');
comprobarProduccion(!$conexion->inTransaction(), 'HU04: error deja cerrada la transacción');
$conexion->exec("INSERT INTO recetas (nombre,cantidad_producir) VALUES ('Sin ingredientes',1)");
$vacia = (int) $conexion->lastInsertId();
esperarRechazoCalculo(fn () => obtenerProduccionMaxima($conexion, $vacia), 'HU04: receta vacía no da producción ilimitada');
$conexion->exec("INSERT INTO recetas (nombre,cantidad_producir) VALUES ('Lote antiguo',2)");
$lote = (int) $conexion->lastInsertId();
esperarRechazoCalculo(fn () => obtenerProduccionMaxima($conexion, $lote), 'HU04: receta antigua sin cantidades por unidad requiere revisión');
esperarRechazoCalculo(fn () => calcularProduccionMaxima([['cantidad' => '0', 'cantidad_disponible' => '10']]), 'HU04: denominador cero rechazado');
esperarRechazoCalculo(fn () => calcularProduccionMaxima([['cantidad' => '1', 'cantidad_disponible' => '-1']]), 'HU04: existencia negativa rechazada');

$materiaPosible = crearMateriaCalculo($conexion, 'Harina viabilidad', '1000');
$posible = guardarRecetaCalculo($conexion, 'Receta A', [['materia_prima_id' => $materiaPosible, 'cantidad' => '500']]);
$imposible = guardarRecetaCalculo($conexion, 'Receta B', [['materia_prima_id' => $materiaPosible, 'cantidad' => '1200']]);
$lista = array_column(obtenerRecetasPosibles($conexion), null, 'id');
comprobarProduccion($lista[$posible]['es_posible'] && $lista[$posible]['maximo_unidades'] === 2, 'HU05: Receta A posible con dos unidades');
comprobarProduccion(!$lista[$imposible]['es_posible'] && $lista[$imposible]['maximo_unidades'] === 0, 'HU05: Receta B distinguida como no posible');
comprobarProduccion($lista[$vacia]['error'] !== '' && $lista[$vacia]['maximo_unidades'] === null, 'HU05: receta vacía se señala para revisión');
comprobarProduccion($lista[$lote]['error'] !== '' && $lista[$posible]['error'] === '', 'HU05: una receta antigua no bloquea la lista válida');
$consulta->execute(['500', $materiaPosible]);
$lista = array_column(obtenerRecetasPosibles($conexion), null, 'id');
comprobarProduccion($lista[$posible]['es_posible'] && $lista[$posible]['maximo_unidades'] === 1, 'HU05: inventario exacto para una unidad es posible');
$consulta->execute(['0', $materiaPosible]);
$lista = array_column(obtenerRecetasPosibles($conexion), null, 'id');
comprobarProduccion(!$lista[$posible]['es_posible'], 'HU05: inventario cero se distingue como no posible');
$consulta->execute(['1000', $materiaPosible]);

$materiaProyeccion = crearMateriaCalculo($conexion, 'Harina proyección', '1000');
$recetaProyeccion = guardarRecetaCalculo($conexion, 'Receta proyección', [['materia_prima_id' => $materiaProyeccion, 'cantidad' => '500']]);
$resultado = obtenerProyeccionProduccion($conexion, $recetaProyeccion);
comprobarProduccion($resultado['actual']['maximo_unidades'] === 2 && $resultado['proyeccion']['maximo_unidades'] === 2, 'HU06: sin pendientes ambos resultados son iguales');
$pendiente->execute([$materiaProyeccion, '200']);
$pendiente->execute([$materiaProyeccion, '300']);
$resultado = obtenerProyeccionProduccion($conexion, $recetaProyeccion);
comprobarProduccion($resultado['actual']['maximo_unidades'] === 2 && $resultado['proyeccion']['maximo_unidades'] === 3, 'HU06: actual dos y proyección tres con 500 pendientes');
comprobarProduccion($resultado['ingredientes'][0]['cantidad_pendiente'] === '500.00', 'HU06: suma varias partidas pendientes de la misma materia');
$pendiente->execute([$decimal, '999']);
comprobarProduccion(obtenerProyeccionProduccion($conexion, $recetaProyeccion)['proyeccion']['maximo_unidades'] === 3, 'HU06: pendientes de otra materia no se mezclan');
$resultado = obtenerProyeccionProduccion($conexion, $receta);
comprobarProduccion($resultado['actual']['maximo_unidades'] === 5 && $resultado['proyeccion']['maximo_unidades'] === 10
    && array_column($resultado['proyeccion']['limitantes'], 'materia_prima_id') === [$harina], 'HU06: al llegar azúcar el limitante cambia a Harina');
$pendiente->execute([$materiaProyeccion, '99999999.99']);
$pendiente->execute([$materiaProyeccion, '99999999.99']);
comprobarProduccion(obtenerProyeccionProduccion($conexion, $recetaProyeccion)['proyeccion']['maximo_unidades'] === 400002, 'HU06: suma mayor que DECIMAL individual sin truncar ni usar flotantes');
$conexion->prepare('DELETE FROM materiales_pendientes WHERE materia_prima_id=? AND cantidad=?')->execute([$materiaProyeccion, '99999999.99']);
comprobarProduccion(convertirExistenciaCalculo('92233720368547758.08') === null, 'HU06: suma fuera del entero de 64 bits rechazada');
comprobarProduccion(convertirExistenciaCalculo('1e3') === null, 'HU06: notación científica rechazada');

$huellaAntes = obtenerHuellaInventario($conexion);
obtenerProduccionMaxima($conexion, $receta);
obtenerRecetasPosibles($conexion);
obtenerProyeccionProduccion($conexion, $recetaProyeccion);
obtenerProyeccionProduccion($conexion, $recetaProyeccion);
comprobarProduccion($huellaAntes === obtenerHuellaInventario($conexion), 'HU04-HU06: consultar y repetir no modifica saldo, movimientos, pendientes ni recetas');
echo 'DATOS_WEB: ' . json_encode(['receta' => $receta, 'posible' => $posible, 'imposible' => $imposible,
    'proyeccion' => $recetaProyeccion, 'vacia' => $vacia, 'lote' => $lote], JSON_THROW_ON_ERROR) . PHP_EOL;
echo 'RESULTADO PRODUCCION: ' . $casos . ' comprobaciones correctas.' . PHP_EOL;
