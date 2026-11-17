# Verificación de calidad

Resultados de las herramientas de la Definition of Done ejecutadas el 17 de noviembre de 2026 sobre la integración de los módulos completos con `develop` (commit base `30e837a`), con PHP 8.3.6 y MariaDB 10.11. Para que sirvan como evidencia del equipo, vuelva a ejecutarlas con los comandos del README y guarde la salida con la fecha.

| Verificación | Herramienta y versión | Comando | Resultado |
|---|---|---|---|
| Sintaxis PHP (DoD 2) | `php -l` | `php -l <archivo>` en cada `.php` | Ningún error |
| Formato PHP (DoD 4) | PHP CS Fixer 3.95 · PSR-12 | `vendor/bin/php-cs-fixer fix --dry-run --diff` | 0 de 61 archivos por corregir (se excluye `pruebas/version-anterior/`) |
| Formato JS y CSS (DoD 4) | Prettier 3.9 | `npx prettier "**/*.{js,mjs,html,css}" --check` | Todos los archivos conformes |
| Análisis estático | PHPStan 2.3 · nivel 6 | `vendor/bin/phpstan analyse --memory-limit=512M` | 0 errores |
| Pruebas automáticas | PHPUnit 9.6 | `vendor/bin/phpunit` | 49 pruebas y 153 aserciones, todas aprobadas |
| Complejidad ciclomática | PHPMD 3.0 · regla CyclomaticComplexity | `phpmd analyze --ruleset=codesize clases includes config` | Máximo por método: 8, en `GestorVentas::leerLineas` |
| Esquema reproducible (DoD 5) | MariaDB 10.11 | Importar `schema.sql`, o el `schema.sql` de develop (`30e837a`) más `migraciones/005-produccion-y-ventas.sql` | Ambos caminos dejan una estructura idéntica (columnas, llaves, índices y restricciones CHECK comparados); un material pendiente existente quedó como pendiente con su fecha de registro como llegada estimada |

## Observaciones abiertas

PHPMD, con sus umbrales por defecto, reporta dos avisos de tamaño de clase que no afectan el funcionamiento:

- `GestorProduccion` tiene una complejidad total de 53 (umbral 50).
- `GestorInventario` tiene 11 métodos públicos (umbral 10).

Antes de este informe ya se había separado `GestorPedidos` de `GestorInventario`, que tenía 27 métodos y una complejidad total de 70. Si el plan de calidad del equipo fija un umbral por clase, el siguiente paso sería separar de `GestorProduccion` la revisión de materiales (`analizarPlanesPendientes` y `resumirMaterialesPorFecha`).

## Pruebas en navegador

Los flujos principales se recorrieron en Chromium a 1280 px y 390 px de ancho, con una cuenta creada con `herramientas/crear-usuario.php`: redirección al inicio de sesión sin cuenta, contraseña equivocada, cierre de sesión, venta desde el celular, salida rechazada, entrada, recepción de pedido, registro de producción, producción rechazada, creación y validación de recetas, plan con fecha pasada, anulación con confirmación, eliminación bloqueada y búsqueda sin tildes. No hubo errores de JavaScript en la consola. Los casos manuales equivalentes están en [`casos-de-prueba.md`](casos-de-prueba.md).
