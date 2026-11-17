-- Migración 005: producción, ventas y llegada de materiales pendientes.
--
-- Aplicar UNA VEZ sobre una instalación que ya tenga las migraciones 002,
-- 003 y 004 (o que se haya creado con el schema.sql anterior a esta versión).
-- No usar después del schema.sql nuevo, que ya contiene esta estructura.
-- Conserve una copia de seguridad antes de aplicarla: MySQL confirma cada
-- cambio de estructura por separado. No se borran datos.
--
-- Qué cambia:
--   1. Nombres únicos en materias primas y recetas, y un ingrediente no se
--      repite dentro de la misma receta.
--   2. Las recetas se pueden archivar (columna activa).
--   3. Los movimientos guardan su origen: manual, material recibido o producción.
--   4. Los materiales pendientes tienen proveedor, fecha estimada de llegada y
--      estado (pendiente, recibido o cancelado). Los que ya existían quedan
--      pendientes, con la fecha de registro como fecha estimada.
--   5. Tablas nuevas: producciones, ventas y venta_detalles.
--
-- Si el paso 1 falla por nombres o ingredientes repetidos, corrija esos
-- registros y vuelva a ejecutar el script desde ese paso. Para encontrarlos:
--   SELECT nombre, COUNT(*) FROM materias_primas GROUP BY nombre HAVING COUNT(*) > 1;
--   SELECT nombre, COUNT(*) FROM recetas GROUP BY nombre HAVING COUNT(*) > 1;
--   SELECT receta_id, materia_prima_id, COUNT(*) FROM receta_ingredientes
--    GROUP BY receta_id, materia_prima_id HAVING COUNT(*) > 1;

-- 1 y 2. Nombres únicos y recetas archivables
ALTER TABLE materias_primas
    ADD UNIQUE KEY uq_materias_primas_nombre (nombre);

ALTER TABLE recetas
    ADD COLUMN activa TINYINT(1) NOT NULL DEFAULT 1,
    ADD UNIQUE KEY uq_recetas_nombre (nombre);

ALTER TABLE receta_ingredientes
    ADD UNIQUE KEY uq_receta_ingrediente (receta_id, materia_prima_id);

-- MySQL y MariaDB eliminan solos el índice que habían creado para la llave
-- foránea de receta_id, porque el índice nuevo empieza por esa columna.

-- 3. Origen de los movimientos
ALTER TABLE movimientos_inventario
    ADD COLUMN origen ENUM('MANUAL', 'PEDIDO', 'PRODUCCION') NOT NULL DEFAULT 'MANUAL',
    ADD COLUMN referencia_id INT NULL,
    ADD INDEX idx_movimientos_fecha (fecha);

-- 4. Llegada de materiales pendientes
ALTER TABLE materiales_pendientes
    ADD COLUMN proveedor VARCHAR(100) NULL,
    ADD COLUMN fecha_estimada_llegada DATE NULL,
    ADD COLUMN estado ENUM('PENDIENTE', 'RECIBIDO', 'CANCELADO') NOT NULL DEFAULT 'PENDIENTE',
    ADD COLUMN fecha_recepcion DATETIME NULL;

UPDATE materiales_pendientes
   SET fecha_estimada_llegada = DATE(fecha_registro)
 WHERE fecha_estimada_llegada IS NULL;

ALTER TABLE materiales_pendientes
    MODIFY fecha_estimada_llegada DATE NOT NULL,
    ADD INDEX idx_pendientes_estado_fecha (estado, fecha_estimada_llegada);

-- 5. Tablas nuevas
CREATE TABLE producciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receta_id INT NOT NULL,
    fecha_planeada DATE NOT NULL,
    cantidad_planeada INT NOT NULL,
    cantidad_producida INT NULL,
    estado ENUM('PLANEADA', 'REALIZADA', 'CANCELADA') NOT NULL DEFAULT 'PLANEADA',
    fecha_realizacion DATETIME NULL,
    observacion VARCHAR(255),
    creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_producciones_fecha_estado (fecha_planeada, estado),

    CONSTRAINT fk_produccion_receta FOREIGN KEY (receta_id) REFERENCES recetas(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacion VARCHAR(255),

    INDEX idx_ventas_fecha_hora (fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE venta_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    receta_id INT NOT NULL,
    cantidad INT NOT NULL,

    UNIQUE KEY uq_venta_producto (venta_id, receta_id),

    CONSTRAINT fk_detalle_venta FOREIGN KEY (venta_id) REFERENCES ventas(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_detalle_receta FOREIGN KEY (receta_id) REFERENCES recetas(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
