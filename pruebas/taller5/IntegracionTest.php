<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class IntegracionTest extends TestCase
{
    public function testCp11TablaDeDecision(): void
    {
        $c = new PDO(getenv('HU02_PRUEBA_DSN'), getenv('HU02_PRUEBA_USUARIO') ?: 'root', getenv('HU02_PRUEBA_CLAVE') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $base = 'prueba_t5_decision_' . bin2hex(random_bytes(6));
        try {
            $c->exec(str_replace('sistema_inventario', $base, file_get_contents(__DIR__ . '/../../database/schema.sql')));
            $filas = [];
            foreach ([['ENTRADA', '1', true, '11.00'], ['SALIDA', '11', false, '10.00'], ['PENDIENTE', '11', true, '10.00'], ['OTRO', '1', false, '10.00']] as [$tipo, $cantidad, $aceptada, $saldo]) {
                $c->exec("INSERT INTO materias_primas(nombre,unidad_medida,cantidad_disponible) VALUES ('Decision','g',10)");
                $id = (int) $c->lastInsertId();
                $resultado = true;
                try {
                    registrarMovimiento($c, ['materia_prima_id' => $id, 'tipo' => $tipo, 'cantidad' => $cantidad, 'observacion' => 'Tabla de decisión']);
                } catch (InvalidArgumentException | DomainException $error) {
                    $resultado = false;
                }
                $actual = $c->query('SELECT cantidad_disponible FROM materias_primas WHERE id=' . $id)->fetchColumn();
                self::assertSame($aceptada, $resultado);
                self::assertSame($saldo, $actual);
                $filas[] = compact('tipo', 'cantidad', 'aceptada', 'saldo', 'resultado', 'actual');
            }
            file_put_contents(__DIR__ . '/resultados/decision.json', json_encode($filas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } finally {
            $c->exec('DROP DATABASE IF EXISTS ' . $base);
        }
    }

    public function testReglasTransaccionesConcurrenciaYMigracion(): void
    {
        // La suite original usa globals; incluir en el ámbito global de esos nombres.
        global $numeroPruebas;
        ob_start();
        try {
            require __DIR__ . '/../../pruebas/hu02-integracion.php';
            $salida=ob_get_contents();
        } finally {
            ob_end_clean();
        }
        file_put_contents(__DIR__ . '/resultados/integracion.txt', $salida);
        self::assertSame(42,$numeroPruebas);
    }
}
