<?php

declare(strict_types=1);

/**
 * Error en los datos que ingresó el usuario o en una regla del negocio
 * (por ejemplo, sacar más harina de la que hay). Lleva un mensaje por campo
 * para mostrarlo junto al formulario.
 */
final class ErrorValidacion extends RuntimeException
{
    /**
     * @param array<string, string> $errores campo => mensaje
     */
    public function __construct(private readonly array $errores)
    {
        parent::__construct(implode(' ', $errores));
    }

    /**
     * @return array<string, string>
     */
    public function obtenerErrores(): array
    {
        return $this->errores;
    }
}
