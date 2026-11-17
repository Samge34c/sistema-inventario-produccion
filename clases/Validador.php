<?php

declare(strict_types=1);

/**
 * Lee y valida los datos de un formulario. Acumula los errores por campo y
 * los lanza juntos, para que el usuario vea todo lo que debe corregir de una
 * sola vez.
 */
final class Validador
{
    /** Máximo que cabe en una columna DECIMAL(10,2). */
    public const DECIMAL_MAXIMO = 99999999.99;

    /** @var array<string, string> */
    private array $errores = [];

    /**
     * @param array<string, mixed> $datos
     */
    public function __construct(private readonly array $datos)
    {
    }

    public function obtenerTexto(string $campo, string $etiqueta, int $longitudMaxima, bool $esObligatorio = true): string
    {
        $valor = $this->datos[$campo] ?? '';
        $texto = is_string($valor) ? trim(preg_replace('/\s+/u', ' ', $valor) ?? '') : '';

        if ($esObligatorio && $texto === '') {
            $this->agregarError($campo, sprintf('Escriba %s.', $etiqueta));
        } elseif (mb_strlen($texto) > $longitudMaxima) {
            $this->agregarError($campo, sprintf('%s admite máximo %d caracteres.', ucfirst($etiqueta), $longitudMaxima));
        }

        return $texto;
    }

    /**
     * Número decimal. Si $esCeroPermitido es false, exige un valor mayor que
     * cero; si es true, un campo vacío vale cero.
     */
    public function obtenerDecimal(string $campo, string $etiqueta, bool $esCeroPermitido = false): float
    {
        $valor = $this->datos[$campo] ?? '';

        if ($esCeroPermitido && is_string($valor) && trim($valor) === '') {
            return 0.0;
        }

        $numero = is_string($valor) || is_numeric($valor) ? convertirTextoANumero((string) $valor) : null;

        if ($numero === null) {
            $this->agregarError($campo, sprintf('Escriba %s como un número.', $etiqueta));

            return 0.0;
        }

        $numero = round($numero, 2);
        $this->validarRangoDecimal($campo, $etiqueta, $numero, $esCeroPermitido);

        return $numero;
    }

    public function obtenerEntero(string $campo, string $etiqueta, int $minimo, int $maximo = 1000000): int
    {
        $valor = filter_var($this->datos[$campo] ?? null, FILTER_VALIDATE_INT);

        if ($valor === false) {
            $this->agregarError($campo, sprintf('Escriba %s como un número entero.', $etiqueta));

            return 0;
        }

        if ($valor < $minimo || $valor > $maximo) {
            $this->agregarError($campo, sprintf(
                '%s debe estar entre %s y %s.',
                ucfirst($etiqueta),
                formatearCantidad($minimo),
                formatearCantidad($maximo)
            ));
        }

        return (int) $valor;
    }

    /**
     * Identificador elegido en un selector (entero positivo).
     */
    public function obtenerId(string $campo, string $etiqueta): int
    {
        $valor = filter_var($this->datos[$campo] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($valor === false) {
            $this->agregarError($campo, sprintf('Elija %s.', $etiqueta));

            return 0;
        }

        return (int) $valor;
    }

    /**
     * Fecha en formato AAAA-MM-DD. Si se indica $fechaMinima, no la deja ser anterior.
     */
    public function obtenerFecha(string $campo, string $etiqueta, ?string $fechaMinima = null): string
    {
        $valor = $this->datos[$campo] ?? '';
        $fecha = is_string($valor) ? trim($valor) : '';

        if (!esFechaValida($fecha)) {
            $this->agregarError($campo, sprintf('Elija %s.', $etiqueta));
        } elseif ($fechaMinima !== null && $fecha < $fechaMinima) {
            $this->agregarError($campo, sprintf('%s no puede ser anterior a %s.', ucfirst($etiqueta), formatearFecha($fechaMinima)));
        }

        return $fecha;
    }

    /**
     * @param list<string> $opciones
     */
    public function obtenerOpcion(string $campo, string $etiqueta, array $opciones): string
    {
        $valor = $this->datos[$campo] ?? '';

        if (!is_string($valor) || !in_array($valor, $opciones, true)) {
            $this->agregarError($campo, sprintf('Elija %s.', $etiqueta));

            return '';
        }

        return $valor;
    }

    public function agregarError(string $campo, string $mensaje): void
    {
        if (!isset($this->errores[$campo])) {
            $this->errores[$campo] = $mensaje;
        }
    }

    public function tieneErrores(): bool
    {
        return $this->errores !== [];
    }

    public function lanzarSiHayErrores(): void
    {
        if ($this->tieneErrores()) {
            throw new ErrorValidacion($this->errores);
        }
    }

    private function validarRangoDecimal(string $campo, string $etiqueta, float $numero, bool $esCeroPermitido): void
    {
        if ($numero < 0 || (!$esCeroPermitido && $numero == 0.0)) {
            $this->agregarError($campo, sprintf(
                '%s debe ser %s.',
                ucfirst($etiqueta),
                $esCeroPermitido ? 'cero o mayor' : 'mayor que cero'
            ));
        } elseif ($numero > self::DECIMAL_MAXIMO) {
            $this->agregarError($campo, sprintf('%s es demasiado grande.', ucfirst($etiqueta)));
        }
    }
}
