# Panadería — Sistema de inventario, recetas y planificación de producción

Aplicación web en PHP y MySQL para gestionar materias primas, recetas, planificación de producción y ventas.

## Requisitos

- PHP 8 o superior con PDO MySQL y mbstring.
- MySQL 8 o MariaDB compatible.
- Git.
- Node.js y npm para las herramientas de formato frontend.

## Instalación local

1. Clonar el repositorio.
2. Para una base nueva, importar `database/schema.sql` en MySQL. Si ya existe una base del esquema anterior, respaldarla y aplicar una sola vez `database/migraciones/002-movimientos-pendientes.sql`; no reimportar el esquema sobre datos existentes.
3. Revisar la conexión local en `config/database.php`.
4. Desde la raíz ejecutar:

```bash
php -S localhost:8000
```

5. Abrir `http://localhost:8000`.

## Módulos

- `modules/inventario/`: materias primas y movimientos de inventario.
- `modules/recetas/`: recetas e ingredientes.
- `modules/produccion/`: planificación y cálculos de producción.
- `modules/ventas/`: ventas y reportes.

## HU01 — Gestionar materias primas

Permite registrar, consultar, editar y eliminar materias primas con nombre, unidad de medida, existencia disponible y stock mínimo.

El caso de prueba reproducible está en `docs/pruebas/hu01-materias-primas.md`.

## HU02 — Movimientos y materiales pendientes

Desde Materias primas, abrir **Movimientos**. Registrar ENTRADA o SALIDA actualiza la existencia en la unidad de la materia prima; PENDIENTE se guarda por separado y no aumenta la disponibilidad actual. Se rechazan salidas mayores al saldo y cantidades con más de dos decimales. Una materia prima con historial no puede borrarse ni cambiar su unidad o saldo directamente; el saldo se ajusta mediante un movimiento.

Caso numérico, instalación, pruebas automatizadas y límites de verificación: [HU02](docs/pruebas/hu02-movimientos.md). La implementación sigue pendiente de revisión e integración.

## Formato frontend

Instalar dependencias:

```bash
npm install
```

Comprobar formato:

```bash
npx prettier "**/*.{js,html,css}" --check
```

## Flujo Git

- `main`: versión integrada y estable.
- `develop`: integración del sprint.
- `feat/<hu>-<nombre-corto>`: funcionalidades.
- `fix/<nombre-corto>`: correcciones.
- `docs/<nombre-corto>`: documentación.

Las ramas de trabajo nacen de `develop` y se integran mediante Pull Request revisada por el integrante distinto del autor.
