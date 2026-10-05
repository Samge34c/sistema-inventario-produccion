<?php

declare(strict_types=1);

function obtenerUnidadesPermitidas(): array
{
    return ['g', 'kg', 'ml', 'l', 'unidad'];
}

function validarMateriaPrima(array $datos): array
{
    $errores = [];
    $nombre = trim((string) ($datos['nombre'] ?? ''));
    $unidadMedida = trim((string) ($datos['unidad_medida'] ?? ''));
    $cantidadDisponible = $datos['cantidad_disponible'] ?? null;
    $stockMinimo = $datos['stock_minimo'] ?? null;

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    } elseif (mb_strlen($nombre) > 100) {
        $errores[] = 'El nombre no puede superar 100 caracteres.';
    }

    if (!in_array($unidadMedida, obtenerUnidadesPermitidas(), true)) {
        $errores[] = 'Seleccione una unidad de medida válida.';
    }

    if (!is_numeric($cantidadDisponible) || (float) $cantidadDisponible < 0) {
        $errores[] = 'La cantidad disponible debe ser un número mayor o igual a cero.';
    }

    if (!is_numeric($stockMinimo) || (float) $stockMinimo < 0) {
        $errores[] = 'El stock mínimo debe ser un número mayor o igual a cero.';
    }

    return $errores;
}
