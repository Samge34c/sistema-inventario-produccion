CREATE DATABASE IF NOT EXISTS sistema_inventario;
USE sistema_inventario;

CREATE TABLE materias_primas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    unidad_medida VARCHAR(20) NOT NULL,
    cantidad_disponible DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 0
);

CREATE TABLE recetas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    cantidad_producir INT NOT NULL DEFAULT 1
);

CREATE TABLE receta_ingredientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receta_id INT NOT NULL,
    materia_prima_id INT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (receta_id) REFERENCES recetas(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (materia_prima_id) REFERENCES materias_primas(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);
CREATE TABLE movimientos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materia_prima_id INT NOT NULL,
    tipo ENUM('ENTRADA', 'SALIDA') NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacion VARCHAR(255),

    FOREIGN KEY (materia_prima_id) REFERENCES materias_primas(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);
