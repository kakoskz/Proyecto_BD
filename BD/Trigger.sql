CREATE TRIGGER trg_LogDetalleVenta
ON DetalleVenta
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;

    INSERT INTO LogDetalleVenta (idDetalle, idTransaccion, idProducto, cantidad)
    SELECT 
        idDetalle,
        idTransaccion,
        idProducto,
        cantidad
    FROM inserted;
END;
GO
