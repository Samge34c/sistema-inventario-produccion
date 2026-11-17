<?php

declare(strict_types=1);

/**
 * Registro de ventas y reportes con un conjunto de ventas conocido
 * (regla especial de la Definition of Done para reportes).
 *
 * Ventas del lunes 9 de noviembre de 2026:
 *   07:10  3 pan
 *   07:40  2 pan + 1 galleta
 *   08:05  10 pan
 *   17:30  4 galletas
 * Total: 4 ventas y 20 unidades (15 pan, 5 galletas).
 * Hora pico: 7 a. m. (2 ventas); 8 a. m. tiene más unidades pero 1 sola venta.
 */
final class GestorVentasTest extends PruebaIntegracion
{
    private const LUNES = '2026-11-09';

    private GestorVentas $ventas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ventas = new GestorVentas($this->conexion);
    }

    /**
     * @param array<int, int> $cantidades receta_id => unidades
     */
    private function venderEn(string $fechaHora, array $cantidades): int
    {
        return $this->ventas->registrarVenta(['fecha_hora' => $fechaHora, 'cantidades' => array_map('strval', $cantidades)]);
    }

    private function cargarVentasDelLunes(): void
    {
        $pan = $this->ids['pan'];
        $galleta = $this->ids['galleta'];
        $this->venderEn(self::LUNES . 'T07:10', [$pan => 3]);
        $this->venderEn(self::LUNES . 'T07:40', [$pan => 2, $galleta => 1]);
        $this->venderEn(self::LUNES . 'T08:05', [$pan => 10]);
        $this->venderEn(self::LUNES . 'T17:30', [$galleta => 4]);
    }

    public function testReporteDiario(): void
    {
        $this->cargarVentasDelLunes();

        $reporte = $this->ventas->generarReporteDiario(self::LUNES);

        $this->assertSame(['transacciones' => 4, 'unidades' => 20], $reporte['resumen']);
        $this->assertSame(['hora' => 7, 'transacciones' => 2, 'unidades' => 6], $reporte['hora_pico']);
        $this->assertSame(['transacciones' => 1, 'unidades' => 10], $reporte['por_hora'][8]);
        $this->assertSame(['Pan', 'Galleta'], array_column($reporte['por_producto'], 'nombre'));
        $this->assertSame([15, 5], array_column($reporte['por_producto'], 'unidades'));
        $this->assertSame([75.0, 25.0], array_column($reporte['por_producto'], 'porcentaje'));
    }

    public function testReporteSemanalAgrupaDeLunesADomingo(): void
    {
        $this->cargarVentasDelLunes();
        $this->venderEn('2026-11-12T09:00', [$this->ids['pan'] => 30]);   // jueves de la misma semana
        $this->venderEn('2026-11-16T09:00', [$this->ids['pan'] => 99]);   // lunes siguiente: no cuenta
        $this->venderEn('2026-11-04T09:00', [$this->ids['pan'] => 8]);    // semana anterior

        $reporte = $this->ventas->generarReporteSemanal('2026-11-14');

        $this->assertSame('2026-11-09', $reporte['inicio']);
        $this->assertSame('2026-11-15', $reporte['fin']);
        $this->assertSame(['transacciones' => 5, 'unidades' => 50], $reporte['resumen']);
        $this->assertSame(['transacciones' => 1, 'unidades' => 8], $reporte['resumen_anterior']);
        $this->assertSame('2026-11-12', $reporte['mejor_dia']);
        $this->assertCount(7, $reporte['por_dia']);
        $this->assertSame(['transacciones' => 0, 'unidades' => 0], $reporte['por_dia']['2026-11-15']);
    }

    public function testHoraPicoDesempataPorUnidades(): void
    {
        $this->venderEn(self::LUNES . 'T09:15', [$this->ids['pan'] => 2]);
        $this->venderEn(self::LUNES . 'T10:15', [$this->ids['pan'] => 5]);

        $reporte = $this->ventas->generarReporteHoraPico(self::LUNES, self::LUNES);

        $this->assertSame(10, $reporte['hora_pico']['hora']);
    }

    public function testReporteHoraPicoPromediaPorDia(): void
    {
        $this->cargarVentasDelLunes();
        $this->venderEn('2026-11-10T07:30', [$this->ids['pan'] => 4]);

        // Del lunes al miércoles (3 días): la franja de las 7 tiene 3 ventas → 1 por día.
        $reporte = $this->ventas->generarReporteHoraPico('2026-11-09', '2026-11-11');

        $this->assertSame(3, $reporte['dias']);
        $this->assertSame(7, $reporte['hora_pico']['hora']);
        $this->assertSame(1.0, $reporte['por_hora'][7]['promedio_transacciones']);
        $this->assertSame(2, $reporte['mapa'][1][7]);  // lunes a las 7
        $this->assertSame(1, $reporte['mapa'][2][7]);  // martes a las 7
    }

    public function testValidaLaVenta(): void
    {
        $this->conexion->exec('UPDATE recetas SET activa = 0 WHERE id = 2');
        $casos = [
            [['cantidades' => []], 'Agregue al menos un producto a la venta.'],
            [['cantidades' => [$this->ids['pan'] => '-1']], 'Revise las cantidades'],
            [['cantidades' => [$this->ids['galleta'] => '1']], 'Revise las cantidades'],
            [['cantidades' => [$this->ids['pan'] => '1'], 'fecha_hora' => date('Y-m-d\TH:i', strtotime('+2 days'))], 'hora futura'],
        ];

        foreach ($casos as [$datos, $mensajeEsperado]) {
            try {
                $this->ventas->registrarVenta($datos);
                $this->fail('Debió rechazar: ' . $mensajeEsperado);
            } catch (ErrorValidacion $error) {
                $this->assertStringContainsString($mensajeEsperado, $error->getMessage());
            }
        }

        $this->assertSame(0, (int) $this->consultarValor('SELECT COUNT(*) FROM ventas'));
    }

    public function testAnularVentaLaQuitaDeLosReportes(): void
    {
        $this->cargarVentasDelLunes();
        $ventaId = (int) $this->consultarValor('SELECT MAX(id) FROM ventas');

        $this->ventas->anularVenta($ventaId);

        $this->assertSame(['transacciones' => 3, 'unidades' => 16], $this->ventas->obtenerResumenDia(self::LUNES));
        $this->expectException(ErrorValidacion::class);
        $this->ventas->anularVenta($ventaId);
    }
}
