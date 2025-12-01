DELIMITER $$

CREATE PROCEDURE RegistrarPersona (
    IN p_Nombre VARCHAR(100),
    IN p_ApellidoPaterno VARCHAR(100),
    IN p_ApellidoMaterno VARCHAR(100),
    IN p_Telefono VARCHAR(15),
    IN p_Email VARCHAR(100),
    IN p_Edad INT,
    IN p_Sexo ENUM('Masculino', 'Femenino', 'Otro'),
    IN p_Usuario VARCHAR(50),
    IN p_Contrasena VARCHAR(255),
    IN p_Imagen VARCHAR(255),
    IN p_idRol INT
)
BEGIN
    DECLARE v_idPersona INT;

    INSERT INTO Persona (
        Nombre, ApellidoPaterno, ApellidoMaterno, Telefono, Email, Edad, Sexo,
        Usuario, Contrasena, Imagen, idRol
    )
    VALUES (
        p_Nombre, p_ApellidoPaterno, p_ApellidoMaterno, p_Telefono, p_Email,
        p_Edad, p_Sexo, p_Usuario, p_Contrasena, p_Imagen, p_idRol
    );

    SET v_idPersona = LAST_INSERT_ID();

END$$


CREATE PROCEDURE EditarPersona (
    IN p_idPersona INT,
    IN p_Nombre VARCHAR(100),
    IN p_ApellidoPaterno VARCHAR(100),
    IN p_ApellidoMaterno VARCHAR(100),
    IN p_Telefono VARCHAR(15),
    IN p_Email VARCHAR(100),
    IN p_Edad INT,
    IN p_Sexo ENUM('Masculino', 'Femenino', 'Otro'),
    IN p_Estatus ENUM('Activo', 'Inactivo'), 
    IN p_Usuario VARCHAR(50),
    IN p_Imagen VARCHAR(255),
    IN p_idRol INT
)
BEGIN
    DECLARE v_DatoAnterior TEXT;
    DECLARE v_DatoNuevo TEXT;

    UPDATE Persona
    SET 
        Nombre = p_Nombre,
        ApellidoPaterno = p_ApellidoPaterno,
        ApellidoMaterno = p_ApellidoMaterno,
        Telefono = p_Telefono,
        Email = p_Email,
        Edad = p_Edad,
        Sexo = p_Sexo,
        Estatus = p_Estatus,
        Usuario = p_Usuario,
        Imagen = p_Imagen,
        idRol = p_idRol
    WHERE idPersona = p_idPersona;

END$$

CREATE PROCEDURE EliminarPersona (
    IN p_idPersona INT
)
BEGIN
    DELETE FROM Persona
    WHERE idPersona = p_idPersona;
END$$

CREATE PROCEDURE EditarContrasenaPersona (
    IN p_idPersona INT,
    IN p_ContrasenaNueva VARCHAR(255)
)
BEGIN
    UPDATE Persona
    SET Contrasena = p_ContrasenaNueva
    WHERE idPersona = p_idPersona;
END$$

CREATE PROCEDURE AgregarCategoria (
    IN p_Nombre VARCHAR(100),
    IN p_Descripcion VARCHAR(255),
    IN p_Imagen VARCHAR(255)
)
BEGIN
    INSERT INTO Categoria (Nombre, Descripcion, Imagen)
    VALUES (p_Nombre, p_Descripcion, p_Imagen);
END$$

CREATE PROCEDURE EditarCategoria (
    IN p_idCategoria INT,
    IN p_Nombre VARCHAR(100),
    IN p_Descripcion VARCHAR(255),
    IN p_Imagen VARCHAR(255)
)
BEGIN
    UPDATE Categoria
    SET 
        Nombre = p_Nombre,
        Descripcion = p_Descripcion,
        Imagen = p_Imagen
    WHERE idCategoria = p_idCategoria;
END$$

CREATE PROCEDURE AgregarIngrediente (
    IN p_Nombre VARCHAR(100),
    IN p_UnidadMedida VARCHAR(50),
    IN p_CantidadDisponible DECIMAL(10,2),
    IN p_CostoUnitario DECIMAL(10,2),
    IN p_Imagen VARCHAR(255)
)
BEGIN
    -- Validar que el nombre no exista
    IF (SELECT COUNT(*) FROM Ingrediente WHERE Nombre = p_Nombre) > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Ya existe un ingrediente con ese nombre';
    END IF;

    INSERT INTO Ingrediente (Nombre, UnidadMedida, CantidadDisponible, CostoUnitario, Estatus, Imagen)
    VALUES (p_Nombre, p_UnidadMedida, p_CantidadDisponible, p_CostoUnitario, 'Activo', p_Imagen);
END$$

CREATE PROCEDURE EditarIngrediente (
    IN p_idIngrediente INT,
    IN p_Nombre VARCHAR(100),
    IN p_UnidadMedida VARCHAR(50),
    IN p_CantidadDisponible DECIMAL(10,2),
    IN p_CostoUnitario DECIMAL(10,2),
    IN p_Estatus ENUM('Activo', 'Inactivo'),
    IN p_Imagen VARCHAR(255)
)
BEGIN
    -- Verificar que el ingrediente existe
    IF (SELECT COUNT(*) FROM Ingrediente WHERE idIngrediente = p_idIngrediente) = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'El ingrediente no existe';
    END IF;

    -- Validar nombre único excepto el mismo ingrediente
    IF (SELECT COUNT(*)
        FROM Ingrediente
        WHERE Nombre = p_Nombre
          AND idIngrediente <> p_idIngrediente) > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Ya existe otro ingrediente con ese nombre';
    END IF;

    UPDATE Ingrediente
    SET 
        Nombre = p_Nombre,
        UnidadMedida = p_UnidadMedida,
        CantidadDisponible = p_CantidadDisponible,
        CostoUnitario = p_CostoUnitario,
        Estatus = p_Estatus,
        Imagen = p_Imagen
    WHERE idIngrediente = p_idIngrediente;
END$$

CREATE PROCEDURE EliminarIngrediente (
    IN p_idIngrediente INT
)
BEGIN
    DELETE FROM Ingrediente
    WHERE idIngrediente = p_idIngrediente;
END$$

CREATE PROCEDURE AgregarPlatillo (
    IN p_Nombre VARCHAR(100),
    IN p_Descripcion TEXT,
    IN p_PrecioVenta DECIMAL(10,2),
    IN p_Imagen VARCHAR(255),
    IN p_idCategoria INT,
    IN p_idReceta INT,                   -- Nuevo requerido
    IN p_Cantidad DECIMAL(10,2),
    IN p_CantidadDisponible DECIMAL(10,2)
)
BEGIN
    DECLARE v_idPlatillo INT;

    -- Validar categoría
    IF (SELECT COUNT(*) FROM Categoria WHERE idCategoria = p_idCategoria) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La categoría no existe';
    END IF;

    -- Validar receta
    IF (SELECT COUNT(*) FROM Receta WHERE idReceta = p_idReceta) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La receta no existe';
    END IF;

    -- Validar que la receta no esté ya usada
    IF (SELECT COUNT(*) FROM Platillo WHERE idReceta = p_idReceta) > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Esa receta ya está asignada a otro platillo';
    END IF;

    -- Insertar en Platillo
    INSERT INTO Platillo (Nombre, Descripcion, PrecioVenta, Imagen, idCategoria, idReceta)
    VALUES (p_Nombre, p_Descripcion, p_PrecioVenta, p_Imagen, p_idCategoria, p_idReceta);

    -- Obtener ID generado
    SET v_idPlatillo = LAST_INSERT_ID();

    -- Insertar detalle de existencia
    INSERT INTO DetallePlatillo (idPlatillo, Cantidad, CantidadDisponible)
    VALUES (v_idPlatillo, p_Cantidad, p_CantidadDisponible);

END$$

CREATE PROCEDURE EditarPlatillo(
    IN p_idPlatillo INT,
    IN p_Nombre VARCHAR(100),
    IN p_Descripcion TEXT,
    IN p_PrecioVenta DECIMAL(10,2),
    IN p_Imagen VARCHAR(255),
    IN p_idCategoria INT,
    IN p_idReceta INT           -- Debe poder actualizarse también
)
BEGIN
    -- Verificar que exista
    IF (SELECT COUNT(*) FROM Platillo WHERE idPlatillo = p_idPlatillo) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El platillo no existe';
    END IF;

    -- Verificar nueva categoría
    IF (SELECT COUNT(*) FROM Categoria WHERE idCategoria = p_idCategoria) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La categoría no existe';
    END IF;

    -- Verificar receta
    IF (SELECT COUNT(*) FROM Receta WHERE idReceta = p_idReceta) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La receta no existe';
    END IF;

    -- Prevenir duplicación de nombres
    IF (
        SELECT COUNT(*) FROM Platillo 
        WHERE Nombre = p_Nombre AND idPlatillo <> p_idPlatilloT
    ) > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ya existe un platillo con ese nombre';
    END IF;

    -- Validar que la receta no esté ya asignada a otro platillo
    IF (
        SELECT COUNT(*) FROM Platillo
        WHERE idReceta = p_idReceta AND idPlatillo <> p_idPlatillo
    ) > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Esa receta ya está asignada a otro platillo';
    END IF;

    -- Actualización
    UPDATE Platillo
    SET 
        Nombre = p_Nombre,
        Descripcion = p_Descripcion,
        PrecioVenta = p_PrecioVenta,
        Imagen = p_Imagen,
        idCategoria = p_idCategoria,
        idReceta = p_idReceta
    WHERE idPlatillo = p_idPlatillo;

END$$

CREATE PROCEDURE EliminarPlatillo (
    IN p_idPlatillo INT
)
BEGIN
    DELETE FROM Platillo
    WHERE idPlatillo = p_idPlatillo;
END$$

CREATE PROCEDURE CrearReceta (
    IN p_Instrucciones TEXT,
    IN p_idPersona INT,
    IN p_IngredientesJSON JSON
)
BEGIN
    DECLARE v_idReceta INT;
    DECLARE v_index INT DEFAULT 0;
    DECLARE v_total INT;
    DECLARE v_idIngrediente INT;
    DECLARE v_cantidad DECIMAL(10,2);

    -- Insertar receta
    INSERT INTO Receta (Instrucciones, idPersona)
    VALUES (p_Instrucciones, p_idPersona);

    SET v_idReceta = LAST_INSERT_ID();

    -- Total de elementos del JSON
    SET v_total = JSON_LENGTH(p_IngredientesJSON);

    -- Recorrer JSON
    WHILE v_index < v_total DO
        
        SET v_idIngrediente = JSON_UNQUOTE(JSON_EXTRACT(p_IngredientesJSON, CONCAT('$[', v_index, '].idIngrediente')));
        SET v_cantidad      = JSON_UNQUOTE(JSON_EXTRACT(p_IngredientesJSON, CONCAT('$[', v_index, '].CantidadRequerida')));

        INSERT INTO DetalleReceta (idReceta, idIngrediente, CantidadRequerida)
        VALUES (v_idReceta, v_idIngrediente, v_cantidad);

        SET v_index = v_index + 1;
    END WHILE;

END$$

CREATE PROCEDURE EditarReceta (
    IN p_idReceta INT,
    IN p_Instrucciones TEXT,
    IN p_IngredientesJSON JSON
)
BEGIN
    -- Actualizar instrucciones
    UPDATE Receta
    SET Instrucciones = p_Instrucciones
    WHERE idReceta = p_idReceta;

    -- Eliminar ingredientes anteriores
    DELETE FROM DetalleReceta
    WHERE idReceta = p_idReceta;

    -- Insertar nuevos ingredientes
    INSERT INTO DetalleReceta (idReceta, idIngrediente, CantidadRequerida)
    SELECT 
        p_idReceta,
        idIngrediente,
        CantidadRequerida
    FROM JSON_TABLE(p_IngredientesJSON, '$[*]'
        COLUMNS (
            idIngrediente INT PATH '$.idIngrediente',
            CantidadRequerida DECIMAL(10,2) PATH '$.CantidadRequerida'
        )
    ) AS t;

END $$

CREATE PROCEDURE EliminarReceta (
    IN p_idReceta INT
)
BEGIN
    DELETE FROM Receta WHERE idReceta = p_idReceta;
END $$

CREATE PROCEDURE RegistrarVenta(
    IN p_idPersona INT,
    IN p_TipoPago ENUM('Efectivo', 'Tarjeta', 'En línea'),
    IN p_DetalleVentaJSON JSON
)
BEGIN
    DECLARE v_idVenta INT;

    -- VALIDACIÓN: No permitir vender más platillos de los disponibles (suma de DetallePlatillo)
    IF EXISTS (
        SELECT 1
        FROM JSON_TABLE(p_DetalleVentaJSON, '$[*]' COLUMNS (
            idPlatillo INT PATH '$.idPlatillo',
            Cantidad DECIMAL(10,2) PATH '$.Cantidad'
        )) AS jt
        JOIN (
            SELECT idPlatillo, SUM(CantidadDisponible) AS StockTotal
            FROM DetallePlatillo
            GROUP BY idPlatillo
        ) AS dp ON dp.idPlatillo = jt.idPlatillo
        WHERE jt.Cantidad > dp.StockTotal
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No hay suficiente cantidad disponible del platillo solicitado.';
    END IF;

    START TRANSACTION;

    -- 1) Registrar venta
    INSERT INTO Venta (TipoPago, idPersona)
    VALUES (p_TipoPago, p_idPersona);

    SET v_idVenta = LAST_INSERT_ID();

    -- 2) Insertar detalles de venta desde JSON
    INSERT INTO DetalleVenta (idVenta, idPlatillo, Cantidad, PrecioUnitario)
    SELECT 
        v_idVenta,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(value, '$.idPlatillo')) AS UNSIGNED),
        CAST(JSON_UNQUOTE(JSON_EXTRACT(value, '$.Cantidad')) AS DECIMAL(10,2)),
        CAST(JSON_UNQUOTE(JSON_EXTRACT(value, '$.PrecioUnitario')) AS DECIMAL(10,2))
    FROM JSON_TABLE(p_DetalleVentaJSON, '$[*]' COLUMNS (value JSON PATH '$')) AS jt;

    -- 3) Descontar stock de platillos desde DetallePlatillo (FIFO)
    -- Usamos un bloque con todas las DECLARE al inicio
    BEGIN
        DECLARE done INT DEFAULT 0;
        DECLARE v_idPlatillo INT;
        DECLARE v_CantVenta DECIMAL(10,2);
        DECLARE v_idDetalle INT;
        DECLARE v_disp DECIMAL(10,2);

        -- Cursor para recorrer los items vendidos en esta venta
        DECLARE cur CURSOR FOR
            SELECT idPlatillo, Cantidad
            FROM DetalleVenta
            WHERE idVenta = v_idVenta;

        -- Handler para finalizar cuando no hay más filas en el cursor
        DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

        SET done = 0;
        OPEN cur;

        read_loop: LOOP
            FETCH cur INTO v_idPlatillo, v_CantVenta;
            IF done THEN
                LEAVE read_loop;
            END IF;

            -- Descontar usando FIFO (lotes por FechaPreparacion ascendente)
            WHILE v_CantVenta > 0 DO

                -- obtener el lote más antiguo con CantidadDisponible > 0
                -- Si no existe, forzamos salida (esto no debería ocurrir por la validación previa)
                SELECT idDetallePlatillo, CantidadDisponible
                INTO v_idDetalle, v_disp
                FROM DetallePlatillo
                WHERE idPlatillo = v_idPlatillo
                  AND CantidadDisponible > 0
                ORDER BY FechaPreparacion ASC
                LIMIT 1;

                -- Si por alguna razón no hay lote (v_idDetalle IS NULL) --> señalamos error
                IF v_idDetalle IS NULL THEN
                    -- Revierte toda la transacción y notifica
                    ROLLBACK;
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Error interno: lote de DetallePlatillo no encontrado al descontar (inconsistencia).';
                END IF;

                IF v_CantVenta <= v_disp THEN
                    UPDATE DetallePlatillo
                    SET CantidadDisponible = CantidadDisponible - v_CantVenta
                    WHERE idDetallePlatillo = v_idDetalle;
                    SET v_CantVenta = 0;
                ELSE
                    UPDATE DetallePlatillo
                    SET CantidadDisponible = 0
                    WHERE idDetallePlatillo = v_idDetalle;
                    SET v_CantVenta = v_CantVenta - v_disp;
                END IF;

            END WHILE; -- fin while por cada item vendido

        END LOOP; -- fin read_loop

        CLOSE cur;
    END;

    -- 4) Registrar inicialmente en Finanzas y calcular TotalInvertido
    INSERT INTO Finanzas (idVenta, TotalVenta, TotalInvertido)
    VALUES (v_idVenta, 0, 0);

    UPDATE Finanzas f
    JOIN (
        SELECT dv.idVenta,
               SUM(dr.CantidadRequerida * i.CostoUnitario) AS TotalInvertido
        FROM DetalleVenta dv
        JOIN Platillo p ON dv.idPlatillo = p.idPlatillo
        JOIN Receta r ON p.idReceta = r.idReceta
        JOIN DetalleReceta dr ON r.idReceta = dr.idReceta
        JOIN Ingrediente i ON dr.idIngrediente = i.idIngrediente
        GROUP BY dv.idVenta
    ) AS inv ON f.idVenta = inv.idVenta
    SET f.TotalInvertido = inv.TotalInvertido;

    -- 5) Restar ingredientes (según tus reglas)
    UPDATE Ingrediente i
    JOIN (
        SELECT dr.idIngrediente, SUM(dv.Cantidad * dr.CantidadRequerida) AS CantidadTotal
        FROM DetalleVenta dv
        JOIN Platillo p ON dv.idPlatillo = p.idPlatillo
        JOIN Receta r ON p.idReceta = r.idReceta
        JOIN DetalleReceta dr ON r.idReceta = dr.idReceta
        GROUP BY dr.idIngrediente
    ) AS t ON i.idIngrediente = t.idIngrediente
    SET i.CantidadDisponible = GREATEST(i.CantidadDisponible - t.CantidadTotal, 0);

    -- 6) Actualizar TotalVenta en Finanzas
    UPDATE Finanzas f
    JOIN (
        SELECT dv.idVenta, SUM(dv.Cantidad * dv.PrecioUnitario) AS TotalVenta
        FROM DetalleVenta dv
        GROUP BY dv.idVenta
    ) AS t ON f.idVenta = t.idVenta
    SET f.TotalVenta = t.TotalVenta;

    -- 7) Limpiar carrito del cliente
    DELETE dc
    FROM DetalleCarrito dc
    JOIN Carrito c ON dc.idCarrito = c.idCarrito
    WHERE c.idPersona = p_idPersona;

    COMMIT;
END $$


DELIMITER $$

CREATE PROCEDURE RegistrarVentaDesdePedido(
    IN p_idPedido INT,
    IN p_idPersona INT,
    IN p_TipoPago ENUM('Efectivo', 'Tarjeta', 'En línea')
)
BEGIN
    DECLARE v_idVenta INT;

    -- Manejo de errores
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -------------------------------------------------------------------
    -- VALIDACIÓN PRINCIPAL: antes de todo, revisar stock por PLATILLO
    -- Si el pedido solicita MÁS de lo disponible, se cancela la venta.
    -------------------------------------------------------------------
    IF EXISTS (
        SELECT 1
        FROM DetallePedido dp
        LEFT JOIN (
            SELECT idPlatillo, COALESCE(SUM(CantidadDisponible),0) AS StockActual
            FROM DetallePlatillo
            GROUP BY idPlatillo
        ) AS inv ON dp.idPlatillo = inv.idPlatillo
        WHERE dp.idPedido = p_idPedido
          AND dp.Cantidad > COALESCE(inv.StockActual,0)
    ) THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stock insuficiente de uno o más platillos. Contacte a la vendedora antes de continuar.';
    END IF;

    -------------------------------------------------------------------
    -- VALIDACIÓN SECUNDARIA: ingredientes según receta
    -------------------------------------------------------------------
    IF EXISTS (
        SELECT 1
        FROM (
            SELECT dr.idIngrediente,
                   SUM(dp.Cantidad * dr.CantidadRequerida) AS CantTotalRequerida
            FROM DetallePedido dp
            JOIN Platillo pl ON dp.idPlatillo = pl.idPlatillo
            JOIN Receta r ON pl.idReceta = r.idReceta
            JOIN DetalleReceta dr ON r.idReceta = dr.idReceta
            WHERE dp.idPedido = p_idPedido
            GROUP BY dr.idIngrediente
        ) AS req
        JOIN Ingrediente i ON i.idIngrediente = req.idIngrediente
        WHERE req.CantTotalRequerida > COALESCE(i.CantidadDisponible,0)
    ) THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No hay suficiente inventario de ingredientes para preparar los platillos.';
    END IF;

    -------------------------------------------------------------------
    -- REGISTRO DE LA VENTA (ya pasó validaciones)
    -------------------------------------------------------------------
    INSERT INTO Venta (TipoPago, idPersona)
    VALUES (p_TipoPago, p_idPersona);

    SET v_idVenta = LAST_INSERT_ID();

    -- Insertar detalles
    INSERT INTO DetalleVenta (idVenta, idPlatillo, Cantidad, PrecioUnitario)
    SELECT v_idVenta, dp.idPlatillo, dp.Cantidad, dp.PrecioUnitario
    FROM DetallePedido dp
    WHERE dp.idPedido = p_idPedido;

    -------------------------------------------------------------------
    -- Actualización de inventario de INGREDIENTES
    -------------------------------------------------------------------
    UPDATE Ingrediente i
    JOIN (
        SELECT dr.idIngrediente,
               SUM(dp.Cantidad * dr.CantidadRequerida) AS CantUsada
        FROM DetallePedido dp
        JOIN Platillo pl ON dp.idPlatillo = pl.idPlatillo
        JOIN Receta r ON pl.idReceta = r.idReceta
        JOIN DetalleReceta dr ON r.idReceta = dr.idReceta
        WHERE dp.idPedido = p_idPedido
        GROUP BY dr.idIngrediente
    ) AS x ON i.idIngrediente = x.idIngrediente
    SET i.CantidadDisponible = GREATEST(i.CantidadDisponible - x.CantUsada, 0);

    -------------------------------------------------------------------
    -- Actualización de inventario de PLATILLOS
    -------------------------------------------------------------------
    UPDATE DetallePlatillo inv
    JOIN DetallePedido dp ON inv.idPlatillo = dp.idPlatillo
    SET inv.CantidadDisponible = GREATEST(inv.CantidadDisponible - dp.Cantidad, 0)
    WHERE dp.idPedido = p_idPedido;

    -------------------------------------------------------------------
    -- FINANZAS
    -------------------------------------------------------------------
    INSERT INTO Finanzas (idVenta, TotalVenta, TotalInvertido)
    SELECT v_idVenta,
           COALESCE(SUM(dv.Total),0),
           COALESCE(SUM(dr.CantidadRequerida * i.CostoUnitario),0)
    FROM DetalleVenta dv
    LEFT JOIN Platillo p ON dv.idPlatillo = p.idPlatillo
    LEFT JOIN Receta r ON p.idReceta = r.idReceta
    LEFT JOIN DetalleReceta dr ON r.idReceta = dr.idReceta
    LEFT JOIN Ingrediente i ON dr.idIngrediente = i.idIngrediente
    WHERE dv.idVenta = v_idVenta
    GROUP BY dv.idVenta;

    -------------------------------------------------------------------
    -- CAMBIAR ESTATUS DEL PEDIDO A ENTREGADO
    -------------------------------------------------------------------
    UPDATE Pedido
    SET Estatus = 'Entregado'
    WHERE idPedido = p_idPedido;

    COMMIT;
END$$

DELIMITER ;



CREATE PROCEDURE AgregarPlatilloCarrito (
    IN p_idPersona INT,
    IN p_idPlatillo INT
)
BEGIN
    DECLARE v_idCarrito INT;
    DECLARE v_PrecioUnitario DECIMAL(10,2);

    START TRANSACTION;

    SELECT idCarrito INTO v_idCarrito
    FROM Carrito
    WHERE idPersona = p_idPersona
    LIMIT 1;

    IF v_idCarrito IS NULL THEN
        INSERT INTO Carrito (idPersona) VALUES (p_idPersona);
        SET v_idCarrito = LAST_INSERT_ID();
    END IF;

    SELECT PrecioVenta INTO v_PrecioUnitario
    FROM Platillo
    WHERE idPlatillo = p_idPlatillo
    LIMIT 1;

    IF v_PrecioUnitario IS NULL THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'El platillo especificado no existe o no tiene precio.';
    END IF;

    IF EXISTS (
        SELECT 1 FROM DetalleCarrito 
        WHERE idCarrito = v_idCarrito AND idPlatillo = p_idPlatillo
    ) THEN
        UPDATE DetalleCarrito
        SET Cantidad = Cantidad + 1
        WHERE idCarrito = v_idCarrito AND idPlatillo = p_idPlatillo;
    ELSE
    
        INSERT INTO DetalleCarrito (idCarrito, idPlatillo, Cantidad, PrecioUnitario)
        VALUES (v_idCarrito, p_idPlatillo, 1, v_PrecioUnitario);
    END IF;

    COMMIT;
END$$

CREATE PROCEDURE SumarCantidadCarrito (
    IN p_idPersona INT,
    IN p_idPlatillo INT
)
BEGIN
    DECLARE v_idCarrito INT;

    SELECT idCarrito INTO v_idCarrito
    FROM Carrito
    WHERE idPersona = p_idPersona
    LIMIT 1;

    UPDATE DetalleCarrito
    SET Cantidad = Cantidad + 1
    WHERE idCarrito = v_idCarrito AND idPlatillo = p_idPlatillo;
END$$

CREATE PROCEDURE RestarCantidadCarrito (
    IN p_idPersona INT,
    IN p_idPlatillo INT
)
BEGIN
    DECLARE v_idCarrito INT;
    DECLARE v_CantidadActual INT DEFAULT 0;

    START TRANSACTION;

    SELECT idCarrito INTO v_idCarrito
    FROM Carrito
    WHERE idPersona = p_idPersona
    LIMIT 1;

    IF v_idCarrito IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El usuario no tiene un carrito activo.';
    END IF;

    SELECT Cantidad INTO v_CantidadActual
    FROM DetalleCarrito
    WHERE idCarrito = v_idCarrito AND idPlatillo = p_idPlatillo
    LIMIT 1;
    
    IF v_CantidadActual IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El platillo no se encuentra en el carrito.';
    END IF;

    IF v_CantidadActual > 1 THEN
        UPDATE DetalleCarrito
        SET Cantidad = Cantidad - 1
        WHERE idCarrito = v_idCarrito AND idPlatillo = p_idPlatillo;
    ELSE

        DELETE FROM DetalleCarrito
        WHERE idCarrito = v_idCarrito AND idPlatillo = p_idPlatillo;
    END IF;

    COMMIT;
END$$

CREATE PROCEDURE ObtenerCarritoPorPersona(IN p_idPersona INT)
BEGIN
    SELECT 
        c.idCarrito, 
        dc.idDetalleCarrito,
        dc.idPlatillo,          
        p.Nombre AS Producto,
        p.Imagen AS Imagen, 
        dc.Cantidad, 
        dc.PrecioUnitario, 
        dc.Total
    FROM Carrito c
    JOIN DetalleCarrito dc ON c.idCarrito = dc.idCarrito
    JOIN Platillo p ON dc.idPlatillo = p.idPlatillo
    WHERE c.idPersona = p_idPersona
    ORDER BY p.Nombre;
END $$

CREATE PROCEDURE CrearPedido(
    IN p_idPersona INT,
    IN p_FechaEntrega DATE,
    IN p_DetallePedidoJSON JSON
)
BEGIN
    DECLARE v_idPedido INT;

    -- Manejo de errores SQL
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Crear el pedido
    INSERT INTO Pedido (idPersona, FechaEntrega, Estatus)
    VALUES (p_idPersona, p_FechaEntrega, 'Pendiente');

    SET v_idPedido = LAST_INSERT_ID();

    -- Insertar los detalles del pedido
    INSERT INTO DetallePedido (idPedido, idPlatillo, Cantidad, PrecioUnitario)
    SELECT 
        v_idPedido,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(det.value, '$.idPlatillo')) AS UNSIGNED),
        CAST(JSON_UNQUOTE(JSON_EXTRACT(det.value, '$.Cantidad')) AS UNSIGNED),
        CAST(JSON_UNQUOTE(JSON_EXTRACT(det.value, '$.PrecioUnitario')) AS DECIMAL(10,2))
    FROM JSON_TABLE(p_DetallePedidoJSON, '$[*]'
        COLUMNS ( value JSON PATH '$' )
    ) AS det;

    -- Limpiar solo los detalles del carrito del usuario
    DELETE dc
    FROM DetalleCarrito dc
    JOIN Carrito c ON c.idCarrito = dc.idCarrito
    WHERE c.idPersona = p_idPersona;

    COMMIT;
END $$

CREATE PROCEDURE CancelarPedido(
    IN p_idPedido INT
)
BEGIN
    DECLARE v_exists INT;

    -- Verificar que el pedido existe
    SELECT COUNT(*) INTO v_exists
    FROM Pedido
    WHERE idPedido = p_idPedido;

    IF v_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Error: El pedido no existe.';
    END IF;

    -- Actualizar el estatus del pedido a 'Cancelado'
    UPDATE Pedido
    SET Estatus = 'Cancelado'
    WHERE idPedido = p_idPedido;

END $$


DELIMITER $$

CREATE PROCEDURE RegistrarDevolucion (
    IN p_idPersona INT,
    IN p_Motivo VARCHAR(255),
    IN p_DetalleDevolucionJSON JSON
)
BEGIN
    DECLARE v_idDevolucion INT;
    DECLARE v_idVenta INT;
    DECLARE v_idDetalleVenta INT;
    DECLARE v_CantidadDevuelta INT;
    DECLARE v_TotalDevuelto DECIMAL(10,2);
    DECLARE done INT DEFAULT 0;

    -- Cursor para recorrer los detalles
    DECLARE cur CURSOR FOR
        SELECT
            CAST(JSON_UNQUOTE(JSON_EXTRACT(det.value, '$.idVenta')) AS UNSIGNED),
            CAST(JSON_UNQUOTE(JSON_EXTRACT(det.value, '$.idDetalleVenta')) AS UNSIGNED),
            CAST(JSON_UNQUOTE(JSON_EXTRACT(det.value, '$.CantidadDevuelta')) AS UNSIGNED),
            CAST(JSON_UNQUOTE(JSON_EXTRACT(det.value, '$.TotalDevuelto')) AS DECIMAL(10,2))
        FROM JSON_TABLE(p_DetalleDevolucionJSON, '$[*]' COLUMNS (value JSON PATH '$')) AS det;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;

    ---------------------------------------------------
    -- 1. Insertar registro principal de devolución
    ---------------------------------------------------
    INSERT INTO Devolucion (Motivo, idPersona)
    VALUES (p_Motivo, p_idPersona);

    SET v_idDevolucion = LAST_INSERT_ID();

    ---------------------------------------------------
    -- 2. Insertar detalles de devolución
    ---------------------------------------------------
    INSERT INTO DetalleDevolucion (idDevolucion, idVenta, idDetalleVenta, CantidadDevuelta, TotalDevuelto)
    SELECT
        v_idDevolucion,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(det.value, '$.idVenta')) AS UNSIGNED),
        CAST(JSON_UNQUOTE(JSON_EXTRACT(det.value, '$.idDetalleVenta')) AS UNSIGNED),
        CAST(JSON_UNQUOTE(JSON_EXTRACT(det.value, '$.CantidadDevuelta')) AS UNSIGNED),
        CAST(JSON_UNQUOTE(JSON_EXTRACT(det.value, '$.TotalDevuelto')) AS DECIMAL(10,2))
    FROM JSON_TABLE(p_DetalleDevolucionJSON, '$[*]' COLUMNS ( value JSON PATH '$' )) AS det;

    ---------------------------------------------------
    -- 3. Cursor para actualizar Finanzas e inventario
    ---------------------------------------------------
    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO v_idVenta, v_idDetalleVenta, v_CantidadDevuelta, v_TotalDevuelto;
        IF done = 1 THEN
            LEAVE read_loop;
        END IF;

        ---------------------------------------------------
        -- 3.1 Actualizar total de venta en Finanzas
        ---------------------------------------------------
        UPDATE Finanzas
        SET TotalVenta = TotalVenta - v_TotalDevuelto
        WHERE idVenta = v_idVenta;

        ---------------------------------------------------
        -- 3.2 Regresar ingredientes al inventario
        ---------------------------------------------------
        UPDATE Ingrediente i
        JOIN DetalleReceta dr ON dr.idIngrediente = i.idIngrediente
        JOIN Receta r        ON r.idReceta      = dr.idReceta
        JOIN DetalleVenta dv ON dv.idPlatillo   = r.idPlatillo
        SET i.CantidadDisponible = i.CantidadDisponible + (dr.CantidadRequerida * v_CantidadDevuelta)
        WHERE dv.idDetalleVenta = v_idDetalleVenta;

        ---------------------------------------------------
        -- 3.3 Validación: evitar totales negativos
        ---------------------------------------------------
        IF EXISTS (
            SELECT 1
            FROM Finanzas
            WHERE idVenta = v_idVenta
              AND TotalVenta < 0
        ) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Error: La devolución excede el monto total de la venta.';
        END IF;

    END LOOP;

    CLOSE cur;

    COMMIT;
END$$

DELIMITER ;
