# HU02 — Entradas, salidas y materiales pendientes

Jira: [SCRUM-10](https://sistema-inventario-produccion.atlassian.net/browse/SCRUM-10).
Rama: `feat/hu02-movimientos`, creada desde `develop` en `28e8a15`.
Fecha real de ejecución: 2026-10-05, aproximadamente 19:34, America/Bogota
(2026-10-06 00:34 UTC). Autoría: trabajo asistido por IA bajo la instrucción
de Samuel; no constituye una revisión de Julian ni aceptación del negocio.

## Instalación reproducible

PHP 8+ de 64 bits con PDO MySQL y mbstring, MySQL 8 o MariaDB compatible,
y un directorio de sesiones PHP existente y escribible.

- Base nueva: importar `database/schema.sql` una sola vez.
- Base del esquema anterior: respaldar antes de aplicar una sola vez
  `database/migraciones/002-movimientos-pendientes.sql`. El script conserva
  materias primas y movimientos. Los ALTER realizan commits implícitos.
- No aplicar ambas rutas sobre la misma base. No borrar una base existente.
- La migración espera el nombre de FK `movimientos_inventario_ibfk_1` creado
  por el esquema previo del repositorio. Si el esquema fue personalizado,
  revisar `SHOW CREATE TABLE movimientos_inventario` y adaptar una copia
  antes de ejecutar; no quitar una restricción a ciegas.
- Configurar `config/database.php` solo localmente, sin subir credenciales.
  Ejecutar `php -S localhost:8000` desde la raíz.

## Caso principal para otro integrante

Usar una base local de prueba, sin datos de negocio. En Materias primas crear
**Harina de prueba**, unidad **g**, existencia **10000**, stock mínimo **1000**.
Abrir **Movimientos** y seleccionar esa materia prima en cada operación.

| Paso | Tipo / dato | Resultado numérico esperado |
|---|---|---|
| 1 | ENTRADA, 2000 g | Existencia 12000 g; una entrada visible. |
| 2 | SALIDA, 1000 g | Existencia 11000 g; una salida visible. |
| 3 | PENDIENTE, 5000 g | Existencia sigue en 11000 g; 5000 g en listado separado. |
| 4 | SALIDA, 11000.01 g | Error controlado; existencia 11000; no nueva salida. |
| 5 | 0, negativo, más de dos decimales o texto por POST | Rechazo; ningún cambio de saldo. |
| 6 | Editar existencia a 9000 o cambiar unidad tras movimientos | Rechazo; saldo e interpretación de cantidades conservados. |
| 7 | Eliminar la materia prima anterior | Rechazo controlado; materia e historial conservados. |

Revisar consola del navegador. Abrir una nueva sesión si el token de formulario
vence. Cada cantidad se interpreta en la unidad de la materia prima: no hay
conversión automática entre g/kg ni entre unidades. PENDIENTE no es una entrada
real, no aumenta saldo y no se incluye en cálculos de disponibilidad actual.
HU06 deberá utilizar estos registros como disponibilidad futura. Todavía no hay
conciliación de recepción de pendientes: al recibir físicamente material,
registrar ENTRADA; no usar la suma de pendientes como saldo actual.

## Pruebas automatizadas

El script de integración crea nombres aleatorios `prueba_hu02_...` y elimina
únicamente las bases que creó. Necesita permisos CREATE/DROP sobre un servidor
local de pruebas y disponibilidad de `proc_open` para los dos procesos concurrentes.
No apuntar a datos reales. Exportar la versión original del esquema:

```bash
git show 28e8a15:database/schema.sql > /tmp/schema-hu02-anterior.sql
HU02_PRUEBA_DSN='mysql:host=127.0.0.1;port=3306;charset=utf8mb4' \
HU02_SCHEMA_ANTERIOR=/tmp/schema-hu02-anterior.sql \
php pruebas/hu02-integracion.php
```

Definir `HU02_PRUEBA_USUARIO` y `HU02_PRUEBA_CLAVE` en el entorno si corresponde;
no guardar sus valores en Git. La prueba incluye rollback con un trigger de
fallo, exactitud en centésimas, límite DECIMAL, FK RESTRICT, modificación
protegida y dos salidas simultáneas de 80 sobre 100: una aceptada, otra rechazada,
saldo final 20. También aplica la migración al esquema anterior con datos.

Para HTTP o navegador, usar otro despliegue local con esquema nuevo y conexión
a una base desechable. Estos scripts dejan materias primas de prueba en esa
base; no eliminan ni alteran una base ajena.

```bash
HU02_URL=http://127.0.0.1:8000 python3 pruebas/hu02-http.py
```

Navegador automatizado: Node.js, paquete Playwright y Chromium. Instalar
Playwright en un directorio temporal de herramientas o usar una instalación
existente; no agregarlo como dependencia de producción.

```bash
HU02_URL=http://127.0.0.1:8000 node pruebas/hu02-web.js
```

Se puede definir `HU02_CHROME` para un ejecutable Chromium existente y
`HU02_CAPTURA` para una captura local. Si el CDN no está accesible en el entorno
local, descargar exactamente Bootstrap 5.3.8 y definir `HU02_BOOTSTRAP_PRUEBA`
con la ruta del CSS. Playwright servirá esa misma hoja únicamente como recurso
de prueba. Esto no certifica disponibilidad del CDN en producción.

## Resultados realmente observados

Entorno aislado: PHP 8.3.6, MariaDB 10.11.14, Chromium Headless Shell
131.0.6778.204; datos sintéticos. No se ejecutó MySQL 8 ni una base del negocio.

- Integración: **42 comprobaciones correctas**, incluyendo esquema nuevo,
  migración con datos, concurrencia y rollback.
- HTTP: **15 comprobaciones correctas**, incluyendo sesión, CSRF, cantidades,
  HTML escapado y conservación de historial. No evalúa consola JavaScript.
- Navegador: **18 comprobaciones correctas**, con cero excepciones JavaScript
  y cero errores de consola en la ejecución con CSS local de prueba.
- El intento con CDN real completó 17 comprobaciones y falló por
  `net::ERR_EMPTY_RESPONSE` al cargar recursos externos. No se declara exitoso.
- Los primeros intentos fallaron por un ejecutable Chromium incompleto y un
  directorio de sesiones ausente en el runtime aislado; se corrigió la
  preparación del entorno y se repitió. Ninguno cuenta como ejecución exitosa.
- CSS de prueba: Bootstrap 5.3.8, SHA-256
  `d85327d99c7a3ee1f9b5d0500d1370acea3ad2db39c163c2f51f232baedbdede`.
- Logs de las ejecuciones finales: [integración](resultados/hu02-integracion-2026-10-05.txt),
  [HTTP](resultados/hu02-http-2026-10-05.txt), [navegador](resultados/hu02-web-2026-10-05.txt).
- Las fechas almacenadas en los registros sintéticos dependen del reloj/zona
  del servidor de pruebas; no se usan como fecha de ejecución ni del negocio.

## DoD y límites

| Punto de ESTANDARES.md | Evidencia / estado |
|---|---|
| 1 | Procedimiento y resultados del autor disponibles; **pendiente ejecución por Julian**, exigida expresamente por el estándar. |
| 2 | Lint PHP de archivos modificados documentado en PR. |
| 3 | Flujo ejecutado en Chromium; CSS local explícito, CDN real no certificado. |
| 4 | PHP CS Fixer 3.95.27 con @PSR12 y Prettier 3.9.9; comandos/resultados en PR. La incorporación del formateador PHP va en PR de mantenimiento independiente. |
| 5 | Esquema y migración SQL versionados; ambos ejecutados en MariaDB. |
| 6 | Sin credenciales nuevas; revisión del diff/escaneo sin coincidencias conocidas no garantiza ausencia universal. |
| 7 | README y este caso actualizados. |

El PR se publica como borrador y SCRUM-10 continúa En curso mientras falta la
comprobación del otro integrante del punto 1. Después podrá marcarse listo y
pasar a En revisión; aprobación formal e integración siguen pendientes. No hay
Done ni aprobación retroactiva. La no conformidad HU01 se mantiene separada.

Límites conocidos del proyecto: no hay autenticación/autorización que satisfaga
RQ02; el token CSRF nuevo protege este formulario, no sustituye control de acceso.
Los formularios antiguos HU01 siguen pendientes de protección CSRF y validación
más estricta. El error de conexión original expone información técnica; debe
corregirse en mantenimiento separado. No se certifica seguridad integral.
Taller 5 pendiente: no se presentan estas comprobaciones como su métrica ISO.
