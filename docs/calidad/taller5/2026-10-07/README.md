# Evidencias de control de calidad — Taller 5

Código medido: `7a01251325365c8d2ebbead5b89d5732671f1463` de HU02. Fecha: 07/10/2026. Ver [reporte estático](REPORTE_ANALISIS_ESTATICO.md) y [métricas](resumen.json).

Esta rama documental parte de develop; no integra HU02 ni cambia el producto. Reproduzca en checkout aislado del commit medido copiando las pruebas de esta rama. **Utilice una base sintética local, nunca la del negocio.**

```bash
git clone https://github.com/Samge34c/sistema-inventario-produccion.git taller5-aislado
cd taller5-aislado
git checkout 7a01251325365c8d2ebbead5b89d5732671f1463
git checkout origin/docs/taller5-calidad -- pruebas/taller5 herramientas-calidad
composer install --working-dir=herramientas-calidad
mkdir -p pruebas/taller5/resultados
git show 28e8a15bbd10612ed40b38021317b4250f807610:database/schema.sql > schema-anterior-prueba.sql
```

Requisitos: PHP >=8.3 con pdo_mysql, mbstring, dom, xml, xmlwriter, tokenizer; PCOV para cobertura; servidor de prueba MariaDB/MySQL; usuario autorizado a crear/eliminar bases aleatorias. Composer lock fija las versiones.

```bash
export HU02_PRUEBA_DSN='mysql:host=127.0.0.1;port=3306;charset=utf8mb4'
export HU02_SCHEMA_ANTERIOR="$PWD/schema-anterior-prueba.sql"
# HU02_PRUEBA_USUARIO y HU02_PRUEBA_CLAVE: configurar localmente, no versionar.
php -d pcov.directory="$PWD/modules/inventario" herramientas-calidad/vendor/bin/phpunit -c pruebas/taller5/phpunit.xml --coverage-clover pruebas/taller5/resultados/coverage.xml --coverage-text
php herramientas-calidad/vendor/bin/phpstan analyse --level=5 --no-progress --error-format=prettyJson config modules/inventario index.php > pruebas/taller5/resultados/phpstan.json
php herramientas-calidad/vendor/bin/pdepend --summary-xml=pruebas/taller5/resultados/pdepend.xml modules/inventario
```

Cobertura: solamente funciones.php y movimientos-funciones.php. PHPStan: 11 archivos. Complejidad: 13 funciones.

HTTP y navegador: scripts del commit medido `pruebas/hu02-http.py` y `pruebas/hu02-web.js`, con despliegue/base sintéticos y HU02_URL. Chromium debe estar instalado. En esta ejecución HU02_BOOTSTRAP_PRUEBA apuntó a Bootstrap 5.3.8 descargado; el producto conserva su CDN.

Sondas: `python3 pruebas/taller5/sondas.py`, con HU02_URL hacia un servidor local sintético. Añade dos materias; no borra ni siembra datos. Para rendimiento use exactamente 1000 materias y cero historial en un despliegue separado. Muestra publicada: un cliente, 5 calentamientos y 100 lecturas por ruta; excluye assets y WAN.

Resultados técnicos del asistente. Ejecución humana cruzada, review y aceptación del negocio pendientes. No se declara ninguna HU Done.
