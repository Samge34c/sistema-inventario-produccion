# Índice de documentación del proyecto

Fecha de corte: 8 de octubre de 2026, America/Bogota.

## Avance y gestión

- [Avance consolidado](gestion/avance-consolidado.md): integra Acta, estándares y Taller 5; incluye decisiones, backlog, riesgos, calidad y responsabilidades.
- [Historial de transiciones de Jira](gestion/movimientos-jira.csv): 10 cambios reales de estado, seis ítems distintos; tres son HU01-HU03, con siete cambios. No constituye todavía el conteo académico de semanas 6 a 11.
- [Estándares adoptados](../ESTANDARES.md).
- [Tablero Jira](https://sistema-inventario-produccion.atlassian.net/jira/software/projects/SCRUM/boards/1): requiere permisos del docente. WIP y políticas visibles pendientes.

Las versiones fuente del Acta del 20/09 y del PDF de calidad del 07/10 fueron aportadas por Samuel. El consolidado identifica esos documentos; no se afirma que sus PDF estén publicados en esta rama.

## Pruebas y calidad

- [Caso HU01](pruebas/hu01-materias-primas.md).
- [Procedimiento HU02](https://github.com/Samge34c/sistema-inventario-produccion/blob/feat/hu02-movimientos/docs/pruebas/hu02-movimientos.md).
- [README para reproducir Taller 5](https://github.com/Samge34c/sistema-inventario-produccion/blob/docs/taller5-calidad/docs/calidad/taller5/2026-10-07/README.md).
- [Reporte de análisis estático](https://github.com/Samge34c/sistema-inventario-produccion/blob/75d97605c2bfb7c76e3041f426060341f552aa93/docs/calidad/taller5/2026-10-07/REPORTE_ANALISIS_ESTATICO.md).
- [Resultados y procedimiento SCRUM-22](https://github.com/Samge34c/sistema-inventario-produccion/blob/fix/scrum22-proteccion-alta/docs/pruebas/scrum22-proteccion-alta.md).

## Propuestas abiertas

| PR | Tema | Qué falta |
|---|---|---|
| [#7](https://github.com/Samge34c/sistema-inventario-produccion/pull/7) | Registro previo de gestión | Revisión; su información histórica se actualiza en el consolidado |
| [#8](https://github.com/Samge34c/sistema-inventario-produccion/pull/8) | Movimientos HU02 | Reproducción de Julian, revisión e integración |
| [#9](https://github.com/Samge34c/sistema-inventario-produccion/pull/9) | Formato PHP | Revisión e integración |
| [#10](https://github.com/Samge34c/sistema-inventario-produccion/pull/10) | Taller 5 | Ejecución independiente y revisión de hallazgos/umbrales |
| [#11](https://github.com/Samge34c/sistema-inventario-produccion/pull/11) | Corrección SCRUM-22 | Reproducción y revisión de Julian |

## Cómo interpretar las ramas

`main` conserva la versión estable anterior; `develop` integra el trabajo aprobado. Una rama o PR abierta contiene una propuesta y no acredita que la historia esté terminada. Los enlaces de este índice indican la rama correcta para evitar ejecutar una versión anterior.

La organización añade navegación sin borrar documentos ni mover rutas de código o evidencias existentes.
