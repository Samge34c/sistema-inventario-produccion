# Pruebas de la versión anterior

Estos scripts verificaron HU01, HU02, HU03 y SCRUM-22 sobre las páginas que había en `develop` hasta el commit `30e837a` (`modules/inventario/guardar.php`, `guardar-movimiento.php`, `editar.php`, `modules/recetas/guardar.php` y otras).

La integración de los módulos completos reemplazó esas páginas, así que estos scripts ya no corren contra la versión actual. Se conservan sin cambios porque respaldan las evidencias publicadas en `docs/pruebas/`, que citan sus rutas originales (`pruebas/hu02-http.py`, etc.).

Para reproducir esas evidencias, use el commit que cita cada documento:

```bash
git checkout 30e837a
```

La verificación de la versión actual está en:

- `vendor/bin/phpunit`: pruebas automáticas de cálculos, inventario, materiales por llegar, recetas, producción y ventas.
- `docs/casos-de-prueba.md`: casos manuales con resultados numéricos conocidos.
