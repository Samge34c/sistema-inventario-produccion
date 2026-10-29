# Recuperación funcional de HU01–HU03

Fecha: 12 de octubre de 2026, hora local de Villavicencio (UTC−5).

## Cambios preparados

- HU01 / SCRUM-9: inicio y cierre de sesión, rechazo de escritura sin una cuenta activa, protección del alta contra solicitudes sin token y protección de edición y eliminación. D-01 y D-02 se comprueban sobre la propuesta conjunta.
- HU02 / SCRUM-10: conserva entradas, salidas, pendientes separados, exactitud en centésimas, historial, transacciones y migración. Se adaptaron los clientes de prueba al inicio de sesión y a los tokens de cada formulario.
- HU03 / SCRUM-11: registro y consulta de recetas por unidad, con ingredientes del inventario, cantidades positivas y unidades asociadas a cada materia. Registrar una receta no descuenta existencias. Se impide borrar una materia prima utilizada en una receta.

La propuesta reúne el código de las ramas de PR #8 y PR #11, conserva sus evidencias anteriores y añade las correcciones y recetas. No acredita aprobación de esas PR ni ejecución independiente de estos cambios nuevos por otro integrante.

## Resultado medido

Salida original: [resultado-sprint1-verificado-2026-10-12.json](recuperacion/resultado-sprint1-verificado-2026-10-12.json). Incluye fechas, versiones, huellas de los archivos probados y registro del servidor.

| Grupo | Resultado |
|---|---|
| Preparador: acceso, seguridad, recetas y migraciones | 40 comprobaciones correctas |
| Integración PHP de HU02 | 42 comprobaciones correctas |
| HTTP de HU02 | 15 comprobaciones correctas |
| Navegador de inventario y movimientos | 18 comprobaciones correctas |
| Navegador de recetas | 6 comprobaciones correctas |

Los 40 controles del preparador incluyen cuatro comprobaciones del resultado de las suites: no deben sumarse como si todos los grupos fueran casos independientes. No representan una certificación completa de seguridad.

Entorno: PHP 8.3.6, MariaDB 10.11.14, Python 3.12.14, Chromium Headless Shell 131.0.6778.204 y Playwright 1.62.1. Bootstrap 5.3.8 se sirvió como recurso local durante las pruebas; no se certifica la disponibilidad del CDN. Sintaxis PHP, PHP CS Fixer 3.95.27 con PSR-12, Prettier y comprobación de diferencias: correctos.

La ejecución usa una instancia local nueva, una base con nombre aleatorio y una copia temporal de la aplicación. Antes de cada envío comprueba la base y el archivo de conexión del servidor. La base temporal se eliminó. No se utilizaron datos del negocio.

Se aplicaron las migraciones 002, 003 y 004 sobre el esquema anterior de HU01. La suite de HU02 comprueba conservación de datos, reversión y concurrencia. La migración 004 cambia la relación de ingredientes para impedir borrados por cascada.

Durante la preparación se corrigieron la interferencia entre las variables de conexión y de inicio de sesión, la disponibilidad de la conexión compartida y la lectura del token correcto por el cliente HTTP. La salida enlazada corresponde a la versión corregida, con sus huellas verificables.

## Reproducción

Requisitos: PHP con PDO MySQL y mbstring, Python, MySQL/MariaDB local, Node y Playwright instalado. El usuario de prueba debe poder crear y eliminar bases temporales. Configurar solo en el entorno local `RECUPERACION_SERVIDOR_DSN` sin `dbname`, `RECUPERACION_PHP` si PHP no está en PATH y, cuando corresponda, `RECUPERACION_USUARIO` y `RECUPERACION_CLAVE`. No publicar credenciales.

```powershell
$env:RECUPERACION_SERVIDOR_DSN = "mysql:host=127.0.0.1;port=3306;charset=utf8mb4"
npm ci
npx playwright install chromium
$env:RECUPERACION_NAVEGADOR = "1"
python pruebas/recuperacion-sprint1.py --salida docs/pruebas/recuperacion/resultado-nuevo.json
```

Para otro navegador instalado, indicar su ruta real en `HU02_CHROME`. `HU02_BOOTSTRAP_PRUEBA` permite reproducir el laboratorio con el archivo CSS real de Bootstrap 5.3.8 disponible localmente. La salida debe ser nueva; no sobrescribir evidencias anteriores. Las cuentas y contraseñas de prueba se generan durante la ejecución y no se conservan en la salida.

## Estado del cierre

Sprint 1 ya está cerrado administrativamente en Jira desde el 05/10; las historias se arrastraron al Sprint 2, activo desde el 05/10 hasta el 18/10. Este trabajo recupera su implementación y conserva las fechas originales.

Falta la revisión y reproducción independiente de los cambios nuevos, aprobar la propuesta y comprobar la versión integrada. Hasta entonces no se declara completa la DoD ni se cierran historias o defectos. NC-01 y los resultados originales del Taller 5 se conservan.
