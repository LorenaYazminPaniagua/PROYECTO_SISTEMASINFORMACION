DELIMITER $$

CREATE TRIGGER trg_InventarioIngrediente_Bajo
AFTER UPDATE ON Ingrediente
FOR EACH ROW
BEGIN
    -- Ejecutar solo si la cantidad cambia y está por debajo del límite
    IF NEW.CantidadDisponible <> OLD.CantidadDisponible
       AND NEW.CantidadDisponible <= 10 THEN

        -- Registrar una sola advertencia por ingrediente
        IF NOT EXISTS (
            SELECT 1 
            FROM Notificacion
            WHERE Tipo = 'Ingrediente'
              AND idIngrediente = NEW.idIngrediente
        ) THEN

            INSERT INTO Notificacion (Tipo, idIngrediente, CantidadActual, Mensaje)
            VALUES (
                'Ingrediente',
                NEW.idIngrediente,
                NEW.CantidadDisponible,
                CONCAT(
                    'Advertencia: el ingrediente "', NEW.Nombre,
                    '" tiene inventario bajo (', NEW.CantidadDisponible, ' ',
                    NEW.UnidadMedida, ').'
                )
            );

        END IF;
    END IF;
END$$

CREATE TRIGGER trg_PlatilloBajo
AFTER UPDATE ON DetallePlatillo
FOR EACH ROW
BEGIN
    DECLARE vNombrePlatillo VARCHAR(100);

    -- Solo aplica si la cantidad cambia y está baja
    IF NEW.CantidadDisponible <> OLD.CantidadDisponible
       AND NEW.CantidadDisponible <= 5 THEN

        -- Obtener nombre del platillo
        SELECT Nombre INTO vNombrePlatillo
        FROM Platillo
        WHERE idPlatillo = NEW.idPlatillo;

        -- Evitar duplicación de la misma advertencia
        IF NOT EXISTS (
            SELECT 1 
            FROM Notificacion
            WHERE Tipo = 'Platillo'
              AND idPlatillo = NEW.idPlatillo
        ) THEN

            INSERT INTO Notificacion (Tipo, idPlatillo, CantidadActual, Mensaje)
            VALUES (
                'Platillo',
                NEW.idPlatillo,
                NEW.CantidadDisponible,
                CONCAT(
                    'Aviso: el platillo "', vNombrePlatillo,
                    '" tiene poca disponibilidad (', NEW.CantidadDisponible, ' porciones).'
                )
            );

        END IF;
    END IF;
END$$

DELIMITER ;
