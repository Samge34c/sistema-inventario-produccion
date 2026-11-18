# Verificación de calidad

Resultados de las herramientas de la Definition of Done ejecutadas el 17 de noviembre de 2026 sobre la integración de los módulos completos con `develop` (commit base `30e837a`), con PHP 8.3.6 y MariaDB 10.11. Para que sirvan como evidencia del equipo, vuelva a ejecutarlas con los comandos del README y guarde la salida con la fecha.

| Verificación | Herramienta y versión | Comando | Resultado |
|---|---|---|---|
| Sintaxis PHP (DoD 2) | `php -l` | `php -l <archivo>` en cada `.php` | Ningún error |
| Formato PHP (DoD 4) | PHP CS Fixer 3.95 · PSR-12 | `vendor/bin/php-cs-fixer fix --dry-run --diff` | 0 de 61 archivos por corregir (se excluye `pruebas/version-anterior/`) |
| Formato JS y CSS (DoD 4) | Prettier 3.9 | `npx prettier "**/*.{js,mjs,html,css}" --check` | Todos los archivos conformes |
| Análisis estático | PHPStan 2.3 · nivel 6 | `vendor/bin/phpstan analyse --memory-limit=512M` | 0 errores |
| Pruebas automáticas | PHPUnit 9.6 | `vendor/bin/phpunit` | 49 pruebas y 153 aserciones, todas aprobadas |
| Cobertura de las pruebas (18/11) | PHPUnit 9.6 con PCOV 1.0.11 | `composer cobertura` | 68,3 % de las líneas de `clases/` e `includes/` (872 de 1.277); 80,5 % en `clases/` |
| Complejidad ciclomática | PHPMD 3.0 · regla CyclomaticComplexity | `phpmd analyze --ruleset=codesize clases includes config` | Máximo por método: 8, en `GestorVentas::leerLineas` |
| Esquema reproducible (DoD 5) | MariaDB 10.11 | Importar `schema.sql`, o el `schema.sql` de develop (`30e837a`) más `migraciones/005-produccion-y-ventas.sql` | Ambos caminos dejan una estructura idéntica (columnas, llaves, índices y restricciones CHECK comparados); un material pendiente existente quedó como pendiente con su fecha de registro como llegada estimada |

## Cobertura de las pruebas

Medida el 18 de noviembre de 2026 con PHPUnit 9.6 y PCOV 1.0.11, el equivalente en PHP de JaCoCo: dice qué líneas ejecutan las pruebas automáticas y cuáles no. Se mide sobre `clases/` e `includes/`, y GitHub Actions la repite en cada PR con el mismo filtro.

| Archivo | Líneas que ejecutan las pruebas | Cobertura |
|---|---|---|
| `clases/BaseDatos.php` | 16 de 16 | 100 % |
| `clases/CalculadoraProduccion.php` | 75 de 76 | 98,7 % |
| `clases/ErrorValidacion.php` | 2 de 2 | 100 % |
| `clases/GestorInventario.php` | 118 de 203 | 58,1 % |
| `clases/GestorPedidos.php` | 76 de 108 | 70,4 % |
| `clases/GestorProduccion.php` | 202 de 231 | 87,4 % |
| `clases/GestorRecetas.php` | 127 de 137 | 92,7 % |
| `clases/GestorVentas.php` | 158 de 190 | 83,2 % |
| `clases/Validador.php` | 50 de 60 | 83,3 % |
| `includes/` (5 archivos) | 48 de 254 | 18,9 % |
| Total | 872 de 1.277 | 68,3 % |

En `includes/` la cobertura es baja porque son la plantilla de las páginas (encabezado, pie y arranque) y funciones de formato que solo usan las páginas. Las páginas de `modules/` no las ejecutan las pruebas automáticas; se verifican con los casos de `docs/casos-de-prueba.md` y el recorrido en navegador.

Diez métodos públicos no los ejecuta ninguna prueba, pero todos los usa alguna página: `listarMateriasPrimas`, `listarMateriasEnAlerta`, `registrarMovimientoDesdeFormulario` y `listarMovimientos` (inventario); `listarPedidos`, `obtenerPedido` y `cancelarPedido` (pedidos); `listarUltimasProduccionesDeReceta` (producción), y `listarVentasDelDia` y `calcularPromedioDiarioPorProducto` (ventas). Tampoco se ejecutan dos métodos privados, `construirFiltrosMovimientos` y `obtenerPlanPendiente`, que usan otros métodos de su misma clase.

Código muerto: de los métodos sin cobertura, uno no lo usaba ninguna parte del sistema, `GestorPedidos::listarPedidosAtrasados`, porque el panel de inicio filtra los pedidos atrasados por su cuenta. Se quitó el 18 de noviembre. Los métodos privados sin uso y el código inalcanzable los revisa PHPStan, que da 0 errores.

## Observaciones abiertas

PHPMD, con sus umbrales por defecto, reporta dos avisos de tamaño de clase que no afectan el funcionamiento:

- `GestorProduccion` tiene una complejidad total de 53 (umbral 50).
- `GestorInventario` tiene 11 métodos públicos (umbral 10).

Antes de este informe ya se había separado `GestorPedidos` de `GestorInventario`, que tenía 27 métodos y una complejidad total de 70. Si el plan de calidad del equipo fija un umbral por clase, el siguiente paso sería separar de `GestorProduccion` la revisión de materiales (`analizarPlanesPendientes` y `resumirMaterialesPorFecha`).

## Pruebas en navegador

Los flujos principales se recorrieron en Chromium a 1280 px y 390 px de ancho, con una cuenta creada con `herramientas/crear-usuario.php`: redirección al inicio de sesión sin cuenta, contraseña equivocada, cierre de sesión, venta desde el celular, salida rechazada, entrada, recepción de pedido, registro de producción, producción rechazada, creación y validación de recetas, plan con fecha pasada, anulación con confirmación, eliminación bloqueada y búsqueda sin tildes. No hubo errores de JavaScript en la consola. Los casos manuales equivalentes están en [`casos-de-prueba.md`](casos-de-prueba.md).
