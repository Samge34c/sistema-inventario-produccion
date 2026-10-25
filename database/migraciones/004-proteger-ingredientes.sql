-- Conserva las recetas cuando una materia prima se utiliza como ingrediente.
SET @clave_ingrediente = (
    SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'receta_ingredientes'
        AND REFERENCED_TABLE_NAME = 'materias_primas' LIMIT 1
);
SET @sql_ingrediente = IF(@clave_ingrediente IS NULL, 'SELECT 1',
    CONCAT('ALTER TABLE receta_ingredientes DROP FOREIGN KEY `', REPLACE(@clave_ingrediente, '`', '``'), '`'));
PREPARE cambio_ingrediente FROM @sql_ingrediente;
EXECUTE cambio_ingrediente;
DEALLOCATE PREPARE cambio_ingrediente;
ALTER TABLE receta_ingredientes ADD CONSTRAINT fk_ingrediente_materia
    FOREIGN KEY (materia_prima_id) REFERENCES materias_primas(id)
    ON DELETE RESTRICT ON UPDATE CASCADE;
