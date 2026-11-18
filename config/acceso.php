<?php

declare(strict_types=1);

function iniciarSesionSegura(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'httponly' => true,
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'samesite' => 'Lax',
            'path' => '/',
        ]);
        session_start();
    }
}

function obtenerTokenAcceso(): string
{
    iniciarSesionSegura();
    if (!isset($_SESSION['token_acceso']) || !is_string($_SESSION['token_acceso'])) {
        $_SESSION['token_acceso'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['token_acceso'];
}

function validarTokenAcceso(mixed $token): bool
{
    iniciarSesionSegura();
    return is_string($token) && isset($_SESSION['token_acceso'])
        && is_string($_SESSION['token_acceso']) && hash_equals($_SESSION['token_acceso'], $token);
}

function exigirTokenAcceso(): void
{
    if (!validarTokenAcceso($_POST['token'] ?? null)) {
        http_response_code(403);
        exit('Solicitud rechazada: código de seguridad no válido.');
    }
}
