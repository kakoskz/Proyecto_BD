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

-- Transaccion(idTransaccion, idCliente, idEmpleado, fecha, sucursal, total)

CREATE PROCEDURE spInsertarTransaccion
    @idCliente  INT,
    @idEmpleado INT,
    @sucursal   VARCHAR(500)
AS
BEGIN
    SET NOCOUNT ON;

    -- Validaciones básicas
    IF NOT EXISTS (SELECT 1 FROM Cliente WHERE idCliente = @idCliente)
    BEGIN
        RAISERROR('El cliente no existe', 16, 1);
        RETURN;
    END;

    IF NOT EXISTS (SELECT 1 FROM Empleado WHERE idEmpleado = @idEmpleado)
    BEGIN
        RAISERROR('El empleado no existe', 16, 1);
        RETURN;
    END;

    -- Creamos la cabecera con total = 0
    INSERT INTO Transaccion (idCliente, idEmpleado, sucursal, total)
    VALUES (@idCliente, @idEmpleado, @sucursal, 0);

    -- Devolvemos el idTransaccion para usarlo en DetalleVenta
    SELECT SCOPE_IDENTITY() AS idTransaccion;
END;
GO


-- DetalleVenta(idDetalle, idTransaccion, idProducto,
--              cantidad, descuento, precioUnitario, precioFinal, totaLinea)

CREATE PROCEDURE spInsertarDetalleVenta
    @idTransaccion INT,
    @idProducto    INT,
    @cantidad      INT,
    @descuento     INT = 0,
    @precioUnitario INT
AS
BEGIN
    SET NOCOUNT ON;

    IF NOT EXISTS (SELECT 1 FROM Transaccion WHERE idTransaccion = @idTransaccion)
    BEGIN
        RAISERROR('La transacción no existe', 16, 1);
        RETURN;
    END;

    IF NOT EXISTS (SELECT 1 FROM Producto WHERE idProducto = @idProducto)
    BEGIN
        RAISERROR('El producto no existe', 16, 1);
        RETURN;
    END;

    DECLARE @precioFinal INT;
    DECLARE @totaLinea   INT;

    -- precioFinal = precioUnitario - descuento (ajusta la lógica si la quieres distinta)
    SET @precioFinal = @precioUnitario - @descuento;
    SET @totaLinea   = @precioFinal * @cantidad;

    INSERT INTO DetalleVenta (
        idTransaccion, idProducto, cantidad, descuento,
        precioUnitario, precioFinal, totaLinea
    )
    VALUES (
        @idTransaccion, @idProducto, @cantidad, @descuento,
        @precioUnitario, @precioFinal, @totaLinea
    );

    -- Actualizar el total de la transacción usando fn_TotalTransaccion
    UPDATE Transaccion
    SET total = dbo.fn_TotalTransaccion(@idTransaccion)
    WHERE idTransaccion = @idTransaccion;
END;
GO
