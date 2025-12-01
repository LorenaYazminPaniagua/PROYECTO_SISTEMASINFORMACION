CREATE OR REPLACE VIEW VistaPlatillos AS
SELECT 
    p.idPlatillo,
    p.Nombre AS Platillo,
    p.Descripcion,
    p.PrecioVenta,
    p.Imagen,

    c.idCategoria,
    c.Nombre AS Categoria,
    c.Descripcion AS CategoriaDescripcion,

    r.idReceta,
    r.Instrucciones,
    r.FechaCreacion AS FechaReceta,
    
    pe.idPersona AS idCreador,
    CONCAT(pe.Nombre, ' ', pe.ApellidoPaterno, ' ', IFNULL(pe.ApellidoMaterno, '')) AS Creador,

    dr.idIngrediente,
    i.Nombre AS Ingrediente,
    dr.CantidadRequerida,
    i.UnidadMedida

FROM Platillo p
INNER JOIN Categoria c ON p.idCategoria = c.idCategoria
INNER JOIN Receta r ON p.idReceta = r.idReceta
INNER JOIN Persona pe ON r.idPersona = pe.idPersona
INNER JOIN DetalleReceta dr ON r.idReceta = dr.idReceta
INNER JOIN Ingrediente i ON dr.idIngrediente = i.idIngrediente;

CREATE OR REPLACE VIEW VistaIngredientes AS
SELECT
    idIngrediente,
    Nombre,
    UnidadMedida,
    CantidadDisponible,
    CostoUnitario,
    Estatus,
    Imagen
FROM Ingrediente;

CREATE OR REPLACE VIEW VistaRecetas AS
SELECT 
    r.idReceta,
    r.Instrucciones,
    r.FechaCreacion,
    pe.idPersona,
    CONCAT(pe.Nombre, ' ', pe.ApellidoPaterno) AS Creador,
    dr.idIngrediente,
    i.Nombre AS Ingrediente,
    dr.CantidadRequerida,
    i.UnidadMedida
FROM Receta r
INNER JOIN Persona pe ON r.idPersona = pe.idPersona
INNER JOIN DetalleReceta dr ON r.idReceta = dr.idReceta
INNER JOIN Ingrediente i ON dr.idIngrediente = i.idIngrediente;

CREATE VIEW VistaPedidos AS
SELECT 
    p.idPedido,
    p.Fecha,
    p.FechaEntrega,
    p.Estatus,
    per.idPersona,
    CONCAT(per.Nombre, ' ', per.ApellidoPaterno) AS Cliente,
    dp.idPlatillo,
    pl.Nombre AS Platillo,
    dp.Cantidad,
    dp.PrecioUnitario,
    dp.Total
FROM Pedido p
INNER JOIN Persona per ON p.idPersona = per.idPersona
INNER JOIN DetallePedido dp ON p.idPedido = dp.idPedido
INNER JOIN Platillo pl ON dp.idPlatillo = pl.idPlatillo;

CREATE VIEW VistaVentas AS
SELECT 
    v.idVenta,
    v.Fecha,
    v.TipoPago,
    v.Estatus,
    per.idPersona AS CajeroID,
    CONCAT(per.Nombre, ' ', per.ApellidoPaterno) AS Cajero,
    dv.idPlatillo,
    pl.Nombre AS Platillo,
    dv.Cantidad,
    dv.PrecioUnitario,
    dv.Total
FROM Venta v
INNER JOIN Persona per ON v.idPersona = per.idPersona
INNER JOIN DetalleVenta dv ON v.idVenta = dv.idVenta
INNER JOIN Platillo pl ON dv.idPlatillo = pl.idPlatillo;

CREATE VIEW VistaNotificaciones AS
SELECT
    n.idNotificacion,
    n.Tipo,
    n.CantidadActual,
    n.Mensaje,
    n.FechaCreacion,
    i.Nombre AS Ingrediente,
    p.Nombre AS Platillo
FROM Notificacion n
LEFT JOIN Ingrediente i ON n.idIngrediente = i.idIngrediente
LEFT JOIN Platillo p ON n.idPlatillo = p.idPlatillo;

CREATE VIEW VistaDevoluciones AS
SELECT
    d.idDevolucion,
    d.Fecha,
    d.Motivo,
    per.idPersona,
    CONCAT(per.Nombre, ' ', per.ApellidoPaterno) AS Cliente,
    dd.idVenta,
    dv.idPlatillo,
    pl.Nombre AS Platillo,
    dd.CantidadDevuelta,
    dd.TotalDevuelto
FROM Devolucion d
INNER JOIN Persona per ON d.idPersona = per.idPersona
INNER JOIN DetalleDevolucion dd ON d.idDevolucion = dd.idDevolucion
INNER JOIN Venta v ON dd.idVenta = v.idVenta
INNER JOIN DetalleVenta dv ON dd.idDetalleVenta = dv.idDetalleVenta
INNER JOIN Platillo pl ON dv.idPlatillo = pl.idPlatillo;

CREATE OR REPLACE VIEW VistaUsuarios AS
SELECT 
    p.idPersona,
    CONCAT(p.Nombre, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
    p.Telefono,
    p.Email,
    p.Usuario,
    r.NombreRol AS Rol,
    p.Estatus,
    p.Imagen
FROM Persona p
INNER JOIN Rol r ON p.idRol = r.idRol;

CREATE OR REPLACE VIEW VistaCategorias AS
SELECT 
    c.idCategoria,
    c.Nombre,
    c.Descripcion,
    c.Imagen,
    COUNT(p.idPlatillo) AS TotalPlatillos
FROM Categoria c
LEFT JOIN Platillo p ON c.idCategoria = p.idCategoria
GROUP BY c.idCategoria, c.Nombre, c.Descripcion, c.Imagen;

CREATE OR REPLACE VIEW VistaFinanzas AS
SELECT
    f.idFinanzas,
    v.idVenta,
    v.Fecha AS FechaVenta,
    v.TipoPago,
    CONCAT(p.Nombre, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS Vendedor,
    f.TotalVenta,
    f.TotalInvertido,
    f.Ganancia
FROM Finanzas f
INNER JOIN Venta v ON f.idVenta = v.idVenta
INNER JOIN Persona p ON v.idPersona = p.idPersona;
