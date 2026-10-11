# Revisión técnica de HU02 junto con SCRUM-22 — 11 de octubre de 2026

## Alcance de la revisión

Esta evidencia recoge comprobaciones automatizadas y no sustituye la revisión del equipo. Se comprobó una combinación local y temporal de las propuestas de HU02 (PR #8) y protección del alta de materia prima (SCRUM-22, PR #11). Ninguna de estas propuestas se integró en `develop` mediante esta revisión.

Fuentes verificadas:

- HU02: `3d96f4afb1640b66f3a8ef08c4a543e5e432490b`. Su código de producto coincide con `7a01251325365c8d2ebbead5b89d5732671f1463`; los cuatro archivos agregados después son evidencias de Julián.
- SCRUM-22: `5e5c3339f90801dddcf28f5d232ed8c89163afd6`. Se contrastaron los archivos de la propuesta remota con la copia local utilizada. La combinación se aplicó sin conflictos.
- Las correcciones de esta revisión modifican los dos clientes de prueba HTTP, no el código PHP del producto.

No se modifican las mediciones históricas del Taller 5 ni las evidencias independientes publicadas por Julián el 9 de octubre. Esta ejecución tampoco completa HU03 ni resuelve D01 (autorización).

## Problema encontrado y corrección

Los dos clientes HTTP creaban la materia prima mediante una solicitud directa a `guardar.php`. Al combinar HU02 con SCRUM-22, el alta exige el token del formulario y la cookie de su sesión. Los clientes no enviaban ese token y fallaban en la primera comprobación: el servidor respondía 403 y rechazaba el alta. La protección nueva estaba funcionando; faltaba adaptar los clientes.

Se actualizan `pruebas/hu02-http.py` y `docs/pruebas/evidencias/hu02-http.js` para abrir primero el formulario, conservar la sesión y enviar el token cuando esté presente. Así funcionan tanto sobre HU02 como sobre su combinación con SCRUM-22. Se conservan las 15 comprobaciones de cada cliente y los rechazos de solicitudes inválidas.

## Resultados y respaldo

Las horas corresponden a Bogotá (UTC−5). Primera fase: 16:20:07–16:20:11. Segunda fase: 16:20:50–16:20:52 del 11/10/2026.

| Grupo | Resultado | Salida conservada |
| --- | --- | --- |
| Integración PHP de HU02 | 42 comprobaciones correctas, incluidas concurrencia, reversión y migración | [Integración PHP](evidencias/revision-2026-10-11/integracion-php.txt) |
| HTTP Python, antes de adaptar el cliente | Falló en el alta inicial | [Fallo Python](evidencias/revision-2026-10-11/http-python-original.txt) |
| HTTP Node, antes de adaptar el cliente | Falló en el alta inicial | [Fallo Node](evidencias/revision-2026-10-11/http-node-original.txt) |
| SCRUM-22 sobre la combinación | 7 casos correctos; los cinco intentos sin token válido se rechazaron sin agregar registros | [Casos SCRUM-22](evidencias/revision-2026-10-11/scrum22-http.json) |
| Flujo con navegador automatizado | 18 comprobaciones correctas; sin excepciones JavaScript ni errores de consola en ese flujo | [Navegador](evidencias/revision-2026-10-11/navegador.txt) |
| HTTP Python, cliente corregido | 15 comprobaciones correctas | [HTTP Python](evidencias/revision-2026-10-11/http-python-corregido.txt) |
| HTTP Node, cliente corregido | 15 comprobaciones correctas | [HTTP Node](evidencias/revision-2026-10-11/http-node-corregido.txt) |

Los dos clientes HTTP comprueban los mismos escenarios; no representan 30 casos distintos. Tampoco se suman sus comprobaciones a los casos del Taller 5. Los fallos de los clientes originales se conservan como fallos observados, no como pruebas funcionales aprobadas.

Los resúmenes [antes](evidencias/revision-2026-10-11/resumen-antes.json) y [después](evidencias/revision-2026-10-11/resumen-despues.json) incluyen horas, versiones y huellas SHA-256 de los PHP del inventario. Estas huellas coinciden entre las fases: solo se corrigieron los clientes. En la primera fase, `codigo_esperado: 1` indica que el ejecutor esperaba reproducir el fallo del cliente; no significa que el escenario funcional pasara.

Comprobaciones adicionales: los 12 PHP de la combinación pasaron revisión de sintaxis; PHP CS Fixer 3.95.27 con PSR-12 no propuso cambios en los 9 PHP del inventario; el cliente Node corregido pasó Prettier y revisión de sintaxis; el cliente Python corregido pasó compilación de sintaxis. Se retiró una línea vacía final de la copia temporal del script de SCRUM-22. Al contrastarla, el script remoto ya tenía el contenido limpio: no fue necesario cambiar su lógica ni su contenido.

## Entorno y límites

PHP 8.3.6, MariaDB 10.11.14, Node 24.19.0 y Python 3.12.14. Cada fase utilizó una instancia temporal de MariaDB y una base desechable con nombre `prueba_revision_sprint1_…`; no se utilizó la base habitual del proyecto. Se detuvieron los servicios y se eliminaron esas bases al terminar.

El navegador usó Bootstrap 5.3.8 servido como recurso local de prueba. No se verificó disponibilidad del CDN. Esta ejecución no comprueba MySQL 8, datos reales, autorización general ni todos los flujos del sistema. El navegador se utilizó mediante un script automatizado; no se presenta como revisión manual del equipo.

## Cómo reproducir y qué falta

1. Obtener la rama actualizada de PR #8 y combinarla localmente, sin publicar una integración, con la propuesta de PR #11. Conservar los identificadores de los commits utilizados.
2. Preparar un servidor local y una base desechable siguiendo [el procedimiento de HU02](hu02-movimientos.md) y [el de SCRUM-22](https://github.com/Samge34c/sistema-inventario-produccion/blob/5e5c3339f90801dddcf28f5d232ed8c89163afd6/docs/pruebas/scrum22-proteccion-alta.md). Evitar la base habitual. Los scripts de prueba modifican datos ficticios de esa base temporal.
3. Ejecutar el script de integración PHP de HU02, los clientes HTTP Python y Node, el script HTTP de SCRUM-22 y el flujo de navegador de HU02. Guardar la salida original y las versiones utilizadas.
4. Comparar los resultados y el código con los criterios de SCRUM-10 y SCRUM-22. El otro integrante debe registrar su revisión y, si corresponde, aprobar las propuestas en GitHub. Esta evidencia no sustituye ese paso de la definición de terminado.

Pendientes de cierre: aprobación humana de PR #8 y PR #11, corrección y comprobación de D01 (SCRUM-21), implementación y pruebas de HU03 (SCRUM-11) e integración de los cambios que cumplan los criterios del equipo. Los estados de Jira se mantienen hasta cumplir esos pasos; el Sprint 1 cerrado no se modifica ni se reabre por esta revisión.
