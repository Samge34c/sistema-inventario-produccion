# Avance del proyecto

Corte: 9 de octubre de 2026, America/Bogota.

Avance del proyecto de inventario,
recetas y planificación de producción

Julian Camilo Caicedo Ramirez y
Samuel Esteban Riveros Martinez

Ingeniería de Sistemas
Universidad Santo Tomás

Gerencia de Software

Stefany Gomez Riveros

9 de octubre de 2026

## Tabla de contenido

Fecha de corte: 9 de octubre de 2026, hora de Bogotá. Se distinguen resultados publicados, compromisos de recuperación y actividades pendientes. El periodo académico se presenta hasta la semana 10; la semana 11 aún no ha transcurrido.

## Consolidación de los talleres

El primer hito continúa abierto: materias primas tiene correcciones pendientes, movimientos cuenta con un reporte de ejecución independiente de Julian y recetas no tiene implementación publicada. El cierre requiere pruebas, revisión formal e integración; se conservan las fechas del Acta.

Se consolidan las versiones aportadas del Acta, los estándares y el Taller 5 (Caicedo Ramirez & Riveros Martinez, 2026a, 2026b, 2026c), siguiendo la guía del avance (Universidad Santo Tomás, s. f.). La Tabla 1 relaciona las fuentes con la gestión actual.

**Tabla 1. Relación entre los tres talleres**

| Fuente | Cómo condiciona el trabajo |
| --- | --- |
| Taller 3: Acta del 20/09 | Define 12 historias de usuario (HU), cuatro sprints, roles, seis riesgos y requisitos de calidad (RQ) 01-05. |
| Taller 4: estándares del 30/09 | Exige criterios reproducibles, nombres del dominio en español, formato automático y revisión por el otro integrante. |
| Taller 5: calidad del 07/10 | Mide HU01/HU02, identifica dos fallos altos y evita declarar terminadas historias que incumplen sus criterios. |

### Tecnología e interlocutora

Se mantiene PHP con acceso a datos mediante PDO, esquema MySQL, HTML/Bootstrap y JavaScript. Bootstrap es el framework de interfaz; no se usa uno backend PHP. Los laboratorios registran MariaDB 10.11.14 y, en el reporte de Julian, 10.4.32. MySQL 8 y el entorno final siguen por verificar. PHPStan analiza código; Prettier y PHP CS Fixer controlan formato.

Diana Marcela Caicedo, propietaria, es la interlocutora principal confirmada en el Acta. Su aceptación funcional está pendiente. GitHub conserva código y evidencia; Jira registra el trabajo.

## Control de cambios

La Tabla 2 distingue cambios, incumplimientos y publicaciones. Los criterios de aceptación (CA) detallados el 07/10 no se atribuyen a una fecha anterior. Las decisiones comunicadas por Samuel se separan de la ejecución técnica asistida.

**Tabla 2. Registro de cambios y seguimiento**

| Origen y tipo | Fecha Bogotá | Cambio, motivo y decisión |
| --- | --- | --- |
| Acta: desviación | 05/10, 12:55 | HU01-HU03 pasan al Sprint 2 al quedar O1 abierto. Samuel registra el arrastre; plazo original: 04/10. |
| Estándares: incumplimiento NC-01 | 05/10 | HU01 integrada sin revisión previa. Samuel registra la excepción; Julian debe revisar. No cambia la regla. |
| Estándares: aplicación | 30/09 y 05/10 | Prettier publicado y PHP CS Fixer propuesto en PR #9. Preparación de Samuel; revisión de Julian pendiente. |
| Taller 5: publicación | 07/10 | Pruebas y métricas en PR #10, con apoyo de Codex autorizado por Samuel. No cambia el alcance del Acta. |
| Calidad: refinamiento CA4-7 | 07/10, 21:30 | Validaciones y seguridad detalladas en Jira, por autorización de Samuel. No consta aceptación de Diana. |
| Acta: recuperación propuesta | 08/10 | Samuel comunica acuerdo con Julian: meta 09/10 y más dedicación al Sprint 2; horas sin cuantificar. |
| Calidad: corrección D-02 | 08/10, 23:46 | Propuesta autorizada por Samuel: rechazo de solicitudes sin token válido. Siete pruebas correctas; PR #11 sin integrar. |
| Tablero: distribución de límites | 09/10 | Samuel configura máximo uno En curso y uno En revisión; políticas en SCRUM-24. Confirmación de Julian pendiente. |
| Calidad: evidencia independiente | 09/10 | Julian publica reporte HU02: 42 comprobaciones de integración y 15 HTTP correctas. No cierra D-01/D-02 ni sustituye aprobación. |

NC-01 no conserva hora inicial. Los estándares aportados coinciden con develop.

## Estado real frente al acta

La Tabla 3 contrasta los hitos; O1 acumula cinco días de atraso al 09/10.

**Tabla 3. Compromisos y situación al corte**

| Hito y fecha original | Estado real y efecto |
| --- | --- |
| O1: inventario y recetas; 04/10 | Abierto: HU01 con defectos; HU02 probada según reporte de Julian, en rama sin aprobación; HU03 sin implementación publicada. Meta 09/10 aún no acreditada. |
| O2: cálculo y proyección; 18/10 | Pendiente. HU04-HU06 necesitan inventario y recetas. El arrastre reduce la capacidad del Sprint 2. |
| O3: planificación; 01/11 | Pendiente. Se conserva inicio del Sprint 3 el 19/10 como objetivo del equipo, condicionado a resolver las dependencias. |
| O4: ventas y reportes; 17/11 aproximado | Pendiente. No hay evidencia de implementación; se conserva el alcance del Acta. |

Arrastre detectado: 05/10, 12:55. Samuel comunicó el 08/10 organización apresurada y acuerdo con Julian de mayor dedicación al Sprint 2. La Tabla 4 propone recuperar O1 el 09/10 e iniciar el Sprint 3 el 19/10; faltan confirmar horas y responsables pendientes.

**Tabla 4. Secuencia propuesta de recuperación**

| Orden y meta | Responsable y evidencia necesaria |
| --- | --- |
| 1. HU01, meta 09/10 | Julian: D-01 y revisión de PR #11. Samuel: D-02 propuesta y revisión cruzada. Cierre: casos de seguridad aprobados e integración autorizada. |
| 2. HU02, meta 09/10 | Julian: contraste y revisión pendientes. Samuel: integrar tras aprobación y capacidad disponible. Conservar versión y resultados publicados. |
| 3. HU03, meta 09/10 | Responsable por confirmar: preparar criterios, implementar recetas y ejecutar CP-15. No iniciar otra tarjeta excediendo el límite. |
| 4. HU04-HU06, meta 18/10 | Equipo: cerrar dependencias, acordar horas adicionales y registrar avance. Confirmar inicio del Sprint 3 el 19/10 según resultados. |

## Product backlog estimado

La unidad es el punto de historia (story point, SP). Las 12 historias suman 60 SP, estimaciones relativas que no equivalen a horas ni a porcentaje terminado. La Tabla 5 prioriza inventario y recetas por las dependencias del cálculo.

**Tabla 5. Backlog completo en orden de trabajo**

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

HU04-HU06 dependen de inventario y recetas. HU03 debe cumplir la definición de preparado (Definition of Ready, DoR). HU02/HU03 no tienen persona asignada en Jira; confirmar responsable.

## Tablero y seguimiento del trabajo

Fuente: [tablero SCRUM en Jira](https://sistema-inventario-produccion.atlassian.net/jira/software/projects/SCRUM/boards/1). Sprint 1 cerrado el 05/10 a las 12:54:40 sin cerrar HU01-HU03; Sprint 2 activo del 05/10 al 18/10. La Tabla 6 resume las políticas publicadas en [SCRUM-24](https://sistema-inventario-produccion.atlassian.net/browse/SCRUM-24).

**Tabla 6. Condiciones para avanzar en el tablero**

| Columna | Política operativa |
| --- | --- |
| Por hacer | Cumplir DoR: criterios, dependencias, estimación, sprint, flujo y caso de prueba definido. |
| En curso | Máximo una tarjeta. Identificar rama desde develop y producir evidencia para revisión; aplicar los controles previos de calidad. |
| En revisión | Máximo una tarjeta. El otro integrante reproduce criterios y verifica la definición de terminado (Definition of Done, DoD). Revisión dentro de 48 horas desde que esté lista. |
| Finalizada | DoD cumplida, revisión del otro integrante e integración. Un cambio de columna aislado no demuestra terminación. |

El 09/10 Samuel configuró trabajo en progreso (WIP) de 1 + 1: HU02 En curso y HU01 En revisión. Verificado en Jira; requiere confirmación de Julian. El límite señala exceso, sin bloqueo automático ni aplicación retrospectiva.

Cuenta docente: Invitado. Samuel reportó seleccionar Lector; faltan aceptación, confirmación del permiso y apertura efectiva del tablero.

### Historial comprobado y periodo académico

Samuel confirmó semana 1 desde el 04/08/2026: semana 6, 08-14/09; semana 11, 13-19/10. Referencia del equipo, sin calendario institucional aportado; el corte está en semana 10.

Hasta el corte: seis ítems con 10 transiciones reales el 04-05/10, semana 9. HU01-HU03 aportan siete; SCRUM-2/3 son ejemplos y SCRUM-5 una épica. Se excluyen ediciones y comentarios. SCRUM-24 no agrega transiciones; semana 11 aún futura.

## Calidad y resultados medidos

El 07/10: 15 casos, 14 ejecutados, 12 aprobados, dos fallidos y uno pendiente. CP-13/14 fallaron por seguridad; CP-15 espera recetas. La Tabla 7 conserva esa línea base (Caicedo Ramirez & Riveros Martinez, 2026c).

**Tabla 7. Requisitos del Acta y evidencia del Taller 5**

| Requisito | Resultado y alcance |
| --- | --- |
| RQ01: fiabilidad | Saldo, historial y reversión ante fallo comprobados en la línea base. Julian publicó reproducción HU02 el 09/10; faltan aprobación formal y cierre completo de criterios. |
| RQ02: seguridad | CP-13/14 fallidos el 07/10. D-01/SCRUM-21 y D-02/SCRUM-22 abiertos; HU01 no cumple DoD 1. |
| RQ03: desempeño | Percentil 95 (p95): listado 9,67 ms; movimientos 7,45 ms. Meta de uso: 1 s. Laboratorio: 1000 materias, un cliente, 100 muestras tras cinco de calentamiento; sin recursos visuales ni red externa. |
| RQ04: interacción | Controles de 12 campos visibles y cuatro escenarios de error documentados; la comprensión por Diana no está aceptada. |
| RQ05: compatibilidad | Intercambio de cantidad/unidad entre formulario, PHP y base de inventario. Alcance parcial; no incluye módulos futuros. |

Tabla 8: métricas medidas; metas propuestas pendientes de validación.

**Tabla 8. Indicadores del 7 de octubre de 2026**

| Métrica | Valor medido y decisión |
| --- | --- |
| Cobertura de líneas | 113/117 = 96,58 %, solo en functions.php y movimientos-funciones.php. Meta propuesta 90 %; no representa todo el producto. |
| Complejidad ciclomática (CCN2) | Máximo 13; otra función 11. Meta propuesta 10; ambas requieren revisión. Se conserva el resultado medido. |
| Densidad de defectos | Dos defectos abiertos / dos HU probadas = 1,00 defecto/HU. Meta 0 antes de liberar el producto. |

PHPStan 2.3.0, nivel 5: 11 archivos analizados, 11 avisos en siete. Nueve sobre conexión y dos condiciones siempre falsas; requieren inspección, no son defectos confirmados. [Reporte completo de análisis estático](https://github.com/Samge34c/sistema-inventario-produccion/blob/75d97605c2bfb7c76e3041f426060341f552aa93/docs/calidad/taller5/2026-10-07/REPORTE_ANALISIS_ESTATICO.md).

## Evidencias del repositorio

Repositorio: [https://github.com/Samge34c/sistema-inventario-produccion](https://github.com/Samge34c/sistema-inventario-produccion). El [índice de documentación](https://github.com/Samge34c/sistema-inventario-produccion/blob/docs/avance-consolidado/docs/README.md) reúne fuentes y ramas. La Tabla 9 identifica tres commits verificables (Samge34c, 2026).

**Tabla 9. Tres commits representativos**

| Hash y fecha Bogotá | Cambio comprobado |
| --- | --- |
| [13f529e](https://github.com/Samge34c/sistema-inventario-produccion/commit/13f529e23e7dc1e299a41c3f2dbbc4b69bb6d706); 30/09, 21:16:43 | Publicación de los estándares adoptados por ambos integrantes. |
| [28a54fc](https://github.com/Samge34c/sistema-inventario-produccion/commit/28a54fc14b9a41917b11837869e398ce465d5eb7); 05/10, 19:36:52 | Implementación de movimientos y actualización transaccional del saldo. |
| [75d9760](https://github.com/Samge34c/sistema-inventario-produccion/commit/75d97605c2bfb7c76e3041f426060341f552aa93); 07/10, 15:00:22 | Publicación de pruebas, métricas y análisis estático del Taller 5. |

Nota. Los dos primeros corresponden a semana 9 y el tercero a semana 10, según el inicio académico confirmado por el equipo.

La Tabla 10 distingue publicación, pruebas y aprobación; las propuestas siguen sin integrar.

**Tabla 10. Propuestas y estado de revisión**

| PR | Contenido y situación |
| --- | --- |
| [#8](https://github.com/Samge34c/sistema-inventario-produccion/pull/8) | HU02: implementada, sin integrar; Julian publicó reproducción el 09/10. Revisión formal pendiente. |
| [#9](https://github.com/Samge34c/sistema-inventario-produccion/pull/9) | PHP CS Fixer: configuración publicada en rama; revisión e integración pendientes. |
| [#10](https://github.com/Samge34c/sistema-inventario-produccion/pull/10) | Taller 5: revisión documental registrada. Reproducción HU02 publicada aparte; resto de la revisión técnica pendiente. |
| [#11](https://github.com/Samge34c/sistema-inventario-produccion/pull/11) | SCRUM-22: propuesta de corrección con siete pruebas HTTP/SQL aprobadas; falta revisión de Julian. |
| [#12](https://github.com/Samge34c/sistema-inventario-produccion/pull/12) | Avance consolidado e índice de documentación; revisión del otro integrante pendiente. |

D-02: siete pruebas HTTP/SQL correctas el 08/10 a las 23:46 sobre 5e5c333. CP-14 fue rechazado sin guardar; el alta válida creó un registro. Sigue abierto hasta revisión e integración.

## Ejecución independiente de HU02

Julian Caicedo publicó el 09/10 el [reporte de ejecución de HU02](https://github.com/Samge34c/sistema-inventario-produccion/blob/3d96f4afb1640b66f3a8ef08c4a543e5e432490b/docs/pruebas/evidencias/julian-hu02-2026-10-09.md) y sus salidas en PR #8 (Caicedo Ramirez, 2026). La publicación identifica responsable, fecha, versión probada y herramientas; permite registrar su aporte sin atribuirle las ejecuciones anteriores del asistente.

**Tabla 11. Evidencia publicada por Julian**

| Dato | Registro y alcance |
| --- | --- |
| Versión probada | 7a01251325365c8d2ebbead5b89d5732671f1463, rama feat/hu02-movimientos. Los archivos de evidencia se publicaron en 3d96f4a. |
| Fecha y herramientas | 09/10/2026, Bogotá; hora exacta no conservada. PHP 8.2.12, MariaDB 10.4.32, Node.js v24.21.0. |
| Integración PHP | 42 comprobaciones correctas reportadas. El archivo conserva el resultado final, sin la salida completa de las 42 comprobaciones. |
| Pruebas HTTP | 15 comprobaciones correctas y cero fallidas, en transcripción normalizada; script publicado. No se conserva la captura original de terminal. |
| Entorno | Según el reporte: base temporal prueba_hu02_http y aplicación aislada en localhost:8001; sin utilizar la base habitual. |
| Comportamientos | Entradas, salidas, pendientes, cantidades inválidas, token de seguridad, bloqueo de GET, edición del saldo y protección del historial. |

Las 42 y 15 comprobaciones pertenecen a dos conjuntos técnicos; no equivalen a 57 casos del plan. Los 15 casos del Taller 5, sus dos fallos históricos y CP-15 pendiente se conservan. La Tabla 11 resume la nueva evidencia; su enlace quedó registrado en SCRUM-23.

La protección frente a falsificación de solicitudes entre sitios (CSRF) probada en movimientos no certifica la autorización de usuarios ni corrige por sí misma HU01. D-01 y D-02 siguen abiertos. La publicación de resultados tampoco reemplaza una revisión formal del PR o el cumplimiento de todos los criterios de aceptación.

## Riesgos y respuestas

La Tabla 12 revisa los seis riesgos del Acta y conserva su probabilidad (P) e impacto (I) originales. Se distingue la respuesta prevista de lo ejecutado; la ausencia de evidencia de materialización no permite cerrar un riesgo (Caicedo Ramirez & Riveros Martinez, 2026a).

**Tabla 12. Situación de los riesgos originales**

| Riesgo y responsable | Materialización, respuesta y vigencia |
| --- | --- |
| R1: validación tardía; Julian; P media, I alto | Sin materialización causal comprobada. Previsto: consultar y registrar pendientes. Ejecución: no consta aceptación de Diana. Vigente; eficacia no evaluada. |
| R2: aumento de alcance; Samuel; P media, I alto | Sin ampliación acreditada. Previsto: contrastar solicitudes y diferir extras. Ejecución: se conservan 12 HU. Vigente; no hay solicitudes nuevas para evaluar eficacia. |
| R3: unidades inconsistentes; Julian; P media, I alto | Sin inconsistencia entre módulos demostrada. Previsto: unidad por materia y casos conocidos. Ejecución: pruebas de inventario/HU02 publicadas. Vigente; eficacia parcial, recetas sin implementar. |
| R4: menor disponibilidad; Samuel; P alta, I medio | Atraso confirmado, causa por parciales no acreditada. Previsto: dividir tareas y revisar semanalmente. Ejecución: acuerdo de más dedicación comunicado. Vigente; horas y eficacia sin comprobar. |
| R5: datos insuficientes; Julian; P media, I medio | Sin error atribuible demostrado. Previsto: normales, faltantes, pendientes y horas de venta. Ejecución: datos ficticios y casos de inventario. Vigente; eficacia parcial, faltan datos validados del negocio. |
| R6: integración tardía; Samuel; P media, I alto | Sin materialización comprobada del flujo completo. Previsto: integrar cada sprint y probar extremo a extremo. Ejecución: HU01 integrada, HU02 en rama. Vigente; eficacia global aún no evaluable. |

### Riesgos nuevos

NC-01 detectada el 05/10: falta revisión posterior de HU01. El reporte de Julian del 09/10 aclara la versión probada; no conserva todas las salidas originales.

D-01/D-02, detectados el 07/10, siguen abiertos. La red de distribución de contenido (CDN) falló en laboratorio; Bootstrap local no certifica el recurso externo.

## Responsabilidades y uso de inteligencia artificial

Se conservan los roles del Acta hasta el Sprint 2, con rotación prevista el 19/10. La Tabla 13 identifica actividades y evidencia de cierre; una asignación no demuestra ejecución.

**Tabla 13. Trabajo inmediato y evidencia de cierre**

| Responsable | Actividad y evidencia |
| --- | --- |
| Julian | HU02: reporte publicado el 09/10. SCRUM-23 sigue parcial: contrastar el resto del Taller 5, revisar avisos de PHPStan, umbrales propuestos y PR pendientes. |
| Julian | SCRUM-21: corregir D-01, probar acceso permitido/rechazado y solicitar revisión de Samuel. Revisar PR #11 de D-02. |
| Samuel | Consolidado actualizado y enlaces registrados en SCRUM-23. SCRUM-22 propuesta y probada con asistencia; falta revisión de Julian e integración posterior. |
| Ambos | Confirmar responsable de HU03 y horas adicionales; registrar resultados y revisar cierre real de O1. La meta 09/10 no está acreditada. |
| Samuel y Julian | Confirmar adopción de WIP 1 + 1; comprobar entrada del docente al tablero y repositorio desde otra sesión. Ensayar cinco minutos entre ambos. |

### Declaración de uso de inteligencia artificial

Se utilizó ChatGPT (Codex) para leer las fuentes, consultar GitHub/Jira, extraer historial, apoyar pruebas y correcciones, y organizar y redactar el consolidado bajo autorización de Samuel. La comprobación técnica de SCRUM-22 fue ejecutada por el asistente en un entorno aislado y no constituye ejecución personal de Julian.

Julian registró revisión documental el 07/10 y publicó su ejecución independiente de HU02 el 09/10: 42 comprobaciones de integración y 15 HTTP, con las limitaciones descritas en la Tabla 11. Samuel aportó las fuentes, confirmó el calendario y comunicó el acuerdo de recuperación. La revisión formal, la interpretación conjunta de hallazgos y la aceptación del negocio siguen pendientes.

## Referencias

Caicedo Ramirez, J. C. (2026, 9 de octubre). Evidencia de ejecución de pruebas HU02 [Reporte de pruebas]. GitHub. [https://github.com/Samge34c/sistema-inventario-produccion/blob/3d96f4afb1640b66f3a8ef08c4a543e5e432490b/docs/pruebas/evidencias/julian-hu02-2026-10-09.md](https://github.com/Samge34c/sistema-inventario-produccion/blob/3d96f4afb1640b66f3a8ef08c4a543e5e432490b/docs/pruebas/evidencias/julian-hu02-2026-10-09.md)

Caicedo Ramirez, J. C., & Riveros Martinez, S. E. (2026a, 20 de septiembre). Acta de constitución del proyecto: Sistema de inventario, recetas y planificación de producción [Trabajo académico no publicado]. Universidad Santo Tomás.

Caicedo Ramirez, J. C., & Riveros Martinez, S. E. (2026b, 30 de septiembre). Estándares del equipo [Documento de proyecto]. GitHub. [https://github.com/Samge34c/sistema-inventario-produccion/blob/13f529e23e7dc1e299a41c3f2dbbc4b69bb6d706/ESTANDARES.md](https://github.com/Samge34c/sistema-inventario-produccion/blob/13f529e23e7dc1e299a41c3f2dbbc4b69bb6d706/ESTANDARES.md)

Caicedo Ramirez, J. C., & Riveros Martinez, S. E. (2026c, 7 de octubre). Plan de control de calidad del sistema de inventario, recetas y planificación de producción [Trabajo académico no publicado]. Universidad Santo Tomás.

Samge34c. (2026). Sistema de inventario, recetas y planificación de producción [Repositorio de código y evidencias]. GitHub. [https://github.com/Samge34c/sistema-inventario-produccion](https://github.com/Samge34c/sistema-inventario-produccion)

Universidad Santo Tomás. (s. f.). Avance del proyecto: Qué debe presentar el estudiante, paso a paso [Guía de Gerencia de Software].
