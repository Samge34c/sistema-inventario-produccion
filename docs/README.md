# Índice de documentación del proyecto

Fecha de corte: 9 de octubre de 2026, America/Bogota.

## Avance y gestión

- [Trabajo pendiente de Sprint 1 y reparto actual](gestion/cierre-trabajo-sprint1.md): pasos para HU01-HU03, D-01/D-02, revisiones de GitHub y estados de Jira; responsable de HU03 asignado a Julian el 09/10. Es planificación, no evidencia de terminación.
- [Avance consolidado](gestion/avance-consolidado.md): integra Acta, estándares y Taller 5; incluye decisiones, backlog, riesgos, calidad y responsabilidades.
- [Historial de transiciones de Jira](gestion/movimientos-jira.csv): 10 cambios reales de estado en seis ítems, dentro de semana 9; tres son HU01-HU03 y aportan siete cambios. Inicio de semana 1 confirmado por Samuel: 04/08/2026. El corte llega a semana 10; semana 11 (13-19/10) sigue futura. No se inventan movimientos para ese periodo.
- [Estándares adoptados](../ESTANDARES.md).
- [Tablero Jira](https://sistema-inventario-produccion.atlassian.net/jira/software/projects/SCRUM/boards/1): WIP configurado el 09/10, máximo una tarjeta En curso y una En revisión. [Políticas en SCRUM-24](https://sistema-inventario-produccion.atlassian.net/browse/SCRUM-24). Invitación docente registrada; aceptación y acceso efectivo pendientes. Adopción de la distribución 1 + 1 por Julian pendiente.

Las versiones fuente del Acta del 20/09 y del PDF de calidad del 07/10 fueron aportadas por Samuel. El consolidado identifica esos documentos; no se afirma que sus PDF estén publicados en esta rama.

## Pruebas y calidad

- [Caso HU01](pruebas/hu01-materias-primas.md).
- [Procedimiento HU02](https://github.com/Samge34c/sistema-inventario-produccion/blob/feat/hu02-movimientos/docs/pruebas/hu02-movimientos.md).
- [Reporte de ejecución independiente de Julian, 09/10](https://github.com/Samge34c/sistema-inventario-produccion/blob/3d96f4afb1640b66f3a8ef08c4a543e5e432490b/docs/pruebas/evidencias/julian-hu02-2026-10-09.md): 42 comprobaciones de integración y 15 HTTP reportadas correctas; versión probada 7a01251. [Seguimiento en SCRUM-23](https://sistema-inventario-produccion.atlassian.net/browse/SCRUM-23). No cierra D-01/D-02 ni sustituye aprobación formal.
- [README para reproducir Taller 5](https://github.com/Samge34c/sistema-inventario-produccion/blob/docs/taller5-calidad/docs/calidad/taller5/2026-10-07/README.md).
- [Reporte de análisis estático](https://github.com/Samge34c/sistema-inventario-produccion/blob/75d97605c2bfb7c76e3041f426060341f552aa93/docs/calidad/taller5/2026-10-07/REPORTE_ANALISIS_ESTATICO.md).
- [Resultados y procedimiento SCRUM-22](https://github.com/Samge34c/sistema-inventario-produccion/blob/fix/scrum22-proteccion-alta/docs/pruebas/scrum22-proteccion-alta.md).

## Propuestas abiertas

| PR | Tema | Qué falta |
|---|---|---|
| [#7](https://github.com/Samge34c/sistema-inventario-produccion/pull/7) | Registro previo de gestión | Revisión; su información histórica se actualiza en el consolidado |
| [#8](https://github.com/Samge34c/sistema-inventario-produccion/pull/8) | Movimientos HU02 | Reporte de Julian publicado; faltan revisión formal e integración |
| [#9](https://github.com/Samge34c/sistema-inventario-produccion/pull/9) | Formato PHP | Revisión e integración |
| [#10](https://github.com/Samge34c/sistema-inventario-produccion/pull/10) | Taller 5 | Reproducción HU02 publicada aparte; falta contraste del resto, avisos y metas propuestas |
| [#11](https://github.com/Samge34c/sistema-inventario-produccion/pull/11) | Corrección SCRUM-22 | Reproducción y revisión de Julian |
| [#12](https://github.com/Samge34c/sistema-inventario-produccion/pull/12) | Avance consolidado y navegación | Revisión del otro integrante |

## Cómo interpretar las ramas

`main` conserva la versión estable anterior; `develop` integra el trabajo aprobado. Una rama o PR abierta contiene una propuesta y no acredita que la historia esté terminada. Los enlaces de este índice indican la rama correcta para evitar ejecutar una versión anterior.

La organización añade navegación sin borrar documentos ni mover rutas de código o evidencias existentes.

## Preparar la sustentación

Guion propuesto, pendiente de ensayo con cronómetro:

| Tiempo | Integrante | Contenido |
|---|---|---|
| 0:00-1:00 | Samuel | Acta, estándares y calidad; 12 HU, 60 SP y tecnología PHP |
| 1:00-2:00 | Samuel | O1 venció el 04/10; arrastre detectado el 05/10; respuesta y límites actuales del tablero |
| 2:00-3:30 | Julian | HU02: versión probada, 42 comprobaciones de integración y 15 HTTP; distinguir reporte de aprobación |
| 3:30-4:30 | Julian | Fallos históricos D-01/D-02, métricas del 07/10 y riesgos vigentes |
| 4:30-5:00 | Samuel | Próximos responsables, dependencia de recetas y evidencia necesaria para cerrar O1 |

Antes de radicar: comprobar acceso del docente, revisar los enlaces desde otra sesión, confirmar responsables pendientes, actualizar cualquier cambio posterior al corte y ensayar entre ambos. El avance es un informe de gestión; el cierre de O1 no es condición para describir con honestidad su estado abierto.
