# Protección del registro de materias primas

## Problema y resultado

SCRUM-22 / D-02: CP-14 permitió guardar una materia prima desde un origen ajeno, con sesión PHP y sin el código de seguridad del formulario. El cambio añade un token aleatorio ligado a la sesión y lo comprueba antes de conectar a la base de datos. La solicitud inválida responde 403 sin escribir; el formulario válido conserva el registro habitual.

## Comprobación ejecutada

El 8 de octubre de 2026 a las 23:46, America/Bogota, se ejecutaron siete casos HTTP con verificación del número de registros en una base aleatoria de datos ficticios: origen ajeno sin token, token incorrecto, token de tipo arreglo, token de otra sesión, falta de sesión/token, alta válida y cantidad negativa. Los siete aprobaron. Los cinco rechazos devolvieron 403 sin crear registros; el alta válida creó exactamente uno y la cantidad negativa no creó ninguno.

Entorno: PHP 8.3.6, MariaDB 10.11.14, Python 3.12.14. Se comprobó sintaxis y formato PHP de los tres archivos modificados. Resultado: [resultados-scrum22-2026-10-08.json](resultados-scrum22-2026-10-08.json).

## Reproducción

Usar una instalación aislada con el esquema del repositorio y datos ficticios. Iniciar el servidor PHP con sesiones habilitadas. Configurar `SCRUM22_URL`, `SCRUM22_PRUEBA_DSN`, `SCRUM22_PHP` y, si corresponde, `SCRUM22_USUARIO` / `SCRUM22_CLAVE` mediante el entorno. No publicar credenciales. Ejecutar:

```bash
python pruebas/scrum22-http.py
```

El script crea un único registro ficticio de alta válida, con prefijo aleatorio, y no modifica registros existentes. Desechar la base de prueba al terminar. El resultado incluye fecha real y conteos antes/después.

## Alcance y revisión

La comprobación repite CP-14 a nivel de servidor; no demuestra un ataque en navegador autenticado. El token no sustituye autenticación/autorización: D-01 sigue siendo trabajo de Julian. Este cambio solo protege el alta de materias primas; no amplía el alcance a edición, eliminación o movimientos. La rama parte de develop, sin integrar HU02. Julian debe reproducir y revisar antes de integrar; D-02 no se cierra automáticamente.
