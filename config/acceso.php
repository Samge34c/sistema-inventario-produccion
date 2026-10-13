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

function exigirAcceso(): void
{
    iniciarSesionSegura();
    if (!isset($_SESSION['usuario_id']) || !is_int($_SESSION['usuario_id']) || $_SESSION['usuario_id'] < 1) {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            http_response_code(403);
            exit('Acceso rechazado: debe iniciar sesión.');
        }
        header('Location: /iniciar-sesion.php', true, 303);
        exit;
    }
    global $conexion;
    require_once __DIR__ . '/database.php';
    $consulta = $conexion->prepare('SELECT id FROM usuarios WHERE id = ? AND activo = 1');
    $consulta->execute([$_SESSION['usuario_id']]);
    if ($consulta->fetchColumn() === false) {
        $_SESSION = [];
        http_response_code(403);
        exit('Acceso rechazado: la cuenta no está habilitada.');
    }
}

function exigirTokenAcceso(): void
{
    if (!validarTokenAcceso($_POST['token'] ?? null)) {
        http_response_code(403);
        exit('Solicitud rechazada: código de seguridad no válido.');
    }
}
