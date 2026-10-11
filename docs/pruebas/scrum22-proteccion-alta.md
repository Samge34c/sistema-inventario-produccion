# Protección del registro de materias primas

## Problema y resultado

SCRUM-22 / D-02: CP-14 permitió guardar una materia prima desde un origen ajeno, con sesión PHP y sin el código de seguridad del formulario. El cambio añade un token aleatorio ligado a la sesión y lo comprueba antes de conectar a la base de datos. La solicitud inválida responde 403 sin escribir; el formulario válido conserva el registro habitual.

## Comprobación ejecutada

El 8 de octubre de 2026 a las 23:46, hora local de Villavicencio (UTC−5), se ejecutaron siete casos HTTP con verificación del número de registros en una base aleatoria de datos ficticios: origen ajeno sin token, token incorrecto, token de tipo arreglo, token de otra sesión, falta de sesión/token, alta válida y cantidad negativa. Los siete aprobaron. Los cinco rechazos devolvieron 403 sin crear registros; el alta válida creó exactamente uno y la cantidad negativa no creó ninguno.

Entorno: PHP 8.3.6, MariaDB 10.11.14, Python 3.12.14. Se comprobó sintaxis y formato PHP de los tres archivos modificados. Resultado: [resultados-scrum22-2026-10-08.json](resultados-scrum22-2026-10-08.json).

## Corrección del procedimiento — 11 de octubre de 2026

La revisión independiente detectó que el procedimiento anterior podía dirigir las solicitudes a una aplicación conectada a `sistema_inventario`, mientras el verificador contaba registros en otra base. Por esa razón, se suspendió la reproducción. Los resultados del 8 de octubre se conservan como evidencia histórica; no acreditan que el procedimiento anterior impidiera una configuración equivocada.

El nuevo preparador crea una base nueva `prueba_scrum22_` con identificador aleatorio, una copia temporal de la aplicación y un servidor propio en `127.0.0.1`. Solo cambia la conexión de esa copia. Antes de obtener el formulario y antes de cada envío, compara el nombre de la base, un identificador exclusivo de la ejecución y la huella del archivo de conexión usado por la aplicación. Si no coinciden, detiene las pruebas antes de enviar datos. La comprobación auxiliar existe únicamente en la copia temporal.

Se ejecutaron los siete casos con el procedimiento corregido: todos aprobaron. También se comprobaron cuatro configuraciones incorrectas: aplicación y verificador en bases distintas, falta de comprobación del servidor, conexión del verificador a la base habitual y un identificador de ejecución incorrecto. Las cuatro se detuvieron sin solicitudes POST. Una base habitual simulada, creada con datos ficticios en una instancia nueva de MariaDB, conservó sus registros. Esto no representa una comprobación sobre datos reales del proyecto.

Salidas conservadas:
- [Siete casos y registro del servidor](resultados-scrum22-aislamiento-2026-10-11.json).
- [Rechazos de configuraciones incorrectas](resultados-guardas-scrum22-2026-10-11.json).

Estas dos salidas contienen la fecha, las versiones y los resultados de las comprobaciones anteriores. La reproducción independiente publicada después se registra a continuación; no se confunde con una aprobación de PR #11.

## Resultado independiente publicado — 11/10/2026

Julian publicó [la salida original de siete casos](resultado-scrum22-julian-20261011.json) en el commit `dec9ff79375c875497eba31cff763aab65c71924`. Ejecución de 17:41:39 a 17:41:44, hora local de Villavicencio: PHP 8.2.12, MariaDB 10.4.32 y Python 3.12.10. Los siete casos aprobaron; se confirmó aislamiento antes de cada POST, configuración original sin cambios y eliminación de la base temporal. El commit publica únicamente esa evidencia; no cambia el script.

El JSON no contiene el hash del checkout ejecutado: Julian debe confirmarlo a partir de su registro local, sin reconstruir un dato ausente. La segunda solicitud de cambios se retiró con referencia a su aclaración en SCRUM-22, comentario 10024: el TypeError alegado no quedó demostrado. La reproducción está publicada; aprobación formal e integración siguen pendientes.

## Procedimiento para otra ejecución independiente

Requisitos: PHP con PDO MySQL, un servicio local MySQL/MariaDB activo, Python con la zona `America/Bogota` disponible y un usuario local que pueda crear y eliminar bases temporales. Si falta alguno, detenerse y registrar el impedimento; no sustituirlo por la base habitual ni declarar resultados.

Actualizar la rama `fix/scrum22-proteccion-alta` y registrar el resultado de `git rev-parse HEAD`. Desde la raíz del repositorio, en PowerShell:

```powershell
$env:SCRUM22_SERVIDOR_DSN = "mysql:host=127.0.0.1;port=3306;charset=utf8mb4"
$env:SCRUM22_PHP = "php"
python pruebas/scrum22-aislado.py --salida docs/pruebas/resultado-scrum22-julian.json
```

Ajustar el puerto y la ruta real de PHP a la instalación local. La conexión del servidor debe omitir `dbname`; el preparador selecciona exclusivamente la base nueva. Si se requieren credenciales, establecer `SCRUM22_USUARIO` y `SCRUM22_CLAVE` en el entorno local sin publicarlas. Para otra ejecución, elegir un archivo de salida nuevo: no sobrescribir evidencia previa.

No ejecutar directamente `scrum22-http.py` ni configurar manualmente la URL o la base de prueba. El preparador inicia y detiene su propio servidor, elimina su base al terminar y conserva la configuración original. El resultado aprobado debe contener siete casos correctos, aislamiento verificado, configuración original sin cambios y base temporal eliminada. Adjuntar esa salida real a SCRUM-22 y comentar PR #11 con la fecha, el hash completo y el entorno. La revisión y la decisión de aprobación corresponden a Julian.

## Alcance y revisión

La comprobación repite CP-14 a nivel de servidor; no demuestra un ataque en navegador autenticado. El token no sustituye autenticación/autorización: D-01 sigue siendo trabajo de Julian. Este cambio solo protege el alta de materias primas; no amplía el alcance a edición, eliminación o movimientos. La rama parte de develop, sin integrar HU02. La reproducción de Julian ya está publicada. Falta su revisión formal final; integrar únicamente después de aprobación y comprobar la versión integrada antes de cerrar D-02.
