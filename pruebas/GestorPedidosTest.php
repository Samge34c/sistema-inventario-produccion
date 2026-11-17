<?php

declare(strict_types=1);

/**
 * Pedidos pendientes de llegada contra la base de datos de pruebas.
 */
final class GestorPedidosTest extends PruebaIntegracion
{
    private GestorPedidos $pedidos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pedidos = new GestorPedidos($this->conexion);
    }

    public function testRecibirPedidoSumaLoQueLlegoYNoSePuedeRecibirDosVeces(): void
    {
        $pedidoId = $this->pedidos->crearPedido([
            'materia_prima_id' => (string) $this->ids['levadura'],
            'cantidad' => '500',
            'fecha_estimada_llegada' => sumarDias(obtenerFechaHoy(), 2),
            'proveedor' => 'Distribuidora de prueba',
        ]);

        $this->assertSame([$this->ids['levadura'] => 500.0], $this->pedidos->obtenerCantidadesPorLlegar());
        $this->assertSame([], $this->pedidos->obtenerCantidadesPorLlegar(obtenerFechaHoy()));

        $this->pedidos->recibirPedido($pedidoId, ['cantidad_recibida' => '480']);

        // 100 + 480 = 580, y el movimiento queda ligado al pedido.
        $this->assertSame(580.0, $this->obtenerExistencia($this->ids['levadura']));
        $this->assertSame('RECIBIDO', $this->consultarValor('SELECT estado FROM materiales_pendientes WHERE id = :id', [':id' => $pedidoId]));
        $this->assertSame($pedidoId, (int) $this->consultarValor(
            "SELECT referencia_id FROM movimientos_inventario WHERE origen = 'PEDIDO'"
        ));
        $this->assertSame([], $this->pedidos->obtenerCantidadesPorLlegar());

        $this->expectException(ErrorValidacion::class);
        $this->pedidos->recibirPedido($pedidoId, ['cantidad_recibida' => '480']);
    }

    public function testPedidoNoAceptaFechaPasada(): void
    {
        $this->expectException(ErrorValidacion::class);
        $this->pedidos->crearPedido([
            'materia_prima_id' => (string) $this->ids['harina'],
            'cantidad' => '100',
            'fecha_estimada_llegada' => sumarDias(obtenerFechaHoy(), -1),
        ]);
    }
}
