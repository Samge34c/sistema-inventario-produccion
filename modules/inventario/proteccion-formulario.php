<?php

declare(strict_types=1);

function obtenerTokenMateriaPrima(): string
{
    if (!isset($_SESSION['token_materia_prima']) || !is_string($_SESSION['token_materia_prima'])) {
        $_SESSION['token_materia_prima'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['token_materia_prima'];
}

function validarTokenMateriaPrima(mixed $token): bool
{
    return is_string($token)
        && isset($_SESSION['token_materia_prima'])
        && is_string($_SESSION['token_materia_prima'])
        && hash_equals($_SESSION['token_materia_prima'], $token);
}
