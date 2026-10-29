# Caso de prueba HU01 — Gestionar materias primas

## Objetivo

Comprobar que una materia prima puede registrarse y luego consultarse con su existencia y unidad de medida.

## Precondiciones

1. Haber ejecutado `database/schema.sql` en MySQL.
2. Tener configurada la conexión local en `config/database.php`.
3. Ejecutar el proyecto desde la raíz con un servidor PHP:

```bash
php -S localhost:8000
```

## Caso principal

1. Abrir `http://localhost:8000/modules/inventario/`.
2. Registrar:
   - Nombre: `Harina`
   - Unidad: `g`
   - Existencia: `10000`
   - Stock mínimo: `1000`
3. Presionar **Guardar materia prima**.

## Resultado esperado

La tabla de existencias debe mostrar `Harina`, unidad `g` y existencia `10000.00`.

Consulta de comprobación:

```sql
SELECT nombre, unidad_medida, cantidad_disponible
FROM materias_primas
WHERE nombre = 'Harina';
```

Resultado esperado:

```text
Harina | g | 10000.00
```

## Validaciones adicionales

- No permite nombre vacío.
- No permite cantidades negativas.
- No permite unidades distintas de las opciones definidas.
- Permite editar una materia prima.
- Permite eliminar una materia prima.
