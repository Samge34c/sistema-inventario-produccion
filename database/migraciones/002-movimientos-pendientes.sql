-- HU02: aplicar UNA VEZ sobre una instalación con el schema anterior.
-- No usar después del schema.sql nuevo, que ya contiene esta estructura.
-- Conservar una copia de seguridad antes de aplicar DDL; MySQL hace commits implícitos.
-- No se borran movimientos ni existencias.
USE sistema_inventario;

ALTER TABLE materias_primas ENGINE=InnoDB;
ALTER TABLE movimientos_inventario ENGINE=InnoDB;

-- El schema anterior genera el nombre movimientos_inventario_ibfk_1.
-- Si el esquema local fue personalizado, revisar SHOW CREATE TABLE antes de aplicar.
ALTER TABLE movimientos_inventario
    DROP FOREIGN KEY movimientos_inventario_ibfk_1,
    ADD CONSTRAINT fk_movimiento_materia FOREIGN KEY (materia_prima_id)
        REFERENCES materias_primas(id) ON DELETE RESTRICT ON UPDATE CASCADE;

CREATE TABLE materiales_pendientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materia_prima_id INT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacion VARCHAR(255),
    CONSTRAINT fk_pendiente_materia FOREIGN KEY (materia_prima_id) REFERENCES materias_primas(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ck_pendiente_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
