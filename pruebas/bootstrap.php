<?php

declare(strict_types=1);

/*
 * Arranque de las pruebas automáticas. Carga las clases y funciones del
 * sistema sin abrir sesión ni conexión web.
 */

require_once dirname(__DIR__) . '/includes/cargador.php';
require_once __DIR__ . '/EntornoPruebas.php';
require_once __DIR__ . '/PruebaIntegracion.php';
