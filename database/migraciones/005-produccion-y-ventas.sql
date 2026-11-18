-- Migración 005: producción, ventas y llegada de pedidos.
-- Aplicar una vez sobre una base con las migraciones 002 a 004. No borra datos.
-- Si falla por nombres repetidos en materias primas o recetas, corríjalos y vuelva a ejecutar.

-- 1 y 2. Nombres únicos y recetas archivables
ALTER TABLE materias_primas
    ADD UNIQUE KEY uq_materias_primas_nombre (nombre);

ALTER TABLE recetas
    ADD COLUMN activa TINYINT(1) NOT NULL DEFAULT 1,
    ADD UNIQUE KEY uq_recetas_nombre (nombre);

ALTER TABLE receta_ingredientes
    ADD UNIQUE KEY uq_receta_ingrediente (receta_id, materia_prima_id);


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
