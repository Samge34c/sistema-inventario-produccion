<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Formato de cantidades, números y fechas en español de Colombia.
 */
final class FuncionesTest extends TestCase
{
    public function testFormateaCantidadesConPuntoDeMilesYComaDecimal(): void
    {
        $this->assertSame('25.000', formatearCantidad(25000));
        $this->assertSame('1.250,5', formatearCantidad(1250.5));
        $this->assertSame('0,25', formatearCantidad(0.25));
        $this->assertSame('187,5', formatearCantidad(187.5));
        $this->assertSame('0', formatearCantidad(-0.001));
        $this->assertSame('10,2', formatearCantidad(10.21, 1));
    }

    public function testEscribeLaUnidadEnSingularOPlural(): void
    {
        $this->assertSame('1 unidad', formatearCantidadConUnidad(1, 'unidad'));
        $this->assertSame('90 unidades', formatearCantidadConUnidad(90, 'unidad'));
        $this->assertSame('2.500 g', formatearCantidadConUnidad(2500, 'g'));
        $this->assertSame('1 receta', pluralizar(1, 'receta', 'recetas'));
        $this->assertSame('3 recetas', pluralizar(3, 'receta', 'recetas'));
    }

    public function testConvierteTextoConComaOPuntoANumero(): void
    {
        $this->assertSame(1250.5, convertirTextoANumero('1.250,5'));
        $this->assertSame(1250.5, convertirTextoANumero('1250,5'));
        $this->assertSame(1250.5, convertirTextoANumero('1250.5'));
        $this->assertSame(3000.0, convertirTextoANumero(' 3000 '));
        $this->assertNull(convertirTextoANumero('abc'));
        $this->assertNull(convertirTextoANumero(''));
    }

    public function testValorDeCampoSinCerosSobrantes(): void
    {
        $this->assertSame('3000', formatearValorCampo(3000.0));
        $this->assertSame('0.25', formatearValorCampo(0.25));
        $this->assertSame('texto', formatearValorCampo('texto'));
    }

    public function testFranjasHorariasEnFormatoDeDoceHoras(): void
    {
        $this->assertSame('12 a. m.', formatearFranjaHoraria(0));
        $this->assertSame('7 a. m.', formatearFranjaHoraria(7));
        $this->assertSame('12 p. m.', formatearFranjaHoraria(12));
        $this->assertSame('5 p. m.', formatearFranjaHoraria(17));
    }

    public function testCalculaElLunesDeLaSemana(): void
    {
        $this->assertSame('2026-11-16', obtenerLunesDeSemana('2026-11-16'));
        $this->assertSame('2026-11-16', obtenerLunesDeSemana('2026-11-19'));
        $this->assertSame('2026-11-16', obtenerLunesDeSemana('2026-11-22'));
    }

    public function testValidaFechasReales(): void
    {
        $this->assertTrue(esFechaValida('2026-11-17'));
        $this->assertFalse(esFechaValida('2026-02-30'));
        $this->assertFalse(esFechaValida('17/11/2026'));
    }

    public function testDescribeDiasCercanos(): void
    {
        $hoy = obtenerFechaHoy();

        $this->assertSame('Hoy', describirDia($hoy));
        $this->assertSame('Mañana', describirDia(sumarDias($hoy, 1)));
        $this->assertSame('Ayer', describirDia(sumarDias($hoy, -1)));
        $this->assertSame(-3, calcularDiferenciaDias($hoy, sumarDias($hoy, -3)));
    }

    public function testCalculaVariacionPorcentual(): void
    {
        $this->assertSame(50.0, calcularVariacion(150, 100));
        $this->assertSame(-25.0, calcularVariacion(75, 100));
        $this->assertNull(calcularVariacion(10, 0));
    }

    public function testEscapaHtml(): void
    {
        $this->assertSame('&lt;b&gt;Pan &amp; queso&lt;/b&gt;', escapar('<b>Pan & queso</b>'));
    }
}
