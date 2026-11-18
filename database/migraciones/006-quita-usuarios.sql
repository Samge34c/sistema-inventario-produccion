-- Migración 006: el sistema ya no tiene inicio de sesión (CC-08, 18 de noviembre de 2026).
-- Aplicar una vez sobre una base con la migración 003. Borra la tabla de cuentas, que ya no se usa.

DROP TABLE IF EXISTS usuarios;
