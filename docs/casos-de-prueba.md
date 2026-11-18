# Casos de prueba manuales

Procedimientos reproducibles para el punto 1 de la Definition of Done (`ESTANDARES.md`, sección 8). Cumplen la regla especial para cálculos y reportes: cada caso trae los valores de entrada y el resultado numérico esperado.

## Preparación

Todos los casos parten de una base recién cargada y de una sesión iniciada:

```bash
mysql -u root < database/schema.sql
mysql -u root < database/datos-prueba.sql
INVENTARIO_CLAVE_INICIAL='una-clave-de-prueba' php herramientas/crear-usuario.php prueba@ejemplo.com "Cuenta de prueba"
```

Abra el sistema e inicie sesión con esa cuenta. Si recarga los datos de prueba, la cuenta se conserva (los datos de prueba no tocan la tabla de usuarios).

Los datos de prueba usan fechas relativas al día de la importación, así que «hoy», «mañana» y «ayer» se refieren a ese día. Los casos son independientes entre sí; si ejecuta varios seguidos, los que cambian existencias (CP-05, CP-06, CP-09) pueden alterar los resultados de los siguientes. Para repetir, vuelva a importar `datos-prueba.sql`.

Existencias iniciales relevantes:

| Materia prima | Existencia | Mínimo | Pedido pendiente |
|---|---:|---:|---|
| Harina de trigo | 25.000 g | 10.000 g | — |
| Levadura seca | 400 g | 250 g | 500 g, llega en 3 días |
| Mantequilla | 2.500 g | 3.000 g | 5.000 g, llega en 2 días |
| Queso costeño | 1.200 g | 2.000 g | 3.000 g, atrasado desde ayer |
| Bocadillo de guayaba | 0 g | 1.000 g | 2.000 g, llega mañana |

## Acceso

### CP-00 · Inicio de sesión y páginas protegidas

- **Pasos:** sin sesión, abra `modules/inventario/index.php`. Inicie sesión con una contraseña equivocada y luego con la correcta. Después use «Cerrar sesión» en el menú.
- **Esperado:** sin sesión, la página lleva a `iniciar-sesion.php`. Con la contraseña equivocada: «No se pudo iniciar sesión con esos datos.» Con la correcta se abre el panel de inicio y el menú muestra el nombre de la cuenta. Al cerrar sesión vuelve al inicio de sesión, y las páginas vuelven a pedirlo. Un envío de formulario sin sesión responde 403.

## Recetas y capacidad

### CP-01 · Producción máxima e ingrediente limitante

- **Entrada:** receta Pan francés (rinde 40 por tanda): harina 5.000 g, levadura 100 g, sal 90 g, azúcar 150 g y aceite 100 ml por tanda.
- **Pasos:** Recetas → Pan francés → Ver detalle.
- **Esperado:** 160 unidades posibles ahora; limita **Levadura seca**. En la tabla, «Alcanza para»: harina 200, levadura 160, sal 888, azúcar 2.133, aceite 1.200. Con pedidos: 200 unidades.
- **Cálculo:** levadura 400 × 40 ÷ 100 = 160; es el menor valor.

### CP-02 · Receta que no alcanza y proyección con pedidos

- **Pasos:** Recetas → filtro «No alcanzan».
- **Esperado:** aparece solo **Roscón de bocadillo** con 0 unidades, limita Bocadillo de guayaba (hay 0 g), y «Con los pedidos por llegar: 40 unidades».
- **Cálculo:** con el pedido, bocadillo 2.000 × 12 ÷ 600 = 40; mantequilla con su pedido 7.500 × 12 ÷ 250 = 360.

### CP-03 · Calculadora de materiales

- **Pasos:** Recetas → Roscón de bocadillo → en «¿Qué necesito para producir...?» escriba 9.
- **Esperado:** bocadillo necesita 450 g y falta 450 g; mantequilla 187,5 g; huevos 3 unidades; mensaje «Falta 1 ingrediente con la existencia actual».

## Inventario

### CP-04 · Salida mayor a la existencia

- **Pasos:** Inventario → Levadura seca → Salida → cantidad 500 → Registrar movimiento.
- **Esperado:** antes de enviar, el formulario avisa «No alcanza: solo hay 400 g». Al enviar, el error «No hay suficiente levadura seca: hay 400 g y se intentan sacar 500 g.» La existencia sigue en 400 g y no se crea ningún movimiento.

### CP-05 · Entrada que saca una materia prima de la alerta

- **Pasos:** Inventario → Bocadillo de guayaba → Entrada → cantidad 1500, motivo «Compra».
- **Esperado:** mensaje «Entrada de 1.500 g de bocadillo de guayaba registrada. Ahora hay 1.500 g.» El estado pasa de «Sin existencias» a «Disponible» (1.500 ≥ 1.000), y el filtro «Por reabastecer» baja de 3 a 2.

### CP-06 · Recibir un pedido con una cantidad distinta

- **Pasos:** Inventario → Pedidos por llegar → pedido #2 (queso costeño, 3.000 g, atrasado) → Recibir → cantidad 2800 → Confirmar recepción.
- **Esperado:** «Pedido #2 recibido. Queso costeño ahora tiene 4.000 g.» (1.200 + 2.800). En Movimientos aparece la entrada con origen «Pedido #2» y la observación «(se pidieron 3.000, llegaron 2.800)». El pedido pasa a la pestaña Recibidos.

### CP-07 · Materia prima con recetas no se elimina

- **Pasos:** Inventario → Sal → menú ⋮ → Eliminar → Confirmar.
- **Esperado:** «No se puede eliminar "Sal" porque se usa en 3 recetas. Quítela primero de esas recetas.»

## Planificación y producción

### CP-08 · Estado de materiales de los planes

- **Pasos:** Producción (rango «Próximos 7 días»).
- **Esperado:**

| Día | Plan | Estado | Detalle |
|---|---|---|---|
| Hoy | Pan francés, 160 u | Materiales completos | Usa los 400 g de levadura |
| Hoy | Almojábana, 50 u | Depende de pedidos | Sin el pedido faltarían 800 g de queso costeño |
| Mañana | Roscón de bocadillo, 24 u | Faltan materiales | Faltan 80 g de levadura seca |
| Mañana | Mantecada, 40 u | Faltan materiales | Faltan 1.000 g de harina de trigo |
| En 2 días | Pandebono, 60 u | Depende de pedidos | Sin el pedido faltarían 2.000 g de queso costeño |

- **Cálculo (Roscón):** necesita 24 × 40 ÷ 12 = 80 g de levadura; hay 400 g pero el plan de pan francés de hoy compromete 400 g, y el pedido de levadura llega después. Faltante: 80 g.
- **Cálculo (Mantecada):** necesita 2.000 g de harina; hay 25.000 g − 20.000 (pan francés) − 4.000 (roscón) = 1.000 g. Faltante: 1.000 g.

### CP-09 · Registrar producción y descontar insumos

- **Pasos:** Producción → Pan francés (hoy) → Registrar → unidades 160 → Registrar y descontar insumos.
- **Esperado:** «Producción registrada: 160 unidades de Pan francés…». Existencias después: harina 5.000 g (−20.000), levadura 0 g (−400), sal 1.640 g (−360), azúcar 7.400 g (−600), aceite 2.600 ml (−400). El detalle muestra «Insumos descontados del inventario» con esas cinco salidas, y en Movimientos aparecen con origen «Producción #4».

### CP-10 · Producción que no alcanza

- **Pasos:** Producción → Almojábana (hoy) → Registrar con 50 unidades.
- **Esperado:** error «No alcanzan los materiales para 50 unidades. Faltan 800 g de queso costeño. Con lo que hay se pueden producir máximo 30 unidades.» No se descuenta nada y el plan sigue Planeado.
- **Cálculo:** 50 × 1.000 ÷ 25 = 2.000 g de queso; hay 1.200 g; máximo 1.200 × 25 ÷ 1.000 = 30.

### CP-11 · No se planifica en fechas pasadas

- **Pasos:** Producción → Planificar producción → Pan francés, fecha de ayer (escrita a mano), 40 unidades → Guardar plan.
- **Esperado:** «La fecha no puede ser anterior a …» con la fecha de hoy.

### CP-12 · Lista de materiales del día

- **Pasos:** Producción → «Materiales del día» de mañana.
- **Esperado:** total de harina de trigo 6.000 g (4.000 roscón + 2.000 mantecada) con faltante 1.000 g; levadura 80 g con faltante 80 g; bocadillo 1.200 g marcado «llega pedido».

## Ventas y reportes

### CP-13 · Registrar una venta en la caja

- **Pasos:** Caja → tocar Pan francés 3 veces y el + de Pandebono 1 vez → Registrar venta.
- **Esperado:** el resumen muestra «Pan francés × 3», «Pandebono × 1» y un total de 4 unidades. Al registrar: «Venta #972 registrada: 4 unidades.» La venta aparece de primera en «Ventas de hoy». Sin productos, el botón Registrar venta está deshabilitado.

### CP-14 · Reporte diario de un día conocido

- **Pasos:** Reportes → Diario → día anterior (flecha ‹).
- **Esperado:** 83 ventas y 505 unidades; hora pico 7 a. m. con 13 ventas y 60 unidades; producto más vendido Pan francés con 285 unidades.

### CP-15 · Anular una venta

- **Pasos:** Caja → en «Ventas de hoy», Anular la primera → Anular venta en la confirmación.
- **Esperado:** «Venta #… anulada.» El total de ventas de hoy baja en 1 y las unidades bajan en las de esa venta.

### CP-16 · Archivar una receta con historial

- **Pasos:** Recetas → Mantecada → Editar → Eliminar → confirmar. Luego Archivar receta.
- **Esperado:** al eliminar: «Esta receta tiene producciones o ventas registradas…». Al archivar, Mantecada ya no aparece en la Caja ni en el selector de Planificar producción, y en Recetas aparece el filtro «Archivadas (1)».

## Pruebas automáticas

Además de estos casos, `vendor/bin/phpunit` ejecuta 49 pruebas automáticas en las suites de Unidad e Integración, con resultados numéricos conocidos para los cálculos de producción, los faltantes y los reportes (por ejemplo, la hora pico de un conjunto de ventas fijo en `pruebas/GestorVentasTest.php`).
