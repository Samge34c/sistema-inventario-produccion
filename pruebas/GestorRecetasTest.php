<?php

declare(strict_types=1);

final class GestorRecetasTest extends PruebaIntegracion
{
    private GestorRecetas $recetas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->recetas = new GestorRecetas($this->conexion);
    }

    public function testCapacidadYProyeccionConPedidos(): void
    {
        (new GestorPedidos($this->conexion))->crearPedido([
            'materia_prima_id' => (string) $this->ids['levadura'],
            'cantidad' => '300',
            'fecha_estimada_llegada' => sumarDias(obtenerFechaHoy(), 3),
        ]);

        $pan = $this->recetas->obtenerReceta($this->ids['pan']);

        $this->assertSame(100, $pan['capacidad']['unidades_maximas']);
        $this->assertSame('Levadura seca', $pan['capacidad']['limitante']['nombre']);
        $this->assertSame(200, $pan['proyeccion']['unidades_maximas']);
        $this->assertSame('Harina de trigo', $pan['proyeccion']['limitante']['nombre']);
        $this->assertTrue($pan['puede_producirse']);
    }

    public function testGuardaRecetaEIgnoraFilasVacias(): void
    {
        $id = $this->recetas->guardarReceta([
            'nombre' => 'Arepa de huevo',
            'cantidad_producir' => '6',
            'ingredientes' => [
                ['materia_prima_id' => (string) $this->ids['harina'], 'cantidad' => '300,5'],
                ['materia_prima_id' => '', 'cantidad' => ''],
                ['materia_prima_id' => (string) $this->ids['huevos'], 'cantidad' => '6'],
            ],
        ]);

        $receta = $this->recetas->obtenerReceta($id);
        $this->assertCount(2, $receta['ingredientes']);
        $this->assertSame(12, $receta['capacidad']['unidades_maximas']);
    }

    public function testRechazaIngredientesRepetidosYCantidadesInvalidas(): void
    {
        try {
            $this->recetas->guardarReceta([
                'nombre' => 'Pan doble',
                'cantidad_producir' => '0',
                'ingredientes' => [
                    ['materia_prima_id' => '1', 'cantidad' => '100'],
                    ['materia_prima_id' => '1', 'cantidad' => '50'],
                    ['materia_prima_id' => '2', 'cantidad' => '0'],
                ],
            ]);
            $this->fail('Debió rechazar la receta.');
        } catch (ErrorValidacion $error) {
            $errores = $error->obtenerErrores();
            $this->assertArrayHasKey('cantidad_producir', $errores);
            $this->assertStringContainsString('ya está en la receta', $errores['ingredientes_1_materia']);
            $this->assertArrayHasKey('ingredientes_2_cantidad', $errores);
        }
    }

    public function testRecetaConVentasSeArchivaEnLugarDeEliminarse(): void
    {
        (new GestorVentas($this->conexion))->registrarVenta(['cantidades' => [$this->ids['pan'] => '2']]);

        try {
            $this->recetas->eliminarReceta($this->ids['pan']);
            $this->fail('Debió impedir la eliminación.');
        } catch (ErrorValidacion $error) {
            $this->assertStringContainsString('Archívela', $error->getMessage());
        }

        $this->recetas->cambiarEstadoReceta($this->ids['pan'], false);

        $this->assertSame(['Galleta'], array_column($this->recetas->listarOpcionesRecetas(), 'nombre'));
        $this->assertCount(2, $this->recetas->listarRecetas(true));
    }
}
