<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../modules/inventario/funciones.php';
require_once __DIR__ . '/../../modules/inventario/movimientos-funciones.php';

final class InventarioTest extends TestCase
{
    private function material(array $cambios = []): array
    {
        return array_replace(['nombre'=>'Harina', 'unidad_medida'=>'g', 'cantidad_disponible'=>'10000', 'stock_minimo'=>'1000'], $cambios);
    }

    public function testCp02ParticionesUnidad(): void
    {
        foreach (['g','kg','ml','l','unidad'] as $unidad) {
            self::assertSame([], validarMateriaPrima($this->material(['unidad_medida'=>$unidad])));
        }
        self::assertContains('Seleccione una unidad de medida válida.', validarMateriaPrima($this->material(['unidad_medida'=>'litros'])));
    }

    public function testCp03FronteraCero(): void
    {
        self::assertSame([], validarMateriaPrima($this->material(['cantidad_disponible'=>'0'])));
    }

    public function testCp04FronteraNegativa(): void
    {
        self::assertContains('La cantidad disponible debe ser un número mayor o igual a cero.', validarMateriaPrima($this->material(['cantidad_disponible'=>'-0.01'])));
    }

    public function testNombreYStockInvalidos(): void
    {
        self::assertNotEmpty(validarMateriaPrima($this->material(['nombre'=>' '])));
        self::assertNotEmpty(validarMateriaPrima($this->material(['nombre'=>str_repeat('á',101)])));
        self::assertSame([], validarMateriaPrima($this->material(['nombre'=>str_repeat('á',100)])));
        self::assertNotEmpty(validarMateriaPrima($this->material(['cantidad_disponible'=>'no-numero'])));
        self::assertNotEmpty(validarMateriaPrima($this->material(['stock_minimo'=>'-1'])));
        self::assertNotEmpty(validarMateriaPrima($this->material(['stock_minimo'=>'abc'])));
    }

    public function testCp10FronterasCantidadMovimiento(): void
    {
        $base = ['materia_prima_id'=>'1','tipo'=>'ENTRADA','cantidad'=>'0.01','observacion'=>''];
        self::assertSame([],validarMovimiento($base));
        self::assertSame(1,convertirCantidadCentesimas('0.01'));
        self::assertSame(9999999999,convertirCantidadCentesimas('99999999.99'));
        self::assertSame([],validarMovimiento(array_replace($base,['cantidad'=>'99999999.99'])));
        foreach (['0','-0.01','1.001','100000000','1e2','NaN','INF','', '1,5',['1']] as $cantidad) {
            self::assertNotEmpty(validarMovimiento(array_replace($base,['cantidad'=>$cantidad])));
        }
        self::assertSame('0.01',convertirCentesimasCantidad(1));
    }

    public function testTokenSesionYDatosIncompletos(): void
    {
        $_SESSION=[];
        self::assertFalse(validarTokenMovimiento('no-token'));
        $token=obtenerTokenMovimiento();
        self::assertSame(64,strlen($token));
        self::assertSame($token,obtenerTokenMovimiento());
        self::assertTrue(validarTokenMovimiento($token));
        self::assertFalse(validarTokenMovimiento('invalido'));
        self::assertFalse(validarTokenMovimiento(['invalido']));
        self::assertCount(4,validarMovimiento([]));
    }
}
