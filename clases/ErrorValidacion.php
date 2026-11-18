<?php

declare(strict_types=1);

final class ErrorValidacion extends RuntimeException
{
    /** @param array<string, string> $errores */
    public function __construct(private readonly array $errores)
    {
        parent::__construct(implode(' ', $errores));
    }

    /** @return array<string, string> */
    public function obtenerErrores(): array
    {
        return $this->errores;
    }
}
