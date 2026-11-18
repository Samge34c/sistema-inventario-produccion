-- Esquema completo. Para una instalación anterior use las migraciones (ver README).

CREATE DATABASE IF NOT EXISTS sistema_inventario CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_inventario;

-- Inventario

CREATE TABLE materias_primas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    -- g, kg, ml, l o unidad
    unidad_medida VARCHAR(20) NOT NULL,
    cantidad_disponible DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 0,

    UNIQUE KEY uq_materias_primas_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE movimientos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materia_prima_id INT NOT NULL,
    tipo ENUM('ENTRADA', 'SALIDA') NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacion VARCHAR(255),
    -- referencia_id: pedido recibido o producción que generó el movimiento
    origen ENUM('MANUAL', 'PEDIDO', 'PRODUCCION') NOT NULL DEFAULT 'MANUAL',
    referencia_id INT NULL,

    INDEX idx_movimientos_fecha (fecha),

    CONSTRAINT fk_movimiento_materia FOREIGN KEY (materia_prima_id) REFERENCES materias_primas(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pedidos de materia prima por llegar
CREATE TABLE materiales_pendientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materia_prima_id INT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacion VARCHAR(255),
    proveedor VARCHAR(100) NULL,
    fecha_estimada_llegada DATE NOT NULL,
    estado ENUM('PENDIENTE', 'RECIBIDO', 'CANCELADO') NOT NULL DEFAULT 'PENDIENTE',
    fecha_recepcion DATETIME NULL,

    INDEX idx_pendientes_estado_fecha (estado, fecha_estimada_llegada),

    CONSTRAINT fk_pendiente_materia FOREIGN KEY (materia_prima_id) REFERENCES materias_primas(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ck_pendiente_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recetas

CREATE TABLE recetas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    -- unidades que salen de una tanda
    cantidad_producir INT NOT NULL DEFAULT 1,
    activa TINYINT(1) NOT NULL DEFAULT 1,

    UNIQUE KEY uq_recetas_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE receta_ingredientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receta_id INT NOT NULL,
    materia_prima_id INT NOT NULL,
    -- cantidad por tanda
    cantidad DECIMAL(10,2) NOT NULL,

    UNIQUE KEY uq_receta_ingrediente (receta_id, materia_prima_id),

    FOREIGN KEY (receta_id) REFERENCES recetas(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_ingrediente_materia FOREIGN KEY (materia_prima_id) REFERENCES materias_primas(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Planificación y registro de producción

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

-- Ventas

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
