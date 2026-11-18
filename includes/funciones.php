<?php

declare(strict_types=1);

const DIAS_SEMANA_CORTOS = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
const DIAS_SEMANA_LARGOS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
const MESES_CORTOS = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
const MESES_LARGOS = [
    'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
    'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
];

function obtenerConfiguracion(string $clave): mixed
{
    return $GLOBALS['configuracion'][$clave] ?? null;
}

function obtenerConexion(): PDO
{
    static $conexion = null;

    if ($conexion instanceof PDO) {
        return $conexion;
    }

    try {
        $conexion = crearConexion($GLOBALS['configuracion']);
    } catch (PDOException $error) {
        error_log('Error de conexión: ' . $error->getMessage());
        mostrarPaginaError(
            'No hay conexión con la base de datos',
            'Revise que MySQL esté encendido (en XAMPP, botón Start de MySQL) y que la base '
                . '"' . obtenerConfiguracion('bd_nombre') . '" exista. Para crearla, importe '
                . 'database/schema.sql desde phpMyAdmin. Las instrucciones completas están en el README.',
            obtenerConfiguracion('mostrar_detalle_errores') ? $error->getMessage() : null
        );
    }

    return $conexion;
}

function url(string $ruta = ''): string
{
    static $urlBase = null;

    if ($urlBase === null) {
        $urlBase = calcularUrlBase();
    }

    return $urlBase . '/' . ltrim($ruta, '/');
}

function calcularUrlBase(): string
{
    $urlConfigurada = obtenerConfiguracion('url_base');

    if (is_string($urlConfigurada) && $urlConfigurada !== '') {
        return rtrim($urlConfigurada, '/');
    }

    $rutaScript = str_replace('\\', '/', (string) realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $rutaRaiz = str_replace('\\', '/', (string) realpath(RAIZ_PROYECTO));
    $nombreScript = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    if ($rutaRaiz === '' || !str_starts_with($rutaScript, $rutaRaiz)) {
        return '';
    }

    $rutaRelativa = substr($rutaScript, strlen($rutaRaiz));

    if ($rutaRelativa === '' || !str_ends_with($nombreScript, $rutaRelativa)) {
        return '';
    }

    return rtrim(substr($nombreScript, 0, -strlen($rutaRelativa)), '/');
}

function redirigir(string $ruta): never
{
    header('Location: ' . url($ruta));
    exit;
}

function esPeticionPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function leerParametro(string $clave, string $valorPorDefecto = ''): string
{
    $valor = $_GET[$clave] ?? $valorPorDefecto;

    return is_string($valor) ? trim($valor) : $valorPorDefecto;
}

function leerParametroEntero(string $clave, int $valorPorDefecto = 0): int
{
    $valor = filter_var($_GET[$clave] ?? null, FILTER_VALIDATE_INT);

    return $valor === false ? $valorPorDefecto : (int) $valor;
}

function leerParametroFecha(string $clave, string $valorPorDefecto): string
{
    $valor = leerParametro($clave);

    return esFechaValida($valor) ? $valor : $valorPorDefecto;
}

function escapar(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function generarCampoCsrf(): string
{
    return '<input type="hidden" name="token" value="' . escapar(obtenerTokenAcceso()) . '">';
}

function verificarTokenCsrf(string $rutaRegreso = 'index.php'): void
{
    if (validarTokenAcceso($_POST['token'] ?? null)) {
        return;
    }

    agregarMensaje('error', 'El formulario venció. Revise los datos y vuelva a enviarlo.');
    redirigir($rutaRegreso);
}

function agregarMensaje(string $tipo, string $texto): void
{
    $_SESSION['mensajes'][] = ['tipo' => $tipo, 'texto' => $texto];
}

/** @return list<array{tipo: string, texto: string}> */
function obtenerMensajes(): array
{
    $mensajes = $_SESSION['mensajes'] ?? [];
    unset($_SESSION['mensajes']);

    return $mensajes;
}

function formatearCantidad(float $valor, int $decimalesMaximos = 2): string
{
    $texto = number_format($valor, $decimalesMaximos, ',', '.');

    if (str_contains($texto, ',')) {
        $texto = rtrim(rtrim($texto, '0'), ',');
    }

    return $texto === '-0' ? '0' : $texto;
}

function formatearValorCampo(mixed $valor): string
{
    if (is_float($valor) || is_int($valor)) {
        $texto = number_format((float) $valor, 2, '.', '');

        return rtrim(rtrim($texto, '0'), '.');
    }

    return is_string($valor) ? $valor : '';
}

function obtenerNombreUnidad(string $unidad, float $cantidad = 2): string
{
    if ($unidad === 'unidad') {
        return abs($cantidad - 1) < 0.0001 ? 'unidad' : 'unidades';
    }

    return $unidad;
}

function formatearCantidadConUnidad(float $valor, string $unidad): string
{
    return formatearCantidad($valor) . ' ' . obtenerNombreUnidad($unidad, $valor);
}

function pluralizar(int $cantidad, string $singular, string $plural): string
{
    return formatearCantidad($cantidad) . ' ' . ($cantidad === 1 ? $singular : $plural);
}

function convertirTextoANumero(string $texto): ?float
{
    $texto = str_replace(' ', '', trim($texto));

    if (str_contains($texto, ',') && str_contains($texto, '.')) {
        $texto = str_replace('.', '', $texto);
    }

    $texto = str_replace(',', '.', $texto);

    return is_numeric($texto) ? (float) $texto : null;
}

function obtenerFechaHoy(): string
{
    return date('Y-m-d');
}

function sumarDias(string $fecha, int $dias): string
{
    return (new DateTimeImmutable($fecha))->modify(sprintf('%+d days', $dias))->format('Y-m-d');
}

function esFechaValida(string $fecha): bool
{
    $objetoFecha = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

    return $objetoFecha !== false && $objetoFecha->format('Y-m-d') === $fecha;
}

function calcularDiferenciaDias(string $desde, string $hasta): int
{
    $inicio = new DateTimeImmutable(substr($desde, 0, 10));
    $fin = new DateTimeImmutable(substr($hasta, 0, 10));

    return (int) $inicio->diff($fin)->format('%r%a');
}

function obtenerLunesDeSemana(string $fecha): string
{
    $objetoFecha = new DateTimeImmutable($fecha);
    $diaSemana = (int) $objetoFecha->format('N');

    return $objetoFecha->modify('-' . ($diaSemana - 1) . ' days')->format('Y-m-d');
}

function formatearFecha(string $fecha, bool $incluirAnio = false): string
{
    $objetoFecha = new DateTimeImmutable($fecha);
    $texto = DIAS_SEMANA_CORTOS[(int) $objetoFecha->format('w')] . ' '
        . $objetoFecha->format('j') . ' '
        . MESES_CORTOS[(int) $objetoFecha->format('n') - 1];

    return $incluirAnio ? $texto . ' ' . $objetoFecha->format('Y') : $texto;
}

function formatearFechaLarga(string $fecha): string
{
    $objetoFecha = new DateTimeImmutable($fecha);

    return DIAS_SEMANA_LARGOS[(int) $objetoFecha->format('w')] . ' '
        . $objetoFecha->format('j') . ' de '
        . MESES_LARGOS[(int) $objetoFecha->format('n') - 1] . ' de '
        . $objetoFecha->format('Y');
}

function formatearHora(string $fechaHora): string
{
    $objetoFecha = new DateTimeImmutable($fechaHora);

    return $objetoFecha->format('g:i') . ($objetoFecha->format('A') === 'AM' ? ' a. m.' : ' p. m.');
}

function formatearFranjaHoraria(int $hora): string
{
    $hora12 = $hora % 12 === 0 ? 12 : $hora % 12;

    return $hora12 . ($hora < 12 ? ' a. m.' : ' p. m.');
}

function formatearFechaHora(string $fechaHora): string
{
    return describirDia(substr($fechaHora, 0, 10)) . ', ' . formatearHora($fechaHora);
}

function describirDia(string $fecha): string
{
    return match (calcularDiferenciaDias(obtenerFechaHoy(), $fecha)) {
        0 => 'Hoy',
        1 => 'Mañana',
        -1 => 'Ayer',
        default => ucfirst(formatearFecha($fecha, substr($fecha, 0, 4) !== date('Y'))),
    };
}

/** @param array<string, string> $errores */
function generarClaseError(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? ' is-invalid' : '';
}

/** @param array<string, string> $errores */
function generarMensajeError(array $errores, string $campo): string
{
    if (!isset($errores[$campo])) {
        return '';
    }

    return '<div class="invalid-feedback">' . escapar($errores[$campo]) . '</div>';
}

/** @param array<string, array{0: string, 1: string, 2: string}> $pestanas */
function generarPestanas(array $pestanas, string $pestanaActiva): string
{
    $html = '<nav class="pestanas-modulo mb-4" aria-label="Secciones del módulo"><ul class="nav nav-underline">';

    foreach ($pestanas as $clave => [$texto, $icono, $ruta]) {
        $esActiva = $clave === $pestanaActiva;
        $html .= '<li class="nav-item"><a class="nav-link' . ($esActiva ? ' active' : '') . '"'
            . ($esActiva ? ' aria-current="page"' : '')
            . ' href="' . escapar(url($ruta)) . '">' . escapar($texto) . '</a></li>';
    }

    return $html . '</ul></nav>';
}

function generarInsigniaExistencia(string $estado): string
{
    return match ($estado) {
        'SIN_EXISTENCIAS' => '<span class="estado estado-mal">Sin existencias</span>',
        'BAJO_MINIMO' => '<span class="estado estado-aviso">Bajo el mínimo</span>',
        default => '<span class="estado estado-bien">Disponible</span>',
    };
}

function generarInsigniaAbastecimiento(string $estado): string
{
    return match ($estado) {
        CalculadoraProduccion::FALTAN_MATERIALES => '<span class="estado estado-mal">Faltan materiales</span>',
        CalculadoraProduccion::DEPENDE_DE_PEDIDOS => '<span class="estado estado-aviso">Depende de pedidos</span>',
        CalculadoraProduccion::SIN_INGREDIENTES => '<span class="estado">Receta sin ingredientes</span>',
        default => '<span class="estado estado-bien">Materiales completos</span>',
    };
}

function generarInsigniaProduccion(string $estado, bool $estaAtrasada = false): string
{
    if ($estado === 'PLANEADA' && $estaAtrasada) {
        return '<span class="estado estado-mal">Atrasada</span>';
    }

    return match ($estado) {
        'REALIZADA' => '<span class="estado estado-bien">Realizada</span>',
        'CANCELADA' => '<span class="estado">Cancelada</span>',
        default => '<span class="estado">Planeada</span>',
    };
}

/** @param array<string, mixed> $analisis */
function describirFaltantes(array $analisis, string $campo = 'faltante'): string
{
    $partes = [];

    foreach ($analisis['ingredientes'] as $fila) {
        if ($fila[$campo] > 0) {
            $partes[] = formatearCantidadConUnidad($fila[$campo], $fila['unidad_medida']) . ' de ' . mb_strtolower($fila['nombre']);
        }
    }

    return implode(', ', $partes);
}

/** @param list<array{etiqueta: string, valor: float, texto: string, titulo: string, esDestacada?: bool}> $columnas */
function generarGraficaColumnas(array $columnas, string $descripcion): string
{
    $maximo = max(array_merge([0.0], array_map(static fn (array $columna): float => $columna['valor'], $columnas)));
    $html = '<div class="grafica-columnas" role="img" aria-label="' . escapar($descripcion) . '">';

    foreach ($columnas as $columna) {
        $altura = $maximo > 0 ? $columna['valor'] / $maximo * 100 : 0;
        $html .= '<div class="grafica-columna' . (!empty($columna['esDestacada']) ? ' destacada' : '') . '" title="' . escapar($columna['titulo']) . '">'
            . '<span class="valor">' . escapar($columna['texto']) . '</span>'
            . '<span class="columna" style="height: ' . round($altura, 1) . '%"></span>'
            . '<span class="etiqueta">' . escapar($columna['etiqueta']) . '</span>'
            . '</div>';
    }

    return $html . '</div>';
}

function calcularVariacion(int $actual, int $anterior): ?float
{
    return $anterior > 0 ? ($actual - $anterior) * 100 / $anterior : null;
}

function generarTextoVariacion(?float $variacion, string $periodo): string
{
    if ($variacion === null) {
        return '<span class="small text-body-secondary">Sin datos ' . escapar(str_starts_with($periodo, 'el ') ? 'del ' . substr($periodo, 3) : 'de ' . $periodo) . '</span>';
    }

    $esPositiva = $variacion >= 0;

    return '<span class="small ' . ($esPositiva ? 'text-success' : 'text-danger') . '">'
        . escapar(($esPositiva ? '+' : '') . formatearCantidad($variacion, 0)) . ' % frente '
        . escapar(str_starts_with($periodo, 'el ') ? 'al ' . substr($periodo, 3) : 'a ' . $periodo) . '</span>';
}

function mostrarPaginaError(string $titulo, string $mensaje, ?string $detalle = null): never
{
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $titulo . ': ' . $mensaje . ($detalle === null ? '' : PHP_EOL . $detalle) . PHP_EOL);
        exit(1);
    }

    if (!headers_sent()) {
        http_response_code(500);
    }

    $titulo = escapar($titulo);
    $mensaje = escapar($mensaje);
    $hojaEstilos = escapar(url('assets/lib/bootstrap/bootstrap.min.css'));
    $estilosPropios = escapar(url('assets/css/estilos.css'));
    $enlaceInicio = escapar(url('index.php'));
    $bloqueDetalle = $detalle === null ? '' : '<details class="mt-4"><summary class="small text-body-secondary">'
        . 'Detalle técnico</summary><pre class="small mt-2 p-3 bg-body-tertiary rounded text-wrap">'
        . escapar($detalle) . '</pre></details>';

    echo <<<HTML
        <!doctype html>
        <html lang="es">
        <head>
          <meta charset="utf-8">
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <title>{$titulo}</title>
          <link rel="stylesheet" href="{$hojaEstilos}">
          <link rel="stylesheet" href="{$estilosPropios}">
        </head>
        <body>
          <main class="container py-5" style="max-width: 720px">
            <div class="card tarjeta shadow-sm">
              <div class="card-body p-4 p-md-5">
                <h1 class="h3 mb-3">{$titulo}</h1>
                <p class="mb-4">{$mensaje}</p>
                <a class="btn btn-primary" href="{$enlaceInicio}">Volver al inicio</a>
                {$bloqueDetalle}
              </div>
            </div>
          </main>
        </body>
        </html>
        HTML;
    exit;
}
