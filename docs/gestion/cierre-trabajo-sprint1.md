# Trabajo pendiente de Sprint 1

Seguimiento del 9 de octubre de 2026, America/Bogota. Preparado con apoyo de Codex por solicitud de Samuel. Reparto operativo registrado hoy; no es un acuerdo atribuido a fechas anteriores. Horas adicionales y fecha de terminación: pendientes de confirmar.

El periodo de Sprint 1 ya está cerrado en Jira. HU01, HU02 y HU03 quedaron pendientes y se arrastraron a Sprint 2. Completar ese trabajo no cambia el cierre histórico.

## Estado comprobado

| Trabajo | Estado | Qué impide terminar |
|---|---|---|
| HU01 / SCRUM-9 | En revisión; código integrado | D-01 y D-02 abiertos; validación posterior y DoD sin completar |
| HU02 / SCRUM-10 | En curso; PR #8 abierta, fuera de borrador | Pruebas de Julian publicadas; falta revisión formal e integración |
| HU03 / SCRUM-11 | Por hacer | Sin rama ni implementación de recetas |
| D-01 / SCRUM-21 | Por hacer; Julian | Falta control de acceso del alta |
| D-02 / SCRUM-22 | Por hacer; Samuel | PR #11 propuesta y probada por el asistente; falta revisión de Julian e integración |

No se ejecutaron pruebas nuevas del producto durante este seguimiento. No se cierran historias ni defectos con esta organización.

## Orden y responsables

1. **Julian revisa la corrección D-02 (PR #11).** Reproducir el procedimiento publicado en una base aislada. Comprobar que el alta sin token válido se rechaza sin escritura y que un alta válida sigue funcionando. Guardar fecha, versión, entorno y salida real; indicar bloqueantes o aprobar formalmente si procede. El reporte HU02 no sustituye esta comprobación.
2. **Julian implementa D-01 (SCRUM-21).** Crear una rama desde develop actualizado. Implementar control de acceso básico del alta conforme a CA6/RQ02, sin ampliar a permisos complejos ni nuevas funciones. El caso CP-13 debe rechazar una solicitud sin autorización con 403 y sin escribir; el uso autorizado debe seguir funcionando. Versionar cambios SQL si aplican y dejar procedimiento reproducible, evidencia y PR.
3. **Samuel revisa el cambio de Julian para D-01.** Ejecutar los casos en un entorno aislado y registrar resultados propios. Revisar los criterios y los demás puntos aplicables de la DoD. Si hay problemas, devolverlos como bloqueantes; aprobar solo después de comprobarlos.
4. **Samuel coordina la integración de las correcciones de HU01.** Integrar solo cambios aprobados por el integrante distinto del autor. Actualizar las ramas, resolver cualquier conflicto y comprobar el resultado integrado. Julian ejecuta el caso HU01 y documenta la validación posterior; NC-01 conserva su fecha y no se convierte en una aprobación previa.
5. **Julian completa la revisión formal de HU02 (PR #8).** Su reporte del 09/10 ya existe: no hay que reconstruirlo ni repetirlo solo para producir más evidencia. Contrastar los criterios CA1-CA7, verificar la migración y demás DoD aplicable. Si cambia el código probado por ajustes o integración, repetir las comprobaciones afectadas sobre la nueva versión y registrar los resultados.
6. **Samuel integra HU02 aprobada.** Registrar el commit integrado y comprobar que el flujo inventario/movimientos funciona junto con las correcciones de HU01. Cerrar SCRUM-10 solo si cumple la DoD.
7. **Julian desarrolla HU03 cuando haya capacidad en el tablero.** Responsable asignado hoy: Julian. Mantener Por hacer mientras En curso esté ocupada. Comprobar la DoR; crear rama desde develop actualizado; implementar registro y consulta de recetas con ingredientes, cantidades y unidades. Conservar los 5 puntos de historia existentes; no convertirlos en horas.
8. **Samuel prueba y revisa HU03.** Caso existente de Jira y CP-15: registrar una receta con Harina = 500 g y Azúcar = 200 g por unidad; consultarla y comprobar ambos ingredientes y cantidades. Confirmar que registrar recetas no descuenta inventario: el descuento corresponde a producción. Registrar resultado real y revisar la DoD antes de aprobar el PR.

Las correcciones y revisiones de HU01 son trabajo para terminar esa historia, no justifican abrir más historias simultáneas. En revisión está ocupada por HU01: atenderla antes de trasladar HU02 a esa columna. Mantener máximo una tarjeta En curso y una En revisión.

## Qué guardar en GitHub

Cada cambio de código se entrega en su propia rama/PR hacia develop, con:
- enlace a la tarjeta de Jira;
- problema y comportamiento esperado;
- instrucciones para repetir la prueba;
- resultados reales, fecha, versión y entorno;
- revisión del integrante distinto del autor;
- cambios de base de datos reproducibles cuando correspondan;
- documentación y formato actualizados, sin credenciales.

La evidencia de pruebas no equivale a una aprobación de código. Si el cambio ya integrado falla, corregirlo mediante otra PR. No aprobar retroactivamente PR #5 ni rehacer el historial.

Al completar el bloque, promover develop a main mediante revisión de la versión estable según ESTANDARES.md. Tener todo solo en ramas de propuesta no acredita terminación.

## Qué registrar en Jira

- SCRUM-9: Samuel responsable; Julian verifica HU01. Conservar NC-01 y enlazar las nuevas correcciones y comprobaciones.
- SCRUM-10: Samuel responsable; Julian revisor. Enlazar la evidencia publicada del 09/10 y la aprobación/integración cuando existan.
- SCRUM-11: Julian responsable; Samuel revisor. Sigue Por hacer hasta cumplir DoR y disponer de capacidad.
- SCRUM-21: Julian implementa, Samuel revisa.
- SCRUM-22: Samuel responsable de la propuesta existente; Julian reproduce y revisa.

Estados: En curso cuando realmente se trabaja; En revisión con evidencias y capacidad disponible; Finalizada solo con criterios, DoD, aprobación e integración cumplidos. El estado de los defectos debe corresponder a la comprobación de la corrección, no solo a publicar una propuesta.

## Condición para dar por terminado el trabajo arrastrado

HU01, HU02 y HU03 deben cumplir sus criterios y DoD aplicable, con resultados del otro integrante, revisiones e integración verificables. D-01 y D-02 deben estar corregidos y comprobados. O1 se contrasta con su definición en el Acta antes de declararlo cumplido.

Completar estas tres historias no cierra automáticamente SCRUM-23 ni termina toda la revisión del Taller 5. Fechas y resultados posteriores se incorporan al avance con su evidencia, sin modificar mediciones históricas.

## Enlaces

- [Tablero Jira](https://sistema-inventario-produccion.atlassian.net/jira/software/projects/SCRUM/boards/1)
- [HU01 / SCRUM-9](https://sistema-inventario-produccion.atlassian.net/browse/SCRUM-9)
- [HU02 / SCRUM-10](https://sistema-inventario-produccion.atlassian.net/browse/SCRUM-10)
- [HU03 / SCRUM-11](https://sistema-inventario-produccion.atlassian.net/browse/SCRUM-11)
- [D-01 / SCRUM-21](https://sistema-inventario-produccion.atlassian.net/browse/SCRUM-21)
- [D-02 / SCRUM-22](https://sistema-inventario-produccion.atlassian.net/browse/SCRUM-22)
- [PR #8 HU02](https://github.com/Samge34c/sistema-inventario-produccion/pull/8)
- [PR #11 D-02](https://github.com/Samge34c/sistema-inventario-produccion/pull/11)
- [Procedimiento D-02](https://github.com/Samge34c/sistema-inventario-produccion/blob/fix/scrum22-proteccion-alta/docs/pruebas/scrum22-proteccion-alta.md)
- [Reporte HU02 de Julian](https://github.com/Samge34c/sistema-inventario-produccion/blob/3d96f4afb1640b66f3a8ef08c4a543e5e432490b/docs/pruebas/evidencias/julian-hu02-2026-10-09.md)
