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

- Consolidación de los talleres (p. 3 del PDF)
- Estándares y responsabilidades del equipo (p. 4 del PDF)
- Control de cambios (p. 5 del PDF)
- Estado real frente al acta (p. 6 del PDF)
- Product backlog estimado (p. 7 del PDF)
- Tablero y seguimiento del trabajo (p. 8 del PDF)
- Atributos de calidad y condiciones de evaluación (p. 9 del PDF)
- Calidad y resultados medidos (p. 10 del PDF)
- Evidencias del repositorio (p. 11 del PDF)
- Ejecución independiente de HU02 (p. 12 del PDF)
- Riesgos y respuestas (p. 13 del PDF)
- Responsabilidades y uso de inteligencia artificial (p. 14 del PDF)
- Referencias (p. 15 del PDF)
Fecha de corte: 9 de octubre de 2026, hora de Bogotá. Se distinguen resultados publicados, compromisos de recuperación y actividades pendientes. El periodo académico se presenta hasta la semana 10; la semana 11 aún no ha transcurrido.

## Consolidación de los talleres

Se consolidan las versiones aportadas del Acta, los estándares y el Taller 5 (Caicedo Ramirez & Riveros Martinez, 2026a, 2026b, 2026c), siguiendo la guía del avance (Universidad Santo Tomás, s. f.). La Tabla 1 relaciona las fuentes con la gestión actual.

**Tabla 1. Relación entre los tres talleres**

| Fuente | Cómo condiciona el trabajo |
| --- | --- |
| Taller 3: Acta del 20/09 | Define 12 historias de usuario (HU), cuatro sprints, roles, seis riesgos y requisitos de calidad (RQ) 01-05. |
| Taller 4: estándares del 30/09 | Exige criterios reproducibles, nombres del dominio en español, formato automático y revisión por el otro integrante. |
| Taller 5: calidad del 07/10 | Mide HU01/HU02, identifica dos fallos altos y evita declarar terminadas historias que incumplen sus criterios. |

### Propósito, alcance y restricciones del Acta

El control manual y separado de inventario, recetas y ventas dificulta anticipar faltantes. La aplicación centralizará esa información para calcular capacidad de producción, planificar por fecha y consultar ventas.

Incluye materias primas y movimientos; recetas, ingrediente limitante y proyección con materiales pendientes; planificación y descuento de insumos; ventas y reportes diarios, semanales y de hora pico.

Excluye facturación, contabilidad, nómina, pagos, domicilios, tienda virtual, inteligencia artificial como función del producto, integración automática con proveedores y aplicación móvil nativa. Cualquier ampliación exige revisar el tiempo disponible.

El Acta prevé cuatro horas semanales por integrante: unas 66 horas-persona teóricas entre el 20/09 y el 17/11, y 43-50 tras descontar imprevistos. Es capacidad estimada, distinta de los 60 puntos del backlog; las horas adicionales del Sprint 2 siguen sin cuantificar.

## Estándares y responsabilidades del equipo

La Tabla 2 resume las reglas adoptadas el 30/09 y su aplicación al trabajo actual (Caicedo Ramirez & Riveros Martinez, 2026b).

**Tabla 2. Reglas de trabajo y tecnología**

| Aspecto | Regla o situación comprobada |
| --- | --- |
| Tecnología | PHP/PDO, MySQL, HTML, CSS/Bootstrap y JavaScript; Git/GitHub y Jira. Bootstrap es el framework de interfaz; no se adoptó un framework backend PHP. |
| Estilo y nombres | Dominio y documentación en español. Variables y funciones: camelCase; clases: PascalCase; constantes: MAYUSCULAS_CON_GUION_BAJO; páginas: kebab-case. Términos externos conservan su idioma. |
| Guías y formato | PSR-12 para PHP y JavaScript Standard Style como bases. Prettier publicado; PHP CS Fixer propuesto en PR #9. PHPStan realiza análisis estático. |
| Commits | tipo(alcance): descripción en español, con verbo en imperativo y un cambio lógico. Usar feat, fix, docs, refactor, test, style o chore según el cambio. |
| Ramas | main conserva versiones estables; develop integra trabajo revisado. Ramas feat/<hu>-<nombre>, fix/<nombre> y docs/<nombre>, creadas desde develop. |
| Preparación | Definición de preparado (Definition of Ready, DoR): historia, criterios, dependencias, estimación, sprint, flujo si aplica y caso con resultado esperado. |
| Terminación | Definición de terminado (Definition of Done, DoD): criterios reproducidos por el otro integrante, sintaxis, flujo web, formato, SQL si aplica, ausencia de secretos y documentación. Integrar tras revisión. |

Hasta el Sprint 2, Julian asume requisitos, backlog, lógica y base de datos; Samuel, seguimiento Scrum, interfaz, pruebas y documentación. La rotación prevista comienza el 19/10. Estas responsabilidades generales no sustituyen la asignación de cada tarea.

Diana Marcela Caicedo, propietaria e interlocutora, debe validar los roles propuestos de producción y ventas. Su aceptación está pendiente. Laboratorios: MariaDB 10.11.14 y 10.4.32; MySQL 8 y entorno final sin verificar.

## Control de cambios

La Tabla 3 distingue cambios, incumplimientos y publicaciones. Los criterios de aceptación (CA) detallados el 07/10 no se atribuyen a una fecha anterior. Las decisiones comunicadas por Samuel se separan de la ejecución técnica asistida.

**Tabla 3. Registro de cambios y seguimiento**

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

La Tabla 4 contrasta los hitos; O1 acumula cinco días de atraso al 09/10.

**Tabla 4. Compromisos y situación al corte**

| Hito y fecha original | Estado real y efecto |
| --- | --- |
| O1: inventario y recetas; 04/10 | Abierto: HU01 con defectos; HU02 probada según reporte de Julian, en rama sin aprobación; HU03 sin implementación publicada. Meta 09/10 aún no acreditada. |
| O2: cálculo y proyección; 18/10 | Pendiente. HU04-HU06 necesitan inventario y recetas. El arrastre reduce la capacidad del Sprint 2. |
| O3: planificación; 01/11 | Pendiente. Se conserva inicio del Sprint 3 el 19/10 como objetivo del equipo, condicionado a resolver las dependencias. |
| O4: ventas y reportes; 17/11 aproximado | Pendiente. No hay evidencia de implementación; se conserva el alcance del Acta. |

Arrastre detectado: 05/10, 12:55. Samuel comunicó el 08/10 organización apresurada y acuerdo con Julian de mayor dedicación al Sprint 2. La Tabla 5 conserva la meta de recuperación de O1 el 09/10 e inicio del Sprint 3 el 19/10; ambas requieren verificar resultados y capacidad.

**Tabla 5. Secuencia propuesta de recuperación**

| Orden y meta | Responsable y evidencia necesaria |
| --- | --- |
| 1. HU01, meta 09/10 | Julian: resolver D-01 y revisar PR #11. Samuel: revisión cruzada. La meta 09/10 no acredita cierre; el acceso básico propuesto requiere acordar esfuerzo y probarse antes de integrar. |
| 2. HU02, meta 09/10 | Julian: contraste y revisión pendientes. Samuel: integrar tras aprobación y capacidad disponible. Conservar versión y resultados publicados. |
| 3. HU03, meta 09/10 | Responsable por confirmar: preparar criterios, implementar recetas y ejecutar CP-15. No iniciar otra tarjeta excediendo el límite. |
| 4. HU04-HU06, meta 18/10 | Equipo: cerrar dependencias, acordar horas adicionales y registrar avance. Confirmar inicio del Sprint 3 el 19/10 según resultados. |

RQ02 y D-01 requieren control de acceso. Se propone un login básico para el Sprint 2; implementación y esfuerzo por acordar. La Tabla 15 detalla las revisiones propuestas.

## Product backlog estimado

La unidad es el punto de historia (story point, SP). Las 12 historias suman 60 SP, estimaciones relativas que no equivalen a horas ni a porcentaje terminado. La Tabla 6 prioriza inventario y recetas por las dependencias del cálculo.

**Tabla 6. Backlog completo en orden de trabajo**

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

Ninguna de las 12 historias aparece Finalizada. Las tareas D-01, D-02 y la revisión cruzada se controlan aparte y no aumentan automáticamente los 60 SP del backlog. 

HU04-HU06 dependen de inventario y recetas. HU03 debe cumplir la definición de preparado (Definition of Ready, DoR). HU02/HU03 no tienen persona asignada en Jira; confirmar responsable.

## Tablero y seguimiento del trabajo

Fuente: [tablero SCRUM en Jira](https://sistema-inventario-produccion.atlassian.net/jira/software/projects/SCRUM/boards/1). El periodo del Sprint 1 se cerró en Jira el 05/10; su trabajo quedó incompleto. HU01-HU03 pasaron al Sprint 2, activo del 05/10 al 18/10. La Tabla 7 resume las políticas publicadas en [SCRUM-24](https://sistema-inventario-produccion.atlassian.net/browse/SCRUM-24).

**Tabla 7. Condiciones para avanzar en el tablero**

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

## Atributos de calidad y condiciones de evaluación

La Tabla 8 integra los cinco atributos con característica, métrica e instrumento, umbral y contexto. Conserva fuentes y alcance del Taller 5; las metas no prueban cumplimiento (Caicedo Ramirez & Riveros Martinez, 2026c).

**Tabla 8. Especificaciones de calidad del proyecto**

| Característica | Métrica e instrumento | Umbral y origen | Contexto |
| --- | --- | --- | --- |
| ACQ-01: fiabilidad | Diferencias entre saldo/historial esperado y registrado; consultas SQL y pruebas de integración. | 0 diferencias; RQ01 y HU02, CA6. | Base de prueba: fallo al guardar, migración y dos salidas de 80 con saldo 100. |
| ACQ-02: seguridad | Registros indebidos aceptados; solicitudes HTTP y consulta de la base de datos. | 0 registros indebidos; RQ02 y HU01, CA6-7. | Altas, edición, borrado y movimientos; sin autorización o token válido del formulario. |
| ACQ-03: eficiencia de desempeño | Percentil 95 (p95) del tiempo de recepción de la página; script Python. | p95 ≤ 1 s; RQ03. Nielsen (1993), citado en el Taller 5. | 1000 materias, sin historial, un cliente; cinco consultas de calentamiento y 100 mediciones por página. |
| ACQ-04: capacidad de interacción | Campos etiquetados/12 y errores que identifican el dato/4, en porcentaje; lista manual. | 100 % en ambos controles; RQ04 y WCAG 3.3.1/3.3.2 citadas en el Taller 5. | 4 campos por formulario: alta, edición y movimientos; errores de nombre, unidad, cantidad y saldo. |
| ACQ-05: compatibilidad | Casos con cantidad/unidad coincidentes/3, en porcentaje; integración y consulta CP-05 a CP-07. | 100 %; misma unidad y hasta dos decimales. RQ05 y HU02, CA4/CA7. | Formulario, PHP y base de inventario/movimientos; no incluye recetas ni módulos futuros. |

Nota. Umbrales del Taller 5; aceptación y validación conjunta pendientes. La consolidación no incorpora mediciones nuevas.

Julian revisa datos y acceso; Samuel, formularios y desempeño; Diana, comprensión. Fallar criterios incumple DoD 1; una migración no reproducible afecta DoD 5. La frecuencia sigue el plan original.

## Calidad y resultados medidos

El 07/10: 15 casos, 14 ejecutados, 12 aprobados, dos fallidos y uno pendiente. CP-13/14 fallaron por seguridad; CP-15 espera recetas. La Tabla 9 conserva esa línea base (Caicedo Ramirez & Riveros Martinez, 2026c).

**Tabla 9. Requisitos del Acta y evidencia del Taller 5**

| Requisito | Resultado y alcance |
| --- | --- |
| RQ01: fiabilidad | Saldo, historial y reversión ante fallo comprobados en la línea base. Julian publicó reproducción HU02 el 09/10; faltan aprobación formal y cierre completo de criterios. |
| RQ02: seguridad | CP-13/14 fallidos el 07/10. D-01/SCRUM-21 y D-02/SCRUM-22 abiertos; HU01 no cumple DoD 1. |
| RQ03: desempeño | Percentil 95 (p95): listado 9,67 ms; movimientos 7,45 ms. Meta de uso: 1 s. Laboratorio: 1000 materias, un cliente, 100 muestras tras cinco de calentamiento; sin recursos visuales ni red externa. |
| RQ04: interacción | Controles de 12 campos visibles y cuatro escenarios de error documentados; la comprensión por Diana no está aceptada. |
| RQ05: compatibilidad | Intercambio de cantidad/unidad entre formulario, PHP y base de inventario. Alcance parcial; no incluye módulos futuros. |

La Tabla 10 registra métricas medidas; las metas propuestas siguen pendientes de validación.

**Tabla 10. Indicadores del 7 de octubre de 2026**

| Métrica | Valor medido y decisión |
| --- | --- |
| Cobertura de líneas | 113/117 = 96,58 %, solo en funciones.php y movimientos-funciones.php. Meta propuesta 90 %; no representa todo el producto. |
| Complejidad ciclomática (CCN2) | Máximo 13; otra función 11. Meta propuesta 10; ambas requieren revisión. Se conserva el resultado medido. |
| Densidad de defectos | Dos defectos abiertos / dos HU probadas = 1,00 defecto/HU. Meta 0 antes de liberar el producto. |

PHPStan 2.3.0, nivel 5: 11 archivos analizados, 11 avisos en siete. Nueve sobre conexión y dos condiciones siempre falsas; requieren inspección, no son defectos confirmados. [Reporte completo de análisis estático](https://github.com/Samge34c/sistema-inventario-produccion/blob/75d97605c2bfb7c76e3041f426060341f552aa93/docs/calidad/taller5/2026-10-07/REPORTE_ANALISIS_ESTATICO.md).

## Evidencias del repositorio

Repositorio: [https://github.com/Samge34c/sistema-inventario-produccion](https://github.com/Samge34c/sistema-inventario-produccion). El [índice de documentación](https://github.com/Samge34c/sistema-inventario-produccion/blob/docs/avance-consolidado/docs/README.md) reúne fuentes y ramas. La Tabla 11 identifica tres commits verificables (Samge34c, 2026).

**Tabla 11. Tres commits representativos**

| Hash y fecha Bogotá | Cambio comprobado |
| --- | --- |
| [13f529e](https://github.com/Samge34c/sistema-inventario-produccion/commit/13f529e23e7dc1e299a41c3f2dbbc4b69bb6d706); 30/09, 21:16:43 | Publicación de los estándares adoptados por ambos integrantes. |
| [28a54fc](https://github.com/Samge34c/sistema-inventario-produccion/commit/28a54fc14b9a41917b11837869e398ce465d5eb7); 05/10, 19:36:52 | Implementación de movimientos y actualización transaccional del saldo. |
| [75d9760](https://github.com/Samge34c/sistema-inventario-produccion/commit/75d97605c2bfb7c76e3041f426060341f552aa93); 07/10, 15:00:22 | Publicación de pruebas, métricas y análisis estático del Taller 5. |

Nota. Los dos primeros corresponden a semana 9 y el tercero a semana 10, según el inicio académico confirmado por el equipo.

La Tabla 12 distingue publicación, pruebas y aprobación; las propuestas siguen sin integrar.

**Tabla 12. Propuestas y estado de revisión**

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

**Tabla 13. Evidencia publicada por Julian**

| Dato | Registro y alcance |
| --- | --- |
| Versión probada | 7a01251325365c8d2ebbead5b89d5732671f1463, rama feat/hu02-movimientos. Los archivos de evidencia se publicaron en 3d96f4a. |
| Fecha y herramientas | 09/10/2026, Bogotá; hora exacta no conservada. PHP 8.2.12, MariaDB 10.4.32, Node.js v24.21.0. |
| Integración PHP | 42 comprobaciones correctas reportadas. El archivo conserva el resultado final, sin la salida completa de las 42 comprobaciones. |
| Pruebas HTTP | 15 comprobaciones correctas y cero fallidas, en transcripción normalizada; script publicado. No se conserva la captura original de terminal. |
| Entorno | Según el reporte: base temporal prueba_hu02_http y aplicación aislada en localhost:8001; sin utilizar la base habitual. |
| Comportamientos | Entradas, salidas, pendientes, cantidades inválidas, token de seguridad, bloqueo de GET, edición del saldo y protección del historial. |

Las 42 y 15 comprobaciones pertenecen a dos conjuntos técnicos; no equivalen a 57 casos del plan. Los 15 casos del Taller 5, sus dos fallos históricos y CP-15 pendiente se conservan. La Tabla 13 resume la nueva evidencia; su enlace quedó registrado en SCRUM-23.

La protección frente a falsificación de solicitudes entre sitios (CSRF) probada en movimientos no certifica la autorización de usuarios ni corrige por sí misma HU01. D-01 y D-02 siguen abiertos. La publicación de resultados tampoco reemplaza una revisión formal del PR o el cumplimiento de todos los criterios de aceptación.

## Riesgos y respuestas

La Tabla 14 revisa los seis riesgos del Acta y conserva su probabilidad (P) e impacto (I) originales. Se distingue la respuesta prevista de lo ejecutado; la ausencia de evidencia de materialización no permite cerrar un riesgo (Caicedo Ramirez & Riveros Martinez, 2026a).

**Tabla 14. Situación de los riesgos originales**

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

La Tabla 15 propone revisiones dentro del alcance vigente. Acceso pendiente y protección parcial están comprobados; los casos futuros son preventivos. El equipo debe acordar esfuerzo y responsables de cada historia.

**Tabla 15. Prioridades propuestas y comprobación de cierre**

| Responsable | Actividad y evidencia |
| --- | --- |
| Julian; Samuel revisa | Sprint 2: SCRUM-21, acceso básico y cierre de sesión; rechazar acciones sin autorización. Revisar PR #11: cubre alta, no edición ni borrado; extender y probar CSRF en estas acciones. |
| Equipo; asignar HU03 | Sprint 2, HU03-HU06: acordar unidades y cantidades válidas; impedir borrados que eliminen ingredientes de recetas por cascada. Probar stock cero, redondeo y pendientes separados del saldo actual. |
| Equipo; Sprint 3 | HU09: verificar descuento completo de insumos, rechazo sin cambios si falta stock y ausencia de consumo duplicado al reenviar el registro. Son casos por preparar y ejecutar. |
| Equipo; Sprint 4 | HU10-HU12: acordar límites diarios/semanales y referencia horaria del negocio. Verificar sumas y hora pico por unidades vendidas; no hay resultados medidos aún. |
| Julian | SCRUM-23: completar contraste del Taller 5, revisar 11 avisos de PHPStan, umbrales y PR pendientes. La reproducción de HU02 publicada no sustituye aprobación formal. |
| Ambos | Samuel mantiene consolidado y revisión cruzada. Confirmar horas adicionales, responsable de HU03, cierre real de O1, adopción de WIP y acceso docente; ensayar la presentación. |

### Declaración de uso de inteligencia artificial

ChatGPT (Codex) apoyó consultas, pruebas, correcciones y redacción bajo autorización de Samuel. Las siete pruebas de SCRUM-22 fueron ejecutadas por el asistente en un entorno aislado. La revisión de código del 09/10 no agregó resultados de ejecución.

Julian registró revisión documental el 07/10 y publicó HU02 el 09/10: 42 comprobaciones de integración y 15 HTTP (Tabla 13). Samuel aportó fuentes, calendario y acuerdo de recuperación. Revisión formal, validación conjunta y aceptación del negocio siguen pendientes.

## Referencias

Caicedo Ramirez, J. C. (2026, 9 de octubre). Evidencia de ejecución de pruebas HU02 [Reporte de pruebas]. GitHub. [https://github.com/Samge34c/sistema-inventario-produccion/blob/3d96f4afb1640b66f3a8ef08c4a543e5e432490b/docs/pruebas/evidencias/julian-hu02-2026-10-09.md](https://github.com/Samge34c/sistema-inventario-produccion/blob/3d96f4afb1640b66f3a8ef08c4a543e5e432490b/docs/pruebas/evidencias/julian-hu02-2026-10-09.md)

Caicedo Ramirez, J. C., & Riveros Martinez, S. E. (2026a, 20 de septiembre). Acta de constitución del proyecto: Sistema de inventario, recetas y planificación de producción [Trabajo académico no publicado]. Universidad Santo Tomás.

Caicedo Ramirez, J. C., & Riveros Martinez, S. E. (2026b, 30 de septiembre). Estándares del equipo [Documento de proyecto]. GitHub. [https://github.com/Samge34c/sistema-inventario-produccion/blob/13f529e23e7dc1e299a41c3f2dbbc4b69bb6d706/ESTANDARES.md](https://github.com/Samge34c/sistema-inventario-produccion/blob/13f529e23e7dc1e299a41c3f2dbbc4b69bb6d706/ESTANDARES.md)

Caicedo Ramirez, J. C., & Riveros Martinez, S. E. (2026c, 7 de octubre). Plan de control de calidad del sistema de inventario, recetas y planificación de producción [Trabajo académico no publicado]. Universidad Santo Tomás.

Samge34c. (2026). Sistema de inventario, recetas y planificación de producción [Repositorio de código y evidencias]. GitHub. [https://github.com/Samge34c/sistema-inventario-produccion](https://github.com/Samge34c/sistema-inventario-produccion)

Universidad Santo Tomás. (s. f.). Avance del proyecto: Qué debe presentar el estudiante, paso a paso [Guía de Gerencia de Software].
