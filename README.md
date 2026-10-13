# Panadería — Sistema de inventario, recetas y planificación de producción

Aplicación web en PHP y MySQL para gestionar materias primas, recetas, planificación de producción y ventas.

## Dónde encontrar cada cosa

**Empieza por [el índice de documentación](docs/README.md).** Reúne el avance, los estándares, las evidencias de calidad y los procedimientos de prueba.

| Necesidad | Enlace |
|---|---|
| Leer el avance consolidado | [Avance del proyecto](docs/gestion/avance-consolidado.md) |
| Consultar los acuerdos del equipo | [ESTANDARES.md](ESTANDARES.md) |
| Revisar casos y mediciones del Taller 5 | [Evidencias publicadas](https://github.com/Samge34c/sistema-inventario-produccion/tree/docs/taller5-calidad/docs/calidad/taller5/2026-10-07) |
| Reproducir movimientos de inventario | [Procedimiento HU02](https://github.com/Samge34c/sistema-inventario-produccion/blob/feat/hu02-movimientos/docs/pruebas/hu02-movimientos.md) |
| Revisar la corrección de SCRUM-22 | [PR #11 y pruebas](https://github.com/Samge34c/sistema-inventario-produccion/pull/11) |
| Consultar el seguimiento | [Tablero Jira](https://sistema-inventario-produccion.atlassian.net/jira/software/projects/SCRUM/boards/1) |

## Estado comprobado al 9 de octubre de 2026

- `develop` incluye HU01, con revisión/calidad pendientes.
- HU02 está implementada en `feat/hu02-movimientos`. Julian publicó [su reporte de reproducción](https://github.com/Samge34c/sistema-inventario-produccion/blob/3d96f4afb1640b66f3a8ef08c4a543e5e432490b/docs/pruebas/evidencias/julian-hu02-2026-10-09.md): 42 comprobaciones de integración y 15 HTTP correctas, con limitaciones de conservación de las salidas. PR #8 sigue pendiente de revisión formal e integración.
- Recetas, producción y ventas siguen pendientes. Las rutas reservadas no representan módulos implementados.
- El Taller 5 conserva la medición del 07/10: 12 casos aprobados, 2 fallidos y 1 pendiente.
- La corrección de SCRUM-22 está propuesta en PR #11; siete comprobaciones HTTP/SQL aprobaron, pero falta revisión e integración.
- Este índice y el consolidado se publican en la rama `docs/avance-consolidado`; no cambian por sí solos el estado de las historias.
- Jira: máximo de una tarjeta En curso y una En revisión, configurado el 09/10; [políticas publicadas en SCRUM-24](https://sistema-inventario-produccion.atlassian.net/browse/SCRUM-24). La invitación docente figura como pendiente de aceptación; acceso efectivo aún no comprobado.

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

- `modules/inventario/`: materias primas en develop; movimientos en PR #8.
- `modules/recetas`, `modules/produccion` y `modules/ventas`: rutas reservadas; implementación pendiente.

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
