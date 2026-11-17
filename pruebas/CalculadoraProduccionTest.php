<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Cálculos de producción con entradas y resultados numéricos conocidos
 * (regla especial de la Definition of Done para inventario y recetas).
 *
 * Los datos son los de la receta «Pan francés» de database/datos-prueba.sql.
 */
final class CalculadoraProduccionTest extends TestCase
{
    private CalculadoraProduccion $calculadora;

    protected function setUp(): void
    {
        $this->calculadora = new CalculadoraProduccion();
    }

    /**
     * @return list<array{materia_prima_id: int, nombre: string, unidad_medida: string, cantidad: float, cantidad_disponible: float}>
     */
    private function crearIngredientesPanFrances(): array
    {
        return [
            $this->crearIngrediente(1, 'Harina de trigo', 5000, 25000),
            $this->crearIngrediente(6, 'Levadura seca', 100, 400),
            $this->crearIngrediente(7, 'Sal', 90, 2000),
            $this->crearIngrediente(2, 'Azúcar', 150, 8000),
            $this->crearIngrediente(12, 'Aceite vegetal', 100, 3000, 'ml'),
        ];
    }

    /**
     * @return array{materia_prima_id: int, nombre: string, unidad_medida: string, cantidad: float, cantidad_disponible: float}
     */
    private function crearIngrediente(int $id, string $nombre, float $cantidad, float $disponible, string $unidad = 'g'): array
    {
        return [
            'materia_prima_id' => $id,
            'nombre' => $nombre,
            'unidad_medida' => $unidad,
            'cantidad' => $cantidad,
            'cantidad_disponible' => $disponible,
        ];
    }

    public function testCalculaProduccionMaximaYIngredienteLimitante(): void
    {
        // Harina: 25.000 × 40 / 5.000 = 200; levadura: 400 × 40 / 100 = 160; sal: 888; azúcar: 2.133; aceite: 1.200.
        $resultado = $this->calculadora->calcularProduccionMaxima($this->crearIngredientesPanFrances(), 40);

        $this->assertSame(160, $resultado['unidades_maximas']);
        $this->assertSame('Levadura seca', $resultado['limitante']['nombre']);
        $this->assertSame(
            [200, 160, 888, 2133, 1200],
            array_column($resultado['ingredientes'], 'unidades_posibles')
        );
    }

    public function testProyectaProduccionConPedidosPorLlegar(): void
    {
        // Con 500 g más de levadura: 900 × 40 / 100 = 360, ahora limita la harina con 200.
        $resultado = $this->calculadora->calcularProduccionMaxima($this->crearIngredientesPanFrances(), 40, [6 => 500.0]);

        $this->assertSame(200, $resultado['unidades_maximas']);
        $this->assertSame('Harina de trigo', $resultado['limitante']['nombre']);
    }

    public function testIngredienteAgotadoDejaLaProduccionEnCero(): void
    {
        $ingredientes = [
            $this->crearIngrediente(1, 'Harina de trigo', 2000, 25000),
            $this->crearIngrediente(10, 'Bocadillo de guayaba', 600, 0),
        ];

        $resultado = $this->calculadora->calcularProduccionMaxima($ingredientes, 12);

        $this->assertSame(0, $resultado['unidades_maximas']);
        $this->assertSame('Bocadillo de guayaba', $resultado['limitante']['nombre']);
    }

    public function testRecetaSinIngredientesNoTieneProduccionMaxima(): void
    {
        $resultado = $this->calculadora->calcularProduccionMaxima([], 10);

        $this->assertNull($resultado['unidades_maximas']);
        $this->assertNull($resultado['limitante']);
    }

    public function testNoPierdeUnidadesPorRedondeoDePuntoFlotante(): void
    {
        // 3 × 10 / 0,3 da 99,999… en punto flotante; deben ser 100 unidades.
        $this->assertSame(100, $this->calculadora->calcularUnidadesPosibles(3.0, 0.3, 10));
    }

    public function testCalculaCantidadRequeridaProporcional(): void
    {
        // Pan de leche rinde 30: para 60 unidades se necesita el doble de harina.
        $this->assertSame(6000.0, $this->calculadora->calcularCantidadRequerida(3000, 30, 60));
        // Roscón rinde 12: para 9 unidades, 250 × 9 / 12 = 187,5 g de mantequilla.
        $this->assertSame(187.5, $this->calculadora->calcularCantidadRequerida(250, 12, 9));
    }

    public function testRendimientoCeroEsInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->calculadora->calcularCantidadRequerida(100, 0, 10);
    }

    public function testAbastecimientoCompletoCuandoAlcanza(): void
    {
        // 160 unidades de pan francés piden exactamente los 400 g de levadura que hay.
        $analisis = $this->calculadora->analizarAbastecimiento($this->crearIngredientesPanFrances(), 40, 160);

        $this->assertSame(CalculadoraProduccion::COMPLETO, $analisis['estado']);
        $this->assertSame(400.0, $analisis['ingredientes'][1]['cantidad_requerida']);
        $this->assertSame(0.0, $analisis['ingredientes'][1]['faltante']);
    }

    public function testAbastecimientoDependeDePedidos(): void
    {
        // Almojábana: 50 unidades piden 2.000 g de queso; hay 1.200 y llegan 3.000.
        $ingredientes = [$this->crearIngrediente(8, 'Queso costeño', 1000, 1200)];

        $analisis = $this->calculadora->analizarAbastecimiento($ingredientes, 25, 50, [], [8 => 3000.0]);

        $this->assertSame(CalculadoraProduccion::DEPENDE_DE_PEDIDOS, $analisis['estado']);
        $this->assertSame(0.0, $analisis['ingredientes'][0]['faltante']);
        $this->assertSame(800.0, $analisis['ingredientes'][0]['faltante_sin_pedidos']);
    }

    public function testFaltanMaterialesCuandoOtroPlanLosComprometio(): void
    {
        // Roscón: 24 unidades piden 80 g de levadura, pero el plan anterior ya usa los 400 g.
        $ingredientes = [$this->crearIngrediente(6, 'Levadura seca', 40, 400)];

        $analisis = $this->calculadora->analizarAbastecimiento($ingredientes, 12, 24, [6 => 400.0]);

        $this->assertSame(CalculadoraProduccion::FALTAN_MATERIALES, $analisis['estado']);
        $this->assertSame(80.0, $analisis['ingredientes'][0]['faltante']);
    }

    public function testFaltanteNuncaSuperaLoRequerido(): void
    {
        // Hay 100, los planes anteriores comprometen 500 y este pide 50: le faltan 50, no 450.
        $ingredientes = [$this->crearIngrediente(1, 'Harina de trigo', 50, 100)];

        $analisis = $this->calculadora->analizarAbastecimiento($ingredientes, 1, 1, [1 => 500.0]);

        $this->assertSame(50.0, $analisis['ingredientes'][0]['faltante']);
    }

    public function testPlanSinIngredientes(): void
    {
        $analisis = $this->calculadora->analizarAbastecimiento([], 10, 10);

        $this->assertSame(CalculadoraProduccion::SIN_INGREDIENTES, $analisis['estado']);
    }
}
