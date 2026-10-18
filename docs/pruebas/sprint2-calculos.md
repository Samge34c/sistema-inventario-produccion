# Sprint 2: producción máxima, recetas posibles y proyección

Fecha de comprobación: 18 de octubre de 2026, Villavicencio (UTC−5).

## Alcance y dependencia

- HU04 / SCRUM-12: calcular unidades completas y todos los ingredientes que limitan la producción, usando solo las existencias actuales.
- HU05 / SCRUM-13: distinguir recetas posibles y no posibles para al menos una unidad. Cada receta se evalúa por separado: no se reservan ingredientes ni se promete producir toda la lista a la vez.
- HU06 / SCRUM-14: mostrar producción actual y proyección suponiendo la llegada de todos los materiales pendientes registrados. Las cantidades se suman por materia prima y se muestran separadas. La consulta no recibe materiales, consume existencias ni altera el historial.

La propuesta depende del acceso, inventario y recetas de [PR #13](https://github.com/Samge34c/sistema-inventario-produccion/pull/13), versión `1db063fc148c30fb3e245213b1b4e84bda0b3450`. La rama incorpora esa dependencia para comprobar el flujo; no afirma que esté aprobada o integrada. La PR de cálculos se compara contra esa rama y deberá pasar a `develop` después de resolver su dependencia.

Se mantiene PHP/PDO y la interfaz Bootstrap, sin framework backend nuevo. No hay cambios de esquema adicionales: se reutilizan las tablas versionadas de inventario, recetas y pendientes. Las cantidades de receta corresponden a una unidad y usan la unidad de su materia prima, sin conversiones implícitas. Una receta antigua de varias unidades requiere revisión antes de calcular.

## Uso

Después de preparar la instalación y crear la cuenta siguiendo README, iniciar sesión y abrir los enlaces de Inicio:

1. **Producción máxima**: seleccionar receta y pulsar Calcular. Se muestran unidades completas, ingredientes limitantes y detalle de cantidades.
2. **Recetas posibles**: consultar cada receta, su viabilidad y máximo de unidades. Una receta sin ingredientes o con datos inválidos se señala para revisión y no bloquea las demás.
3. **Proyección con pendientes**: seleccionar receta y pulsar Proyectar. Se muestran ambos resultados y los limitantes de cada escenario. No hay fecha estimada de llegada: se usan todos los pendientes registrados como hipótesis.

Las consultas necesitan una cuenta activa. Son lecturas: no introducen acciones de recepción o descuento, que pertenecen a otras historias.

## Ejemplos originales de Jira y resultados

| Historia | Entrada | Esperado y comprobado |
|---|---|---|
| HU04 | Harina 5000 g, Azúcar 1000 g; receta 500 g y 200 g por unidad | 5 unidades; Azúcar limitante |
| HU05 | Harina 1000 g; Receta A requiere 500 g, B requiere 1200 g | A posible (2); B no posible (0) |
| HU06 | Harina actual 1000 g, pendientes 200 + 300 g; receta 500 g por unidad | Actual 2; proyección 3; saldo conservado |

Se comprobaron además stock cero, existencia exacta para una unidad, redondeo hacia abajo, empate entre ingredientes, 0,30/0,10, recetas inexistentes/vacías/antiguas, cantidades inválidas, pendientes ajenos a la receta, suma de varias partidas y cambio del ingrediente limitante en la proyección. Los cálculos usan enteros en centésimas, con validación de rango, evitando errores de coma flotante. Las lecturas relacionadas utilizan una misma transacción de lectura repetible.

## Ejecución y evidencia

Salida original: [resultado-2026-10-18.json](sprint2/resultado-2026-10-18.json). Incluye inicio/fin, entorno, huellas SHA-256 de los archivos, salidas de suites y registro del servidor.

| Grupo | Resultado |
|---|---|
| Integración de HU04–HU06 | 28 comprobaciones correctas |
| Navegador de HU04–HU06 | 15 comprobaciones correctas |
| Regresión: integración PHP HU02 | 42 comprobaciones correctas |
| Regresión: HTTP HU02 | 15 comprobaciones correctas |
| Regresión: navegador HU02 | 18 comprobaciones correctas |
| Regresión: navegador recetas | 6 comprobaciones correctas |
| Preparador conjunto | 42 controles correctos; incluye resultados de suites |

Los controles del preparador no se suman como casos independientes de las suites. No sustituyen los 15 casos históricos del Taller 5 ni cambian sus resultados del 07/10.

Entorno medido: PHP 8.3.6, MariaDB 10.11.14, Python 3.12.14, Chromium Headless Shell 131.0.6778.204 y Playwright 1.62.1. Navegador en escritorio y 390 px; sin errores de consola en los flujos válidos y sin desbordamiento horizontal del documento en la proyección móvil. Bootstrap 5.3.8 servido como recurso local de prueba; no se certifica disponibilidad del CDN, teléfono físico ni MySQL 8.

Sintaxis de los siete PHP nuevos/modificados del cálculo y entrada: correcta. PHP CS Fixer con PSR-12 y Prettier: sin cambios propuestos después de aplicar formato.

Se utilizó una instancia local nueva, base con nombre aleatorio y copia temporal. Antes de los envíos HTTP del preparador se comprueba la base y la huella de conexión. La suite de cálculos exige el nombre temporal del preparador antes de crear datos ficticios. Se comprobó que consultar varias veces conserva inventario, movimientos, pendientes y recetas. La base temporal fue eliminada. No se utilizaron datos del negocio ni se publican credenciales.

## Reproducción

Seguir los requisitos y variables locales del [procedimiento de recuperación](recuperacion-sprint1-2026-10-12.md). PHP debe tener PDO MySQL y mbstring; se requiere un servidor MySQL/MariaDB local, Node, Python y Playwright. Usar una salida nueva, sin sobrescribir evidencias:

```powershell
npm ci
npx playwright install chromium
$env:RECUPERACION_SERVIDOR_DSN = "mysql:host=127.0.0.1;port=3306;charset=utf8mb4"
$env:RECUPERACION_NAVEGADOR = "1"
$env:RECUPERACION_PRODUCCION = "1"
python pruebas/recuperacion-sprint1.py --salida docs/pruebas/sprint2/resultado-nuevo.json
```

Configurar usuario/contraseña de base solo en las variables locales descritas en el procedimiento. No apuntar a la base habitual ni ejecutar las suites directamente con datos del negocio. El preparador crea su base y copia temporal; no se borra la base habitual.

## Revisión y cierre

Implementación y comprobaciones automatizadas publicables; faltan reproducción y revisión por el otro integrante, aprobación y comprobación de la versión integrada. Los resultados no se atribuyen a una ejecución personal de Julián o Samuel. No se marca Finalizada ninguna historia ni se cierra el Sprint 2 por disponer de código en una propuesta. Se conserva el arrastre de Sprint 1, las fechas y NC-01.
