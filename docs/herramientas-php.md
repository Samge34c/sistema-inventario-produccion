# Formato PHP reproducible

PHP CS Fixer implementa el formateador adoptado en `ESTANDARES.md`.
La versión exacta y sus dependencias se conservan en `composer.lock`.
Es una dependencia de desarrollo; no es necesaria para servir la aplicación.

Requisitos de las herramientas: PHP 8.2 o superior, Composer y extensiones que
indique Composer al instalar. La aplicación usa además PDO MySQL y mbstring.
El mínimo de las herramientas es distinto al requisito previo de la aplicación.

```bash
composer install
composer validar
composer comprobar-formato
```

La configuración aplica exclusivamente `@PSR12`; excluye `vendor` y `node_modules`.
Para aplicar el formato, usar `composer aplicar-formato` y revisar el diff.
No se presenta la comprobación de formato como una prueba funcional o una
medición de atributos del Taller 5.
