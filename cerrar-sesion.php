<?php

declare(strict_types=1);

const PAGINA_PUBLICA = true;

require_once __DIR__ . '/includes/inicio.php';

if (!esPeticionPost()) {
    http_response_code(405);
    header('Allow: POST');
    exit('Utilice el formulario para cerrar sesión.');
}

exigirTokenAcceso();
$_SESSION = [];
$parametros = session_get_cookie_params();
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $parametros['path'],
    'secure' => $parametros['secure'], 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
header('Location: ' . url('iniciar-sesion.php'), true, 303);
exit;
