<?php

declare(strict_types=1);

/*
 * Compatibilidad con los scripts que esperan la variable $conexion al incluir
 * este archivo, como herramientas/crear-usuario.php. Las páginas del sistema
 * usan obtenerConexion() de includes/funciones.php.
 */

require_once __DIR__ . '/../includes/cargador.php';

$conexion = obtenerConexion();
