# Panadería — Sistema de inventario, recetas y planificación de producción

Aplicación web en PHP y MySQL para gestionar materias primas, recetas, planificación de producción y ventas.

## Requisitos

- PHP 8 o superior con PDO MySQL.
- MySQL 8 o MariaDB compatible.
- Git.
- Node.js y npm para las herramientas de formato frontend.

## Instalación local

1. Clonar el repositorio.
2. Importar `database/schema.sql` en MySQL.
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
