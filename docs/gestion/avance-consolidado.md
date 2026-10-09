# Avance del proyecto

Corte: 9 de octubre de 2026, America/Bogota.

Avance del proyecto de inventario,
recetas y planificación de producción

Julian Camilo Caicedo Ramirez y Samuel Esteban Riveros Martinez

Ingeniería de Sistemas, Universidad Santo Tomás

Gerencia de Software

Stefany Gomez Riveros

Fecha de entrega: pendiente de confirmar

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
Corte histórico: 9 de octubre de 2026, Bogotá; no es la fecha de entrega. Sesión 21, plazo, nombre del archivo y corte final: pendientes de confirmar. El conteo publicado llega a semana 10; semana 11 sin cubrir.

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
| Guías y formato | PSR-12 para PHP y JavaScript Standard Style. Prettier publicado; PHP CS Fixer propuesto en PR #9. PHPStan no figura en el Taller 4: su uso posterior está documentado en el Taller 5. Fecha y motivo del acuerdo: pendientes de confirmar. |
| Commits | tipo(alcance): descripción en español, con verbo en imperativo y un cambio lógico. Usar feat, fix, docs, refactor, test, style o chore según el cambio. |
| Ramas | main conserva versiones estables; develop integra trabajo revisado. Ramas feat/<hu>-<nombre>, fix/<nombre> y docs/<nombre>, creadas desde develop. |
| Preparación | Definición de preparado (Definition of Ready, DoR): historia, criterios, dependencias, estimación, sprint, flujo si aplica y caso con resultado esperado. |
| Terminación | Definición de terminado (Definition of Done, DoD): criterios reproducidos por el otro integrante, sintaxis, flujo web, formato, SQL si aplica, ausencia de secretos y documentación. Integrar tras revisión. |

Hasta el Sprint 2, Julian asume requisitos, backlog, lógica y base de datos; Samuel, seguimiento Scrum, interfaz, pruebas y documentación. La rotación prevista comienza el 19/10. Estas responsabilidades generales no sustituyen la asignación de cada tarea.

Diana Marcela Caicedo, propietaria e interlocutora, debe validar los roles propuestos de producción y ventas. Su aceptación está pendiente. Laboratorios: MariaDB 10.11.14 y 10.4.32; MySQL 8 y entorno final sin verificar.

## Control de cambios

La Tabla 3 separa hechos y decisiones pendientes. CA: criterio de aceptación. Fechas de 2026, Bogotá; versiones radicadas: pendientes de confirmar.

**Tabla 3. Control de cambios frente a los talleres**

| Qué cambió | Fecha | Por qué | Quién lo decidió |
| --- | --- | --- | --- |
| Acta: HU01-HU03 pasaron al Sprint 2. | 05/10, 12:55 | O1 pendiente al 04/10; causa pendiente de confirmar. | Samuel registró; decisión no documentada. |
| T4: NC-01, HU01 integrada sin revisión. | 05/10 | Causa y decisión correctiva: pendientes de confirmar. | Decisión no documentada. |
| T4: Prettier publicado; PHP CS Fixer propuesto, PR #9. | 30/09 y 05/10 | Formato previsto; configuración posterior. Motivo: pendiente de confirmar. | Samuel preparó; acuerdo pendiente de confirmar. |
| T5: pruebas y métricas publicadas, PR #10. | 07/10 | Diferencias frente a lo radicado: pendiente de confirmar. | Samuel autorizó apoyo; cambios pendientes de confirmar. |
| CA4-7 en Jira después de las comprobaciones. | 07/10, 21:30 | HU01: validaciones/RQ02; HU02: reglas del 05/10. Motivo pendiente de confirmar. | Samuel autorizó; aceptación de Diana pendiente. |
| Acta: meta 09/10 y mayor dedicación comunicadas. | 08/10 | Responder al arrastre; horas y resultado pendientes de confirmar. | Samuel comunicó acuerdo con Julian. |
| D-02: corrección propuesta, siete pruebas; PR #11. | 08/10, 23:46 | Evitar altas sin token válido del formulario. | Samuel autorizó; ejecutó el asistente. |
| Tablero: máximo 1 + 1; SCRUM-24. | 09/10 | WIP 1 + 1; motivo pendiente de confirmar. | Samuel configuró; acuerdo de Julian pendiente de confirmar. |
| T5: reporte independiente HU02 publicado. | 09/10 | Actualiza reproducción HU02 pendiente; resto de revisión sin completar. | Julian publicó; aprobación formal pendiente. |
| PHPStan: uso posterior a T4, documentado en T5. | 07/10; (reporte) | Acuerdo: fecha y motivo pendientes de confirmar. | Decisión no documentada. |

Nota. Decisión no documentada: decisión original no documentada; confirmación pendiente. Las fechas de reporte no acreditan adopción.

## Estado real frente al acta

La Tabla 4 responde las cuatro preguntas de la guía. Conserva el corte del 09/10; el resultado final de la meta de ese día está pendiente de confirmar.

**Tabla 4. Contraste escrito con el Acta**

| Pregunta | Respuesta al corte histórico |
| --- | --- |
| Qué se cumplió como estaba previsto | Se conservan las 12 HU y las tecnologías del Acta. Hay inventario integrado y movimientos publicados con pruebas; esto no acredita el cierre de O1. |
| Qué se desvió, cuánto y por qué | O1 venció el 04/10 y seguía abierto al 09/10: cinco días. NC-01 registra HU01 integrada sin revisión el 05/10. Causas concretas y registros originales: pendientes de confirmar. |
| Qué decidió el equipo | Jira registra arrastre el 05/10, 12:55; decisión original no documentada; confirmación pendiente. Samuel comunicó más dedicación el 08/10; horas pendientes de confirmar. No consta el registro original del acuerdo. |
| Qué hitos pasaron y cuáles vienen | Venció O1 el 04/10; siguen O2 (18/10), O3 (01/11) y O4 (17/11 aproximado). La meta del 09/10 queda como registro; resultado final pendiente de confirmar. |

La Tabla 5 es una propuesta sujeta a capacidad real; no fija nuevas fechas de cumplimiento.

**Tabla 5. Propuesta de recuperación sujeta a capacidad real**

| Trabajo | Responsable y condición de cierre |
| --- | --- |
| HU01 y HU02 | Julian atiende D-01 y revisa PR #11; Samuel hace revisión cruzada. Incorporar cada cambio después de la revisión y aprobación del otro integrante. |
| HU03: recetas | Responsable y horas adicionales: pendiente de confirmar. Preparar criterios, implementar y ejecutar CP-15. Nueva fecha: pendiente de confirmar. |
| HU04-HU06 | Dependen de inventario y recetas. Hito original: 18/10; viabilidad sujeta a capacidad real. Inicio del Sprint 3 previsto en el Acta: 19/10. |

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

Al corte histórico, ninguna HU aparece Finalizada. D-01/D-02 no aumentan automáticamente los 60 SP. HU02/HU03 no tienen persona asignada en Jira. Responsable de HU03, horas adicionales y nueva fecha: pendientes de confirmar; recuperación sujeta a capacidad real (Tabla 5).

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

Samuel reportó invitación y selección de Lector. Acceso de la profesora probado con cuenta invitada, fecha y resultado: pendientes de confirmar. Este texto no acredita que pueda entrar.

### Historial comprobado y periodo académico

Samuel confirmó semana 1 desde el 04/08/2026: semana 6, 08-14/09; semana 11, 13-19/10. Referencia del equipo, sin calendario institucional aportado; el corte está en semana 10.

Historial al 09/10: seis ítems distintos y 10 cambios de estado. Tres HU (SCRUM-9/10/11) aportan siete cambios; dos ejemplos (SCRUM-2/3), dos; una épica (SCRUM-5), uno. Ocurrieron el 04-05/10. Se excluyen ediciones y comentarios. Conteo final de semanas 6-11 y fecha de corte final: pendientes de confirmar.

## Atributos de calidad y condiciones de evaluación

La Tabla 8 integra los cinco atributos con característica, métrica e instrumento, umbral y contexto. Conserva fuentes y alcance del Taller 5; las metas no prueban cumplimiento (Caicedo Ramirez & Riveros Martinez, 2026c).

**Tabla 8. Especificaciones de calidad del proyecto**

| Característica | Métrica e instrumento | Umbral y origen | Contexto |
| --- | --- | --- | --- |
| ACQ-01: fiabilidad | Diferencias entre saldo/historial esperado y registrado; consultas SQL y pruebas de integración. | 0 diferencias; RQ01 y HU02, CA6. | Base de prueba: fallo al guardar, migración y dos salidas de 80 con saldo 100. |
| ACQ-02: seguridad | Registros indebidos aceptados; solicitudes HTTP y consulta de la base de datos. | 0 registros indebidos; RQ02 y HU01, CA6-7. | Altas, edición, borrado y movimientos; sin autorización o token válido del formulario. |
| ACQ-03: eficiencia de desempeño | Percentil 95 (p95): tiempo que no supera el 95 % de consultas; recepción de página con Python. | p95 ≤ 1 s; RQ03. Nielsen (1993), según Taller 5. | 1000 materias, sin historial, un cliente; cinco consultas de calentamiento y 100 mediciones por página. |
| ACQ-04: capacidad de interacción | Campos etiquetados/12 y errores que identifican el dato/4, en porcentaje; lista manual. | 100 % en ambos controles; RQ04 y WCAG 3.3.1/3.3.2 citadas en el Taller 5. | 4 campos por formulario: alta, edición y movimientos; errores de nombre, unidad, cantidad y saldo. |
| ACQ-05: compatibilidad | Casos con cantidad/unidad coincidentes/3, en porcentaje; integración y consulta CP-05 a CP-07. | 100 %; misma unidad y hasta dos decimales. RQ05 y HU02, CA4/CA7. | Formulario, PHP y base de inventario/movimientos; no incluye recetas ni módulos futuros. |

Nota. Nielsen y WCAG se retoman del Taller 5 (Caicedo Ramirez & Riveros Martinez, 2026c); sin consulta nueva ni mediciones adicionales. Metas y aceptación por validar.

Responsabilidades previstas: Julian, datos/acceso; Samuel, formularios/desempeño; Diana, comprensión. Fallar criterios incumple DoD 1; migración no reproducible, DoD 5. Frecuencia: plan del Taller 5.

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

La Tabla 10 conserva mediciones; las metas deben revisarse y acordarse.

**Tabla 10. Indicadores del 7 de octubre de 2026**

| Métrica | Valor medido y decisión |
| --- | --- |
| Cobertura de líneas | 113/117 = 96,58 %, solo en funciones.php y movimientos-funciones.php. Meta propuesta 90 %; no representa todo el producto. |
| Complejidad ciclomática (CCN2) | Máximo 13; otra función 11. Meta propuesta 10; ambas requieren revisión. Se conserva el resultado medido. |
| Densidad de defectos | Dos defectos abiertos / dos HU probadas = 1,00 defecto/HU. Meta 0 antes de liberar el producto. |

PHPStan inspecciona código sin ejecutarlo. 2.3.0, nivel 5: 11 archivos, 11 avisos en siete (nueve sobre conexión; dos condiciones siempre falsas). Requieren revisión; no son defectos confirmados. [Reporte estático](https://github.com/Samge34c/sistema-inventario-produccion/blob/75d97605c2bfb7c76e3041f426060341f552aa93/docs/calidad/taller5/2026-10-07/REPORTE_ANALISIS_ESTATICO.md).

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

La Tabla 13 resume el [reporte de ejecución de HU02](https://github.com/Samge34c/sistema-inventario-produccion/blob/3d96f4afb1640b66f3a8ef08c4a543e5e432490b/docs/pruebas/evidencias/julian-hu02-2026-10-09.md) publicado por Julian Caicedo el 09/10 en PR #8 (Caicedo Ramirez, 2026). Conserva responsable, fecha, versión, herramientas y límites de la evidencia; no le atribuye pruebas anteriores del asistente.

**Tabla 13. Evidencia publicada por Julian**

| Dato | Registro y alcance |
| --- | --- |
| Versión probada | 7a01251325365c8d2ebbead5b89d5732671f1463, rama feat/hu02-movimientos. Los archivos de evidencia se publicaron en 3d96f4a. |
| Fecha y herramientas | 09/10/2026, Bogotá; hora exacta no conservada. PHP 8.2.12, MariaDB 10.4.32, Node.js v24.21.0. |
| Integración PHP | 42 comprobaciones correctas reportadas. El archivo conserva el resultado final, sin la salida completa de las 42 comprobaciones. |
| Pruebas HTTP | 15 comprobaciones correctas y cero fallidas reportadas. Salida de pruebas organizada; no se conserva la captura original. Script publicado. |
| Entorno | Según el reporte: base temporal prueba_hu02_http y aplicación aislada en localhost:8001; sin utilizar la base habitual. |
| Comportamientos | Entradas, salidas, pendientes, cantidades inválidas, token de seguridad, bloqueo de GET, edición del saldo y protección del historial. |

Las 42 y 15 comprobaciones pertenecen a dos conjuntos técnicos; no equivalen a 57 casos del plan. Se conservan los 15 casos del Taller 5, dos fallos históricos y CP-15 pendiente. El enlace del reporte quedó registrado en SCRUM-23.

La protección frente a falsificación de solicitudes entre sitios (CSRF) probada en movimientos no certifica la autorización de usuarios ni corrige por sí misma HU01. D-01 y D-02 siguen abiertos. La publicación de resultados tampoco reemplaza una revisión formal del PR o el cumplimiento de todos los criterios de aceptación.

## Riesgos y respuestas

La Tabla 14 conserva los seis riesgos, responsables, probabilidad (P) e impacto (I) del Acta. Distingue respuesta prevista, ejecución y vigencia (Caicedo Ramirez & Riveros Martinez, 2026a).

**Tabla 14. Situación de los riesgos originales**

| Riesgo y responsable | Materialización, respuesta y vigencia |
| --- | --- |
| R1: validación tardía; Julian; P media, I alto | No tenemos evidencia de que haya causado un problema. Consultar y registrar pendientes: previsto. Aceptación de Diana no consta; respuesta sin evaluar. Vigente. |
| R2: aumento de alcance; Samuel; P media, I alto | Sin ampliación demostrada. Previsto: diferir extras; se conservan 12 HU. Sin solicitudes nuevas para evaluar la respuesta. Vigente. |
| R3: unidades inconsistentes; Julian; P media, I alto | Sin inconsistencia demostrada. Previsto: unidad por materia y casos conocidos. Pruebas inventario/HU02 publicadas; recetas pendientes. Respuesta parcial; vigente. |
| R4: menor disponibilidad; Samuel; P alta, I medio | Atraso demostrado; causa pendiente de confirmar. Previsto: dividir tareas y revisar semanalmente. Mayor dedicación comunicada; horas y resultados pendientes de confirmar. Vigente. |
| R5: datos insuficientes; Julian; P media, I medio | Sin error atribuible comprobado. Previsto: probar faltantes, pendientes y ventas. Hay datos ficticios de inventario; faltan datos validados. Respuesta parcial; vigente. |
| R6: integración tardía; Samuel; P media, I alto | Previsto: integrar cada sprint y probar flujo completo. HU01 integrada; HU02 en rama. Todavía no podemos comprobar si la respuesta funcionó en todo el sistema. Vigente. |

### Incumplimientos, defectos y recurso externo

NC-01, 05/10: integrar sin revisión amenaza la calidad. El avance registra el hecho; causa, decisión correctiva y registro original: pendientes de confirmar.

D-01/D-02, 07/10: amenazan autorización e integridad (Taller 5; SCRUM-21/22). PR #11 propone protección del alta, con siete pruebas del asistente. Revisión e integración pendientes.

CDN: servicio externo que entrega Bootstrap. Su fallo afecta la interfaz; fecha pendiente de confirmar. Se usó Bootstrap local en laboratorio, sin comprobar corrección del recurso externo.

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

ChatGPT (Codex) apoyó consultas, pruebas, correcciones y redacción bajo autorización de Samuel. Las siete pruebas de SCRUM-22 fueron ejecutadas por el asistente en un entorno aislado. Autor y alcance de la revisión de código del 09/10: pendientes de confirmar.

Verificación manual del equipo de lo generado con IA, persona, fuente contrastada y registro: pendientes de confirmar. La evidencia HU02 publicada se conserva por separado (Tabla 13). Revisión formal, validación conjunta y aceptación del negocio siguen pendientes; esta edición no acredita verificaciones adicionales.

## Referencias

Caicedo Ramirez, J. C. (2026, 9 de octubre). Evidencia de ejecución de pruebas HU02 [Reporte de pruebas]. GitHub. [https://github.com/Samge34c/sistema-inventario-produccion/blob/3d96f4afb1640b66f3a8ef08c4a543e5e432490b/docs/pruebas/evidencias/julian-hu02-2026-10-09.md](https://github.com/Samge34c/sistema-inventario-produccion/blob/3d96f4afb1640b66f3a8ef08c4a543e5e432490b/docs/pruebas/evidencias/julian-hu02-2026-10-09.md)

Caicedo Ramirez, J. C., & Riveros Martinez, S. E. (2026a, 20 de septiembre). Acta de constitución del proyecto: Sistema de inventario, recetas y planificación de producción [Trabajo académico no publicado]. Universidad Santo Tomás.

Caicedo Ramirez, J. C., & Riveros Martinez, S. E. (2026b, 30 de septiembre). Estándares del equipo [Documento de proyecto]. GitHub. [https://github.com/Samge34c/sistema-inventario-produccion/blob/13f529e23e7dc1e299a41c3f2dbbc4b69bb6d706/ESTANDARES.md](https://github.com/Samge34c/sistema-inventario-produccion/blob/13f529e23e7dc1e299a41c3f2dbbc4b69bb6d706/ESTANDARES.md)

Caicedo Ramirez, J. C., & Riveros Martinez, S. E. (2026c, 7 de octubre). Plan de control de calidad del sistema de inventario, recetas y planificación de producción [Trabajo académico no publicado]. Universidad Santo Tomás.

Samge34c. (2026). Sistema de inventario, recetas y planificación de producción [Repositorio de código y evidencias]. GitHub. [https://github.com/Samge34c/sistema-inventario-produccion](https://github.com/Samge34c/sistema-inventario-produccion)

Universidad Santo Tomás. (s. f.). Avance del proyecto: Qué debe presentar el estudiante, paso a paso [Guía de Gerencia de Software].
