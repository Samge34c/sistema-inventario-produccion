# Panadería

Sistema web de inventario, recetas y planificación de producción para una panadería. Centraliza las existencias de materias primas y las recetas, calcula cuánto se puede producir con lo disponible, permite planificar la producción por fecha y muestra reportes de ventas.

## Qué hace

| Módulo | Funciones |
|---|---|
| **Formularios** | Todos los formularios exigen un código de seguridad, que rechaza los envíos falsificados (SCRUM-22). El sistema no tiene cuentas ni inicio de sesión: quien lo abre puede usarlo (CC-08, 18 de noviembre de 2026). |
| **Inicio** | Panel con lo que necesita atención hoy: planes con materiales faltantes, pedidos atrasados, materias primas bajo el mínimo, producción de hoy y mañana, y recetas que se pueden hornear ya. |
| **Inventario** | Materias primas con existencia, mínimo y estado. Entradas y salidas con historial y origen (manual, pedido o producción). Pedidos pendientes de llegada que, al recibirse, suman la cantidad real. |
| **Recetas** | Ingredientes por tanda y rendimiento. Producción máxima, ingrediente limitante, recetas posibles y proyección contando los pedidos por llegar. Calculadora de materiales para cualquier cantidad. |
| **Producción** | Planes por fecha y cantidad, materiales requeridos y faltantes, lista de alistamiento del día (imprimible) y registro de lo producido, que descuenta los insumos del inventario. |
| **Caja** | Registro rápido de ventas tocando los productos, pensado también para celular. Historial por día y anulación de ventas. |
| **Reportes** | Reporte diario (frente al mismo día de la semana anterior), semanal (frente a la semana anterior) y de hora pico con mapa de calor día × hora. |

## Requisitos

- PHP 8.2 o superior con las extensiones `pdo_mysql` y `mbstring` (XAMPP 8.2 o más reciente las trae activas). No usa Laravel ni otro framework de servidor.
- MySQL 5.7+ o MariaDB 10.4+.
- Un navegador actual. No necesita internet: Bootstrap y los íconos están incluidos en `assets/lib`.

## Instalación

### 1. Base de datos

Para una base **nueva**, importe en phpMyAdmin (Importar) o desde la consola, en este orden:

1. `database/schema.sql`: crea la base `sistema_inventario` con todas las tablas.
2. `database/datos-prueba.sql` (opcional): carga una panadería de ejemplo con 12 materias primas, 6 recetas, materiales por llegar, planes de producción y dos semanas de ventas. Las fechas se ajustan solas al día de la importación.

```bash
mysql -u root < database/schema.sql
mysql -u root < database/datos-prueba.sql
```

Para una instalación **existente**, no importe el esquema encima: haga una copia de seguridad y aplique las migraciones que le falten, en orden.

| Si su base está en… | Aplique |
|---|---|
| El esquema anterior a HU02 | 002, 003, 004, 005 y 006 |
| HU02 ya instalada | 003, 004, 005 y 006 |
| La versión de develop con usuarios (HU01 a HU03) | 005 y 006 |
| La versión con inicio de sesión de la PR #15 | solo 006 |

```bash
mysql -u root sistema_inventario < database/migraciones/005-produccion-y-ventas.sql
mysql -u root sistema_inventario < database/migraciones/006-quita-usuarios.sql
```

La 005 no borra datos: los materiales pendientes que ya existían quedan como pedidos pendientes, con la fecha de registro como llegada estimada. La 006 borra la tabla de cuentas, que el sistema ya no usa.

### 2. Conexión

Los valores por defecto sirven para XAMPP recién instalado (usuario `root` sin contraseña, base `sistema_inventario`). Para cambiarlos hay dos opciones, y ninguna deja contraseñas en GitHub:

- **Variables de entorno**, como en HU02: `INVENTARIO_DSN` (por ejemplo `mysql:host=localhost;dbname=sistema_inventario;charset=utf8mb4`), `INVENTARIO_USUARIO` e `INVENTARIO_CLAVE`.
- **Archivo local**: copie `config/configuracion.local.ejemplo.php` como `config/configuracion.local.php` y deje solo las claves que necesite cambiar. Ese archivo está en `.gitignore`.

En `config/configuracion.php` también se ajustan la zona horaria (por defecto `America/Bogota`), el nombre del negocio y si se muestra el detalle técnico de los errores (`mostrar_detalle_errores`; póngalo en `false` si publica el sistema en un servidor compartido).

### 3. Abrir el sistema

- **Con XAMPP:** copie el proyecto en `htdocs` (por ejemplo `C:\xampp\htdocs\sistema-inventario-produccion`), encienda Apache y MySQL y abra `http://localhost/sistema-inventario-produccion/`.
- **Con el servidor de PHP:** desde la raíz del proyecto, `php -S 127.0.0.1:8000` y abra `http://127.0.0.1:8000/`.

El sistema abre directo en el panel de inicio, sin pedir correo ni contraseña.

## Cómo calcula

- **Unidades.** Cada materia prima tiene una unidad (g, kg, ml, l o unidades). La existencia, el mínimo y las cantidades de las recetas se escriben en esa misma unidad, así que no hay conversiones.
- **Receta.** Las cantidades son las de *una tanda*, y el rendimiento dice cuántas unidades de producto salen de ella. Para otra cantidad se escala en proporción: 60 unidades de una receta que rinde 30 piden el doble de cada ingrediente.
- **Producción máxima.** Para cada ingrediente se calcula cuántas unidades completas alcanzan (`existencia × rendimiento ÷ cantidad por tanda`, redondeado hacia abajo). La producción máxima es la menor de todas, y ese ingrediente es el **limitante**. La **proyección** hace el mismo cálculo sumando los pedidos pendientes.
- **Faltantes de un plan.** Los planes pendientes se revisan en orden de fecha (y de creación dentro del mismo día). Cada uno cuenta con: existencia actual − lo que necesitan los planes anteriores + pedidos que llegan hasta su fecha. El resultado es **Materiales completos**, **Depende de pedidos** (alcanza solo si llegan) o **Faltan materiales**.
- **Registro de producción.** Descuenta los insumos de las unidades realmente producidas. Si algún insumo no alcanza, no descuenta nada y dice qué falta y cuánto se puede producir.
- **Ventas y hora pico.** Una *venta* es una atención en caja y puede llevar varios productos. La **hora pico** es la franja de una hora con más ventas; si hay empate, gana la de más unidades.
- **Integridad.** La existencia solo cambia con movimientos, así que el historial siempre explica la cantidad actual. Una materia prima con historial no se puede borrar, y una receta con ventas o producciones se archiva en lugar de borrarse.

## Estructura

```text
index.php                 Panel de inicio
modules/inventario/       Materias primas, movimientos y pedidos
modules/recetas/          Recetas y capacidad de producción
modules/produccion/       Planificación, materiales del día y registro
modules/ventas/           Caja, historial y reportes
clases/                   Lógica del negocio (una clase por archivo)
  CalculadoraProduccion   Cálculos puros: máxima, limitante, faltantes
  GestorInventario        Materias primas y movimientos
  GestorPedidos           Pedidos pendientes de llegada
  GestorRecetas           Recetas, ingredientes y capacidad
  GestorProduccion        Planes, revisión de materiales y registro
  GestorVentas            Ventas y reportes
  Validador, ErrorValidacion, BaseDatos   Apoyo
includes/                 Arranque, funciones de apoyo y diseño común
config/                   Configuración, conexión y código de seguridad de los formularios
herramientas/             Copia de librerías
assets/                   CSS, JavaScript propio y librerías locales
database/                 Esquema, migraciones y datos de prueba
pruebas/                  Pruebas automáticas (PHPUnit)
pruebas/version-anterior/ Scripts que verificaron HU01–HU03 y SCRUM-22 antes de esta versión
docs/                     Casos de prueba manuales y verificación de calidad
```

## Herramientas de calidad

Las herramientas de desarrollo no son necesarias para usar el sistema, solo para verificarlo (puntos 2 y 4 de la Definition of Done de `ESTANDARES.md`).

```bash
composer install        # PHPUnit, PHPStan y PHP CS Fixer
npm install             # Prettier

vendor/bin/phpunit                                   # pruebas automáticas
composer cobertura                                   # pruebas con cobertura (necesita PCOV o Xdebug)
vendor/bin/php-cs-fixer fix --dry-run --diff         # formato PHP (PSR-12)
npx prettier "**/*.{js,mjs,html,css}" --check        # formato JS y CSS
vendor/bin/phpstan analyse --memory-limit=512M       # análisis estático (nivel 6)
```

La cobertura dice qué líneas de `clases/` e `includes/` ejecutan las pruebas y cuáles no. Es el mismo trabajo que hace JaCoCo en Java. En GitHub Actions se mide en cada PR, con PCOV, y el resultado queda en el registro del paso "Pruebas automáticas y cobertura". Para medirla en su equipo hace falta PCOV o Xdebug (con `xdebug.mode=coverage`); XAMPP no los trae activos.

Las pruebas de integración crean y usan una base aparte, `sistema_inventario_pruebas`, con los mismos datos de conexión. Nunca tocan la base real. Si MySQL no está encendido, esas pruebas se omiten y las de cálculo se ejecutan igual.

Si actualiza Bootstrap o Bootstrap Icons con npm, ejecute `npm run copiar-librerias` para copiar las nuevas versiones a `assets/lib`.

Los casos de prueba manuales, con datos de entrada y resultados numéricos conocidos, están en [`docs/casos-de-prueba.md`](docs/casos-de-prueba.md).

## Seguimiento

- [Estándares del equipo](ESTANDARES.md).
- [Tablero Jira](https://sistema-inventario-produccion.atlassian.net/jira/software/projects/SCRUM/boards/1).
- Evidencias de sprints anteriores: [recuperación de HU01–HU03](docs/pruebas/recuperacion-sprint1-2026-10-12.md), [HU02](docs/pruebas/hu02-movimientos.md) y [SCRUM-22](docs/pruebas/scrum22-proteccion-alta.md). Conservan sus versiones y fechas; los scripts que usaron están en `pruebas/version-anterior/`.
