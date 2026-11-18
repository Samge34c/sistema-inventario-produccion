<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

abstract class PruebaIntegracion extends TestCase
{
    protected PDO $conexion;

    /** @var array{harina: int, levadura: int, huevos: int, pan: int, galleta: int} */
    protected array $ids;

    protected function setUp(): void
    {
        $conexion = EntornoPruebas::obtenerConexion();

        if ($conexion === null) {
            $this->markTestSkipped('MySQL no está disponible; se omiten las pruebas de integración.');
        }

        $this->conexion = $conexion;
        $this->ids = EntornoPruebas::cargarDatosBase($conexion);
    }

    protected function obtenerExistencia(int $materiaPrimaId): float
    {
        return EntornoPruebas::obtenerExistencia($this->conexion, $materiaPrimaId);
    }

    /** @param array<string, mixed> $parametros */
    protected function consultarValor(string $sql, array $parametros = []): mixed
    {
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchColumn();
    }
}
