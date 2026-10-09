# Avance del proyecto

Corte: 8 de octubre de 2026, America/Bogota.

Avance del proyecto de inventario,
recetas y planificación de producción

Julian Camilo Caicedo Ramirez y
Samuel Esteban Riveros Martinez

Ingeniería de Sistemas
Universidad Santo Tomás

Gerencia de Software

Stefany Gomez Riveros

8 de octubre de 2026

## Tabla de contenido

Fecha de corte: 8 de octubre de 2026, hora de Bogotá. El documento presenta resultados comprobados y distingue los compromisos de recuperación de las actividades todavía pendientes.

## Consolidación de los talleres

El primer hito continúa abierto: materias primas tiene correcciones pendientes, movimientos espera revisión independiente y recetas aún no está implementado. El equipo acordó recuperar el trabajo al inicio del Sprint 2, conservando las fechas siguientes como metas sujetas a pruebas y revisión.

El Acta define el alcance; los estándares establecen cómo preparar y terminar cada historia; el Taller 5 transforma los requisitos de calidad en pruebas y mediciones. Se conservan como base las tres versiones aportadas para este avance (Caicedo Ramirez & Riveros Martinez, 2026a, 2026b, 2026c).

**Tabla 1. Relación entre los tres talleres**

| Fuente | Cómo condiciona el trabajo |
| --- | --- |
| Taller 3: Acta del 20/09 | Define 12 historias, cuatro sprints, roles, seis riesgos y requisitos RQ01-RQ05. No se amplía el alcance funcional. |
| Taller 4: estándares del 30/09 | Exige criterios reproducibles, nombres del dominio en español, formato automático y revisión por el otro integrante. |
| Taller 5: calidad del 07/10 | Mide HU01/HU02, identifica dos fallos altos y evita declarar terminadas historias que incumplen sus criterios. |

### Tecnología e interlocutora

Se mantiene PHP/PDO y esquema MySQL, HTML/Bootstrap para formularios y JavaScript en pruebas. Bootstrap es el framework de interfaz; no se ha adoptado uno backend PHP. Se probó MariaDB 10.11.14; MySQL 8 y el entorno final siguen por verificar. PHPStan analiza código; Prettier y PHP CS Fixer controlan formato.

Diana Marcela Caicedo, propietaria, es la interlocutora principal confirmada en el Acta. Su aceptación funcional está pendiente. GitHub conserva código y evidencia; Jira registra el trabajo.

## Control de cambios

Se conserva el compromiso original del Acta y se registran las acciones posteriores. La explicación del atraso y el acuerdo de recuperación fueron comunicados por Samuel el 08/10; no se presentan como pruebas de ejecución ni como aceptación de Diana.

**Tabla 2. Cambios y decisiones respecto de las fuentes**

| Cambio y origen | Fecha | Motivo y responsable |
| --- | --- | --- |
| Acta: HU01-HU03 pasan al Sprint 2 | 05/10, 12:55 | O1 no se completó. Samuel registra el arrastre; se conserva el vencimiento original del 04/10. |
| Estándares: excepción NC-01 | 05/10 | HU01 se integró sin revisión formal previa. Samuel documenta el incumplimiento; falta revisión posterior de Julian. |
| Estándares: herramientas | 30/09 y 05/10 | Prettier se publica y PHP CS Fixer se propone en PR #9. Samuel prepara; Julian debe revisar los cambios pendientes. |
| Calidad: línea base medida | 07/10 | Se publican pruebas, métricas y análisis estático en PR #10 bajo autorización de Samuel; Julian revisa el documento. |
| Calidad: identificación de CA4-7 | 07/10, 21:30 | Se detallan validaciones, RQ02 y reglas de movimientos ya preparadas. Samuel autoriza el registro; no consta aceptación del negocio. |
| Gestión: recuperación de O1 | 08/10 | Samuel informa acuerdo con Julian: meta 09/10 y mayor dedicación al Sprint 2. Las horas adicionales aún no están cuantificadas. |
| Calidad: propuesta D-02 | 08/10, 23:46 | Samuel autoriza proteger el alta con token. Siete pruebas correctas; PR #11 pendiente de revisión e integración. |

Las fechas indican registro o comprobación real. NC-01 conserva el día de detección, pero no su hora inicial. Los documentos originales no se reescriben para aparentar que el hito se cumplió. El archivo de estándares aportado coincide byte por byte con la versión actual de develop.

## Estado real frente al acta

**Tabla 3. Compromisos y situación al corte**

| Hito y fecha original | Estado real y efecto |
| --- | --- |
| O1: inventario y recetas; 04/10 | Incumplido; cuatro días de atraso al 08/10. HU01 integrada sin cierre de calidad; HU02 en rama; HU03 pendiente. Meta de recuperación: 09/10, cinco días después del plazo. |
| O2: cálculo y proyección; 18/10 | Pendiente. HU04-HU06 necesitan inventario y recetas. El arrastre reduce la capacidad del Sprint 2. |
| O3: planificación; 01/11 | Pendiente. Se conserva inicio del Sprint 3 el 19/10 como objetivo del equipo, condicionado a resolver las dependencias. |
| O4: ventas y reportes; 17/11 aproximado | Pendiente. No hay evidencia de implementación; se conserva el alcance del Acta. |

### Explicación y recuperación del primer hito

La organización apresurada del trabajo y la preparación tardía de las evidencias de revisión desplazaron actividades del Sprint 1 al inicio del Sprint 2. Según lo informado por Samuel, el equipo acordó completar el trabajo pendiente de inventario, movimientos y recetas el 9 de octubre de 2026. Ese cierre requiere cumplir los criterios de aceptación, corregir los fallos y obtener la revisión del otro integrante; al corte todavía no se acredita su cumplimiento.

Para recuperar el cronograma, Samuel y Julian acordaron aumentar la dedicación durante el Sprint 2 y priorizar las dependencias de cálculo y proyección, con el objetivo de iniciar el Sprint 3 el 19 de octubre. El acuerdo no fija todavía un número de horas adicionales. El cierre de O1 se verificará con resultados y revisiones, y cualquier nueva desviación se registrará conservando las fechas originales.

La detección del arrastre está respaldada por Jira el 05/10 a las 12:55. La causa se identifica como explicación del equipo comunicada el 08/10; el historial no permite atribuir por sí solo el atraso a parciales o a indisponibilidad de los integrantes.

## Product backlog estimado

La unidad es el punto de historia (story point, SP). Las 12 historias suman 60 SP; son estimaciones de esfuerzo relativo, no horas ni porcentaje terminado. Se conserva el orden del backlog de Jira, priorizando la recuperación de inventario y recetas por dependencia técnica.

**Tabla 4. Backlog completo en orden de trabajo**

| Historia | Resultado | SP | Sprint | Estado |
| --- | --- | --- | --- | --- |
| HU01 / SCRUM-9 | Materias primas | 3 | 1 a 2 | En revisión |
| HU02 / SCRUM-10 | Movimientos de inventario | 5 | 1 a 2 | En curso |
| HU03 / SCRUM-11 | Recetas e ingredientes | 5 | 1 a 2 | Por hacer |
| HU04 / SCRUM-12 | Producción máxima | 8 | 2 | Por hacer |
| HU05 / SCRUM-13 | Recetas posibles | 5 | 2 | Por hacer |
| HU06 / SCRUM-14 | Proyección con pendientes | 5 | 2 | Por hacer |
| HU07 / SCRUM-15 | Producción por fecha | 5 | 3 | Por hacer |
| HU08 / SCRUM-16 | Materiales y faltantes | 8 | 3 | Por hacer |
| HU09 / SCRUM-17 | Producción y descuento | 5 | 3 | Por hacer |
| HU10 / SCRUM-18 | Ventas | 3 | 4 | Por hacer |
| HU11 / SCRUM-19 | Reportes diarios y semanales | 5 | 4 | Por hacer |
| HU12 / SCRUM-20 | Hora pico de ventas | 3 | 4 | Por hacer |

Nota. HU significa historia de usuario. HU04, HU08 y HU09 tienen prioridad alta; las demás, media. La prioridad de Jira y el orden de trabajo son campos distintos.

Ninguna de las 12 historias aparece Finalizada. Las tareas D-01, D-02 y la revisión cruzada se controlan aparte y no aumentan automáticamente los 60 SP del backlog. Se corrige el total de 65 SP del borrador anterior.

HU04 necesita inventario y recetas; HU05 necesita recetas y cálculo; HU06 incorpora pendientes solo para proyección. Se preparará HU03 cuando se libere capacidad de trabajo y cumpla la definición de preparado (DoR).

## Tablero y seguimiento del trabajo

Fuente: [tablero SCRUM en Jira](https://sistema-inventario-produccion.atlassian.net/jira/software/projects/SCRUM/boards/1). Sprint 1 cerrado el 05/10 a las 12:54:40; Sprint 2 activo del 05/10 al 18/10. El registro de cierre del sprint no acredita el cierre de sus historias. HU01-HU03 fueron arrastradas al Sprint 2.

**Tabla 5. Condiciones para avanzar en el tablero**

| Columna | Política operativa |
| --- | --- |
| Por hacer | Cumplir DoR: criterios, dependencias, estimación, sprint, flujo y caso de prueba definido. |
| En curso | Trabajo identificado en rama desde develop; producir evidencia y respetar el límite de dos historias activas. |
| En revisión | Pruebas reproducidas por el otro integrante y controles de la definición de terminado (DoD). HU01 conserva la excepción NC-01. |
| Finalizada | DoD cumplida, revisión del otro integrante e integración. Un cambio de columna aislado no demuestra terminación. |

El límite operativo es dos historias entre En curso y En revisión: HU01 y HU02 ocupan las dos plazas. Jira devuelve constraintType: none; el límite aún no está aplicado en la configuración y las políticas deben quedar visibles en la fuente. Su ajuste y el acceso de lectura del docente se mantienen pendientes, por decisión de atender Jira después del consolidado.

### Historial comprobado y periodo académico

Se revisó el historial completo de los 23 ítems existentes: del 04/10 al 05/10 hay 10 transiciones reales de estado en seis ítems distintos. Tres ítems son HU01-HU03, con siete transiciones; SCRUM-2/3 son tareas de ejemplo y SCRUM-5 es una épica. Se excluyen cambios al mismo estado, comentarios, ediciones y asignaciones iniciales a sprint.

El conteo obligatorio entre semanas 6 y 11 sigue pendiente de las fechas del cronograma de Gerencia de Software. Los sprints del Acta son fechas internas y no permiten deducir esas semanas académicas. Se utilizará el inicio de semana 1 o el calendario del aula virtual para filtrar el historial ya extraído.

## Calidad y resultados medidos

El 07/10 se diseñaron 15 casos: 14 ejecutados, 12 aprobados, dos fallidos y uno pendiente. CP-13/14 fallaron por seguridad; CP-15 espera recetas (Caicedo Ramirez & Riveros Martinez, 2026c).

**Tabla 6. Requisitos del Acta y evidencia del Taller 5**

| Requisito | Resultado y alcance |
| --- | --- |
| RQ01: fiabilidad | Saldo e historial y reversión ante fallo comprobados en HU02; falta reproducción independiente. |
| RQ02: seguridad | CP-13/14 fallidos el 07/10. D-01/SCRUM-21 y D-02/SCRUM-22 abiertos; HU01 no cumple DoD 1. |
| RQ03: desempeño | p95: listado 9,67 ms; movimientos 7,45 ms. Meta 1 s. Laboratorio, 1000 materias, un cliente, 100 muestras y cinco iniciales; sin recursos visuales ni red externa. |
| RQ04: interacción | Controles de 12 campos visibles y cuatro escenarios de error documentados; la comprensión por Diana no está aceptada. |
| RQ05: compatibilidad | Intercambio de cantidad/unidad entre formulario, PHP y base de inventario. Alcance parcial; no incluye módulos futuros. |

**Tabla 7. Indicadores del 7 de octubre de 2026**

| Métrica | Valor medido y decisión |
| --- | --- |
| Cobertura de líneas | 113/117 = 96,58 %, solo en dos archivos de lógica. Meta propuesta 90 %; no representa todo el producto. |
| Complejidad CCN2 | Máximo 13; otra función 11. Meta propuesta 10. Revisar ambas funciones; no ampliar el alcance del avance con refactor opcional. |
| Densidad de defectos | Dos defectos abiertos / dos HU probadas = 1,00 defecto/HU. Meta 0 antes de liberar el producto. |

PHPStan 2.3.0, nivel 5: 11 archivos analizados, 11 avisos en siete. Nueve sobre conexión y dos condiciones siempre falsas; requieren inspección, no son defectos confirmados. [Reporte completo de análisis estático](https://github.com/Samge34c/sistema-inventario-produccion/blob/75d97605c2bfb7c76e3041f426060341f552aa93/docs/calidad/taller5/2026-10-07/REPORTE_ANALISIS_ESTATICO.md).

## Evidencias del repositorio

Repositorio público: [https://github.com/Samge34c/sistema-inventario-produccion](https://github.com/Samge34c/sistema-inventario-produccion). El índice en [docs/avance-consolidado](https://github.com/Samge34c/sistema-inventario-produccion/tree/docs/avance-consolidado) reúne documentos y ramas pendientes de revisión (Samge34c, 2026).

**Tabla 8. Tres commits representativos**

| Hash y fecha Bogotá | Cambio comprobado |
| --- | --- |
| [13f529e](https://github.com/Samge34c/sistema-inventario-produccion/commit/13f529e23e7dc1e299a41c3f2dbbc4b69bb6d706); 30/09, 21:16:43 | Publicación de los estándares adoptados por ambos integrantes. |
| [28a54fc](https://github.com/Samge34c/sistema-inventario-produccion/commit/28a54fc14b9a41917b11837869e398ce465d5eb7); 05/10, 19:36:52 | Implementación de movimientos y actualización transaccional del saldo. |
| [75d9760](https://github.com/Samge34c/sistema-inventario-produccion/commit/75d97605c2bfb7c76e3041f426060341f552aa93); 07/10, 15:00:22 | Publicación de pruebas, métricas y análisis estático del Taller 5. |

Nota. Falta confirmar su pertenencia al intervalo académico de semanas 6 a 11. Los hashes y fechas sí se verificaron.

**Tabla 9. Propuestas y estado de revisión**

| PR | Contenido y situación |
| --- | --- |
| [#7](https://github.com/Samge34c/sistema-inventario-produccion/pull/7) | Registro previo de gestión; abierto. El nuevo consolidado actualiza sus datos históricos. |
| [#8](https://github.com/Samge34c/sistema-inventario-produccion/pull/8) | HU02: implementada, sin integrar; ejecución independiente y revisión formal pendientes. |
| [#9](https://github.com/Samge34c/sistema-inventario-produccion/pull/9) | PHP CS Fixer: configuración publicada en rama; revisión e integración pendientes. |
| [#10](https://github.com/Samge34c/sistema-inventario-produccion/pull/10) | Taller 5: evidencias publicadas y revisión documental de Julian. Falta su reproducción independiente. |
| [#11](https://github.com/Samge34c/sistema-inventario-produccion/pull/11) | SCRUM-22: propuesta de corrección. Siete pruebas HTTP/SQL aprobadas; falta revisión de Julian. |

El 08/10 a las 23:46, siete pruebas HTTP/SQL aprobaron sobre la corrección 5e5c333: CP-14 devolvió 403 sin guardar; el alta válida creó un registro y la cantidad negativa ninguno. D-02 sigue abierto hasta revisar e integrar. Se conserva la medición del 07/10.

## Riesgos y respuestas

Se revisan los seis riesgos del Acta, aunque la guía mencione cinco. Se mantiene la valoración original mientras el equipo no acuerde otra; las respuestas se contrastan con la evidencia disponible (Caicedo Ramirez & Riveros Martinez, 2026a).

**Tabla 10. Situación de los riesgos originales**

| Riesgo y responsable | Materialización, respuesta y vigencia |
| --- | --- |
| R1: validación tardía; Julian | No consta aceptación funcional de Diana. Consultar y registrar pendientes sigue vigente; eficacia por comprobar. |
| R2: aumento de alcance; Samuel | No se acredita ampliación funcional. Mantener las 12 HU y separar mantenimiento; respuesta vigente. |
| R3: unidades inconsistentes; Julian | La prueba antigua de HU02 es discrepante y no identifica versión. Mantener unidad por materia y repetir casos conocidos; riesgo abierto. |
| R4: menor disponibilidad; Samuel | Hay atraso, pero no prueba de su causa por parciales. El equipo acordó dedicar más horas al Sprint 2; falta cuantificarlas y registrar ejecución. |
| R5: datos insuficientes; Julian | Se usaron datos ficticios. Pruebas de faltantes y pendientes disponibles; falta validar datos del negocio. Riesgo vigente. |
| R6: integración tardía; Samuel | Se materializó en O1: el cierre previsto del 04/10 no se logró. La respuesta original no evitó el arrastre. Recuperar O1 con pruebas y revisión antes de cálculo. |

### Riesgos nuevos

La integración de HU01 sin revisión formal generó NC-01 el 05/10. Una validación posterior debe resolver el estado actual, sin borrar el incumplimiento original. La versión no identificada de la prueba de Julian obliga a registrar rama, commit y entorno antes de aprobar.

Los fallos de seguridad detectados el 07/10 aumentan el riesgo de modificaciones indebidas. D-01 queda a cargo de Julian y D-02 tiene propuesta probada en PR #11. La dependencia del CDN falló en laboratorio; el resultado con Bootstrap local no certifica el recurso externo.

## Responsabilidades y uso de inteligencia artificial

Se conservan los roles del Acta hasta el Sprint 2. La rotación prevista es el 19/10. Las actividades siguientes permiten registrar el aporte de ambos integrantes y comprobar el compromiso de recuperación; no se declaran realizadas por estar asignadas.

**Tabla 11. Trabajo inmediato y evidencia de cierre**

| Responsable | Actividad y evidencia |
| --- | --- |
| Julian | SCRUM-23: reproducir HU02/Taller 5; registrar fecha, commit, herramientas, resultados y logs en PR #8 y #10. |
| Julian | SCRUM-21: corregir D-01, probar acceso permitido/rechazado y solicitar revisión de Samuel. Revisar PR #11 de D-02. |
| Samuel | Consolidar documentos y evidencias; preparar SCRUM-22. Su propuesta está probada, pero requiere reproducción y revisión de Julian. |
| Ambos | Meta 09/10: verificar cierre real de HU01-HU03. Aumentar dedicación al Sprint 2; fijar horas y registrar avances para conservar el inicio del Sprint 3 el 19/10. |
| Samuel y Julian | Confirmar calendario académico, aplicar WIP/políticas y verificar acceso del docente. Ensayar cinco minutos con intervención de ambos. |

### Declaración de uso de inteligencia artificial

Se utilizó ChatGPT (Codex) para leer las fuentes, consultar GitHub/Jira, extraer historial, apoyar pruebas y correcciones, y organizar y redactar el consolidado bajo autorización de Samuel. La comprobación técnica de SCRUM-22 fue ejecutada por el asistente en un entorno aislado y no constituye ejecución personal de Julian.

La revisión humana registrada de Julian es documental: sus comentarios del 07/10 a las 23:02 y 23:24 aclaran que no reprodujo personalmente las pruebas. Samuel aportó las versiones base y comunicó la explicación y el acuerdo de recuperación. El equipo debe revisar el contenido, interpretar resultados y realizar las aprobaciones pendientes.

La aceptación de Diana, el cierre de las historias, el acceso del docente y el ensayo de sustentación no se presentan como realizados.

## Referencias

Caicedo Ramirez, J. C., & Riveros Martinez, S. E. (2026a, 20 de septiembre). Acta de constitución del proyecto: Sistema de inventario, recetas y planificación de producción [Trabajo académico no publicado]. Universidad Santo Tomás.

Caicedo Ramirez, J. C., & Riveros Martinez, S. E. (2026b, 30 de septiembre). Estándares del equipo [Documento de proyecto]. GitHub. [ESTANDARES.md](https://github.com/Samge34c/sistema-inventario-produccion/blob/13f529e23e7dc1e299a41c3f2dbbc4b69bb6d706/ESTANDARES.md)

Caicedo Ramirez, J. C., & Riveros Martinez, S. E. (2026c, 7 de octubre). Plan de control de calidad del sistema de inventario, recetas y planificación de producción [Trabajo académico no publicado]. Universidad Santo Tomás.

Samge34c. (2026). Sistema de inventario, recetas y planificación de producción [Repositorio de código y evidencias]. GitHub. [https://github.com/Samge34c/sistema-inventario-produccion](https://github.com/Samge34c/sistema-inventario-produccion)

Universidad Santo Tomás. (s. f.). Avance del proyecto: Qué debe presentar el estudiante, paso a paso [Guía de Gerencia de Software].
