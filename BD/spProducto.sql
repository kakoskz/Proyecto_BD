CREATE PROCEDURE spIngresarProducto(
    @idCategoria INT,
    @nombre VARCHAR(500),
    @stock INT,
    @precioUnitario INT,
    @descripcion VARCHAR(500)
)
AS
BEGIN
    SET NOCOUNT ON;

    -- Validar que la categoría exista
    IF NOT EXISTS (SELECT 1 FROM Categoria WHERE idCategoria = @idCategoria)
    BEGIN
        RAISERROR('La categoría no existe', 16, 1);
        RETURN;
    END

    INSERT INTO Producto(idCategoria, nombre, stock, precio_unitario, descripcion)
    VALUES (@idCategoria, @nombre, @stock, @precioUnitario, @descripcion);

    -- Devolver el ID del producto creado
    SELECT SCOPE_IDENTITY() AS idProducto;
END;
GO


CREATE PROCEDURE spBuscarProducto
(
    @nombre VARCHAR(500)
)
AS
BEGIN
    SET NOCOUNT ON;

    SELECT 
        P.idProducto,
        P.nombre,
        P.stock,
        P.precio_unitario,
        P.descripcion,
        C.nombre AS Categoria
    FROM Producto P
    INNER JOIN Categoria C ON P.idCategoria = C.idCategoria
    WHERE P.nombre LIKE '%' + @nombre + '%';
END;
GO

-- utilizamos el id por el hecho de que no es una tabla enfocada a la realidad con sku, codigo de barras, modelos y marca
--se simplifico bastante para no agrandar tanto el proyecto
CREATE PROCEDURE spModificarProducto(
    @idProducto     INT,
    @idCategoria    INT,
    @nombre         VARCHAR(500),
    @stock          INT,
    @precioUnitario INT,
    @descripcion    VARCHAR(500) = NULL
)
AS
BEGIN
    SET NOCOUNT ON;

    -- validar que el producto exista
    IF NOT EXISTS (SELECT 1 FROM Producto WHERE idProducto = @idProducto)
    BEGIN
        RAISERROR('El producto no existe.', 16, 1);
        RETURN;
    END

    -- validar que la categoría exista
    IF NOT EXISTS (SELECT 1 FROM Categoria WHERE idCategoria = @idCategoria)
    BEGIN
        RAISERROR('La categoría indicada no existe.', 16, 1);
        RETURN;
    END

    UPDATE Producto
    SET 
        idCategoria    = @idCategoria,
        nombre         = @nombre,
        stock          = @stock,
        precio_unitario = @precioUnitario,
        descripcion    = @descripcion
    WHERE idProducto = @idProducto;
END;

CREATE PROCEDURE spEliminarProducto(
    @idProducto INT
)
AS
BEGIN
    SET NOCOUNT ON;

    -- validar que el producto exista
    IF NOT EXISTS (SELECT 1 FROM Producto WHERE idProducto = @idProducto)
    BEGIN
        RAISERROR('El producto no existe.', 16, 1);
        RETURN;
    END

    -- validar si tiene ventas asociadas
    IF EXISTS (SELECT 1 FROM DetalleVenta WHERE idProducto = @idProducto)
    BEGIN
        RAISERROR('No se puede eliminar el producto porque tiene ventas asociadas.', 16, 1);
        RETURN;
    END

    DELETE FROM Producto
    WHERE idProducto = @idProducto;
END;

CREATE PROCEDURE sp_Categoria_Listar
AS
BEGIN
    SET NOCOUNT ON;
    SELECT idCategoria, nombre FROM dbo.Categoria;
END
GO

CREATE PROCEDURE spObtenerProducto
    @idProducto INT
AS
BEGIN
    SET NOCOUNT ON;
    SELECT idProducto, idCategoria, nombre, stock, precio_unitario, descripcion 
    FROM Producto 
    WHERE idProducto = @idProducto;
END
GO