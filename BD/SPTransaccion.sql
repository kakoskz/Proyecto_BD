CREATE FUNCTION fn_TotalTransaccion (@idTransaccion INT)
RETURNS INT
AS
BEGIN
    DECLARE @total INT;

    SELECT @total = SUM(totaLinea)
    FROM DetalleVenta
    WHERE idTransaccion = @idTransaccion;

    RETURN ISNULL(@total, 0);
END;
GO


CREATE PROCEDURE spReporteVentasPorTransaccion
AS
BEGIN
    SET NOCOUNT ON;

    SELECT 
        T.idTransaccion,
        T.fecha,
        C.nombre AS Cliente,
        T.sucursal,
        dbo.fn_TotalTransaccion(T.idTransaccion) AS TotalTransaccion
    FROM Transaccion T
    INNER JOIN Cliente C ON T.idCliente = C.idCliente; 
END;
GO

