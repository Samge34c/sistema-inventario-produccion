# Sistema de inventario, recetas y planificación de producción

Aplicación web en PHP, MySQL, HTML, Bootstrap y JavaScript.

## Preparar una instalación local

1. Instalar PHP con PDO MySQL y mbstring, y un servidor MySQL/MariaDB.
2. Para una base nueva, importar `database/schema.sql`. Para una instalación anterior a HU02, aplicar en orden las migraciones 002, 003 y 004 sobre la base seleccionada. Si HU02 ya estaba instalada, aplicar solo 003 y 004. Conservar una copia antes de migrar una instalación con datos; no importar el esquema nuevo encima.
3. Configurar la conexión local con `INVENTARIO_DSN`, `INVENTARIO_USUARIO` y `INVENTARIO_CLAVE`. El DSN debe indicar la base seleccionada. No guardar credenciales en GitHub.
4. Crear una cuenta desde la terminal: configurar temporalmente `INVENTARIO_CLAVE_INICIAL` con una contraseña privada de al menos 12 caracteres y ejecutar `php herramientas/crear-usuario.php correo nombre`. Utilizar los datos reales elegidos localmente; eliminar la variable de contraseña después. No hay cuentas públicas ni contraseñas predeterminadas.
5. Iniciar `php -S 127.0.0.1:8000` desde la raíz y abrir `http://127.0.0.1:8000/iniciar-sesion.php`.

## Flujo disponible en la propuesta

- Iniciar y cerrar sesión con una cuenta activa.
- Registrar y consultar materias primas.
- Registrar entradas, salidas y materiales pendientes; consultar el historial.
- Registrar y consultar recetas con cantidades por unidad del producto. Cada ingrediente utiliza la unidad de su materia prima; crear recetas no descuenta inventario.

## Pruebas y seguimiento

- [Recuperación de HU01–HU03 y procedimiento vigente](docs/pruebas/recuperacion-sprint1-2026-10-12.md).
- [Evidencia anterior de HU02](docs/pruebas/hu02-movimientos.md).
- [Estándares del equipo](ESTANDARES.md).
- [Tablero Jira](https://sistema-inventario-produccion.atlassian.net/jira/software/projects/SCRUM/boards/1).

Los informes y pruebas anteriores conservan sus versiones y fechas. La implementación preparada no sustituye revisión independiente, aprobación ni verificación de una integración. No hay Laravel u otro framework de servidor incorporado.
