<?php

declare(strict_types=1);

/**
 * Materias primas y movimientos contra la base de datos de pruebas.
 */
final class GestorInventarioTest extends PruebaIntegracion
{
    private GestorInventario $inventario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventario = new GestorInventario($this->conexion);
    }

    public function testEntradaYSalidaActualizanExistenciaYDejanHistorial(): void
    {
        $this->inventario->registrarMovimiento($this->ids['harina'], 'ENTRADA', 2500, 'Compra');
        $this->inventario->registrarMovimiento($this->ids['harina'], 'SALIDA', 1000.5, 'Merma');

        // 10.000 + 2.500 − 1.000,5 = 11.499,5
        $this->assertSame(11499.5, $this->obtenerExistencia($this->ids['harina']));
        $this->assertSame(2, (int) $this->consultarValor('SELECT COUNT(*) FROM movimientos_inventario WHERE materia_prima_id = 1'));
    }

    public function testSalidaMayorALaExistenciaSeRechazaSinCambiarNada(): void
    {
        try {
            $this->inventario->registrarMovimiento($this->ids['levadura'], 'SALIDA', 150);
            $this->fail('Debió rechazar la salida.');
        } catch (ErrorValidacion $error) {
            $this->assertStringContainsString('No hay suficiente levadura seca', $error->getMessage());
        }

        $this->assertSame(100.0, $this->obtenerExistencia($this->ids['levadura']));
        $this->assertSame(0, (int) $this->consultarValor('SELECT COUNT(*) FROM movimientos_inventario'));
    }

    public function testCrearMateriaPrimaRegistraInventarioInicial(): void
    {
        $id = $this->inventario->guardarMateriaPrima([
            'nombre' => 'Azúcar',
            'unidad_medida' => 'g',
            'stock_minimo' => '1000',
            'cantidad_inicial' => '1.250,5',
        ]);

        $this->assertSame(1250.5, $this->obtenerExistencia($id));
        $this->assertSame('Inventario inicial', $this->consultarValor(
            'SELECT observacion FROM movimientos_inventario WHERE materia_prima_id = :id',
            [':id' => $id]
        ));
    }

    public function testNoPermiteNombresRepetidosNiDatosInvalidos(): void
    {
        try {
            $this->inventario->guardarMateriaPrima(['nombre' => 'harina de trigo', 'unidad_medida' => 'litros', 'stock_minimo' => '-5']);
            $this->fail('Debió rechazar los datos.');
        } catch (ErrorValidacion $error) {
            $errores = $error->obtenerErrores();
            $this->assertSame('Ya existe una materia prima con ese nombre.', $errores['nombre']);
            $this->assertArrayHasKey('unidad_medida', $errores);
            $this->assertArrayHasKey('stock_minimo', $errores);
        }
    }

    public function testEstadoDeExistencia(): void
    {
        $this->assertSame(GestorInventario::SIN_EXISTENCIAS, GestorInventario::determinarEstadoExistencia(0, 100));
        $this->assertSame(GestorInventario::BAJO_MINIMO, GestorInventario::determinarEstadoExistencia(99.99, 100));
        $this->assertSame(GestorInventario::DISPONIBLE, GestorInventario::determinarEstadoExistencia(100, 100));
    }

    public function testNoEliminaMateriaPrimaUsadaEnRecetas(): void
    {
        $this->expectException(ErrorValidacion::class);
        $this->expectExceptionMessage('se usa en 2 recetas');
        $this->inventario->eliminarMateriaPrima($this->ids['harina']);
    }

    public function testEliminaMateriaPrimaSinHistorial(): void
    {
        $id = $this->inventario->guardarMateriaPrima(['nombre' => 'Canela', 'unidad_medida' => 'g', 'stock_minimo' => '0']);
        $this->inventario->eliminarMateriaPrima($id);

        $this->assertNull($this->inventario->obtenerMateriaPrima($id));
    }
}
