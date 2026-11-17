<?php

declare(strict_types=1);

/**
 * Planificación, faltantes y descuento de insumos con resultados conocidos.
 *
 * Datos base: 10.000 g de harina y 100 g de levadura; el pan rinde 20 por
 * tanda con 1.000 g de harina y 20 g de levadura (1 g de levadura por unidad).
 */
final class GestorProduccionTest extends PruebaIntegracion
{
    private GestorProduccion $produccion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->produccion = new GestorProduccion($this->conexion);
    }

    private function planificarPan(string $fecha, int $unidades): int
    {
        return $this->produccion->planificarProduccion([
            'receta_id' => (string) $this->ids['pan'],
            'fecha_planeada' => $fecha,
            'cantidad_planeada' => (string) $unidades,
        ]);
    }

    public function testPlanesSeRevisanEnOrdenYComprometenMateriales(): void
    {
        $hoy = obtenerFechaHoy();
        $primero = $this->planificarPan($hoy, 100);
        $segundo = $this->planificarPan(sumarDias($hoy, 1), 50);

        $analisis = $this->produccion->analizarPlanesPendientes();

        // El primero usa los 100 g de levadura; al segundo le faltan sus 50 g.
        $this->assertSame(CalculadoraProduccion::COMPLETO, $analisis[$primero]['estado']);
        $this->assertSame(CalculadoraProduccion::FALTAN_MATERIALES, $analisis[$segundo]['estado']);
        $levaduraSegundo = $analisis[$segundo]['ingredientes'][1];
        $this->assertSame('Levadura seca', $levaduraSegundo['nombre']);
        $this->assertSame(50.0, $levaduraSegundo['cantidad_requerida']);
        $this->assertSame(100.0, $levaduraSegundo['cantidad_comprometida']);
        $this->assertSame(50.0, $levaduraSegundo['faltante']);
    }

    public function testPedidoQueLlegaATiempoCubreElFaltante(): void
    {
        $hoy = obtenerFechaHoy();
        $this->planificarPan($hoy, 100);
        $segundo = $this->planificarPan(sumarDias($hoy, 1), 50);
        $pedidos = new GestorPedidos($this->conexion);

        // Llega después del plan: no cuenta.
        $pedidos->crearPedido(['materia_prima_id' => '2', 'cantidad' => '60', 'fecha_estimada_llegada' => sumarDias($hoy, 2)]);
        $this->assertSame(CalculadoraProduccion::FALTAN_MATERIALES, $this->produccion->analizarPlanesPendientes()[$segundo]['estado']);

        // Llega el mismo día del plan: sí cuenta.
        $pedidos->crearPedido(['materia_prima_id' => '2', 'cantidad' => '60', 'fecha_estimada_llegada' => sumarDias($hoy, 1)]);
        $analisis = $this->produccion->analizarPlanesPendientes()[$segundo];
        $this->assertSame(CalculadoraProduccion::DEPENDE_DE_PEDIDOS, $analisis['estado']);
        $this->assertSame(60.0, $analisis['ingredientes'][1]['cantidad_por_llegar']);
    }

    public function testRegistrarProduccionDescuentaInsumosProporcionales(): void
    {
        $plan = $this->planificarPan(obtenerFechaHoy(), 100);

        // Se producen 80 de los 100 planeados: 4.000 g de harina y 80 g de levadura.
        $this->produccion->registrarProduccion($plan, ['unidades_producidas' => '80']);

        $this->assertSame(6000.0, $this->obtenerExistencia($this->ids['harina']));
        $this->assertSame(20.0, $this->obtenerExistencia($this->ids['levadura']));
        $registrada = $this->produccion->obtenerProduccion($plan);
        $this->assertSame('REALIZADA', $registrada['estado']);
        $this->assertSame(80, $registrada['cantidad_producida']);
        $this->assertCount(2, $registrada['consumos']);
        $this->assertSame([], $this->produccion->analizarPlanesPendientes());
    }

    public function testNoRegistraSiNoAlcanzaYNoDescuentaNada(): void
    {
        $plan = $this->planificarPan(obtenerFechaHoy(), 100);

        try {
            $this->produccion->registrarProduccion($plan, ['unidades_producidas' => '120']);
            $this->fail('Debió rechazar la producción.');
        } catch (ErrorValidacion $error) {
            $this->assertStringContainsString('Faltan 20 g de levadura seca', $error->getMessage());
            $this->assertStringContainsString('máximo 100 unidades', $error->getMessage());
        }

        $this->assertSame(10000.0, $this->obtenerExistencia($this->ids['harina']));
        $this->assertSame(100.0, $this->obtenerExistencia($this->ids['levadura']));
        $this->assertSame('PLANEADA', $this->produccion->obtenerProduccion($plan)['estado']);
    }

    public function testNoSeRegistraDosVecesNiSeCancelaUnPlanRealizado(): void
    {
        $plan = $this->planificarPan(obtenerFechaHoy(), 10);
        $this->produccion->registrarProduccion($plan, ['unidades_producidas' => '10']);

        $this->expectException(ErrorValidacion::class);
        $this->produccion->cancelarProduccion($plan, []);
    }

    public function testCancelarLiberaLosMateriales(): void
    {
        $hoy = obtenerFechaHoy();
        $primero = $this->planificarPan($hoy, 100);
        $segundo = $this->planificarPan($hoy, 50);

        $this->produccion->cancelarProduccion($primero, ['motivo' => 'Cambio de pedido']);
        $analisis = $this->produccion->analizarPlanesPendientes();

        $this->assertArrayNotHasKey($primero, $analisis);
        $this->assertSame(CalculadoraProduccion::COMPLETO, $analisis[$segundo]['estado']);
        $this->assertSame('Cambio de pedido', $this->produccion->obtenerProduccion($primero)['observacion']);
    }

    public function testNoPlanificaEnFechaPasadaNiConCantidadCero(): void
    {
        try {
            $this->produccion->planificarProduccion([
                'receta_id' => (string) $this->ids['pan'],
                'fecha_planeada' => sumarDias(obtenerFechaHoy(), -1),
                'cantidad_planeada' => '0',
            ]);
            $this->fail('Debió rechazar el plan.');
        } catch (ErrorValidacion $error) {
            $this->assertArrayHasKey('fecha_planeada', $error->obtenerErrores());
            $this->assertArrayHasKey('cantidad_planeada', $error->obtenerErrores());
        }
    }

    public function testResumenDeMaterialesDelDiaSumaTodosLosPlanes(): void
    {
        $hoy = obtenerFechaHoy();
        $this->planificarPan($hoy, 100);
        $this->produccion->planificarProduccion([
            'receta_id' => (string) $this->ids['galleta'],
            'fecha_planeada' => $hoy,
            'cantidad_planeada' => '40',
        ]);

        $resumen = array_column($this->produccion->resumirMaterialesPorFecha($hoy), null, 'nombre');

        // Harina: 5.000 (pan) + 2.000 (galleta) = 7.000; huevos: 8 de 12.
        $this->assertSame(7000.0, $resumen['Harina de trigo']['cantidad_requerida']);
        $this->assertSame(8.0, $resumen['Huevos']['cantidad_requerida']);
        $this->assertSame(0.0, $resumen['Harina de trigo']['faltante']);
    }
}
