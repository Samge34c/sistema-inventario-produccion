# Taller 5 — Reporte de análisis estático

Fecha: 07/10/2026 (America/Bogota). Código: **7a01251325365c8d2ebbead5b89d5732671f1463** de HU02, PR #8. No certifica develop ni módulos futuros.

## PHPStan 2.3.0

PHP 8.3.6, nivel 5, sin baseline ni supresión. Alcance: 11 PHP en config, modules/inventario e index.php; vendor y pruebas excluidos.

```bash
php herramientas-calidad/vendor/bin/phpstan analyse --level=5 --no-progress --error-format=prettyJson config modules/inventario index.php > phpstan.json
```

**11 hallazgos; exit 1.** `totals.errors=0` son errores generales de herramienta; `totals.file_errors=11` son hallazgos en archivos. El análisis no está limpio.

| Archivo | Líneas | Hallazgo | Cantidad |
|---|---|---|---:|
| editar.php | 19, 46 | conexion podría no estar definida | 2 |
| eliminar.php | 19 | Mismo aviso | 1 |
| guardar-movimiento.php | 27 | Mismo aviso | 1 |
| guardar.php | 31 | Mismo aviso | 1 |
| index.php del módulo | 10 | Mismo aviso | 1 |
| movimientos.php | 16, 17, 19 | Mismo aviso | 3 |
| movimientos-funciones.php | 32 | Comparación int === null siempre falsa | 1 |
| movimientos-funciones.php | 107 | Condición siempre falsa | 1 |

Los nueve avisos de conexion requieren revisar contrato de conexión y alcance de require: las pruebas de flujo no reprodujeron una variable indefinida. No equivalen automáticamente a nueve defectos funcionales. Las dos condiciones requieren inspección y simplificación. No ocultar avisos para obtener un cero. Julian: revisar contrato e inferencia; Samuel: verificar regresión (responsabilidades propuestas conforme al Acta).

## PHP Depend 3.0.0

```bash
php herramientas-calidad/vendor/bin/pdepend --summary-xml=pdepend.xml modules/inventario
```

13 funciones. Se usa **CCN2**, con operadores booleanos, distinto de CCN simple. Máximo **13** en registrarMovimiento; actualizarMateriaPrimaProtegida tiene **11**. Objetivo propuesto **≤10 por función** (PHPMD considera 11+ muy alta). Separar validaciones y cálculo en funciones coherentes conservando la transacción; repetir pruebas. No se cambió funcionalidad para mejorar artificialmente esta línea base.

## Evidencia complementaria

- PHPUnit 11.5.57 + PCOV 1.0.11: **113/117 líneas = 96,58 %**, solo funciones.php y movimientos-funciones.php. No cubre todo el producto ni mide ramas.
- 8 pruebas y 45 aserciones PHPUnit; la suite de integración añade 42 comprobaciones propias. HTTP: 15; navegador: 18 (Bootstrap 5.3.8 local en prueba; producto conserva CDN).
- 12 casos funcionales del plan aprobados; CP-13/14 de calidad fallidos. No sumar casos y aserciones como unidades iguales.
- Defectos confirmados abiertos: D-01 alta HU01 sin control de acceso; D-02 alta HU01 sin validación CSRF. Dos causas distintas; no duplicar por endpoint.
- Origin ajeno + cookie prueba aceptación del POST en servidor; no acredita explotación en navegador autenticado ni evalúa SameSite.

Medición técnica del asistente autorizado por Samuel. No sustituye ejecución independiente de Julian, review formal ni aceptación de Diana Marcela Caicedo. Reportes JSON/XML y logs acompañan este documento.
