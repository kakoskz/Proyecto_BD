CREATE PROCEDURE spInsertarClienteUsuario(
    @email VARCHAR(300),
    @psswd VARCHAR(255),
    @nombre VARCHAR(250),
    @alias  VARCHAR(50) = NULL,
    @rut VARCHAR(10),
    @domicilio  VARCHAR(500),
    @ciudad VARCHAR(100),
    @genero VARCHAR(50) = NULL,
    @codigoPostal   VARCHAR(15) = NULL,
    @preferencias    VARCHAR(500) = NULL
)
AS
BEGIN
    SET NOCOUNT ON;

--primero hay que validar si es que existe el USUARIO
    IF EXISTS (SELECT 1 FROM Users WHERE email = @email)
    BEGIN
        RAISERROR('El Correo ya esta en uso', 16, 1)
        RETURN;
    END
--segundo validamos si es que existe el CLIENTE
        IF EXISTS(SELECT 1 FROM Cliente WHERE rut = @rut)
    BEGIN 
        RAISERROR('El cliente ya existe (RUT DUPLICADO', 16, 1);
        RETURN;
    END
    INSERT INTO Users (email, psswd)
    VALUES (@email, @psswd);

    DECLARE @idUser INT;
    SET @idUser = SCOPE_IDENTITY(); --recibe el ultimo id creado para asignarselo al Cliente

    INSERT INTO Cliente (idUser, nombre, alias, rut, domicilio, ciudad, genero, codigoPostal, preferencias)
    VALUES (@idUser, @nombre, @alias, @rut, @domicilio, @ciudad, @genero, @codigoPostal, @preferencias);

    DECLARE @idCliente INT;
    SET @idCliente = SCOPE_IDENTITY();-- recibe el ultimo id creado

    SELECT 
        @idUser AS idUser,
        @idCliente AS idCliente;
END;


CREATE PROCEDURE spInsertarClienteInvitado(
    @nombre VARCHAR(250),
    @rut VARCHAR(10),
    @domicilio VARCHAR(500),
    @ciudad VARCHAR(100)
)
AS 
BEGIN
    SET NOCOUNT ON;

    DECLARE @idCliente INT;
    -- buscamos si es que el cliente existe con ese rut(compras anteriores)
    SELECT @idCliente = idCliente
    FROM Cliente
    WHERE rut = @rut ;


    --ahora lo mostramos si es que existe

    IF @idCliente IS NOT NULL
    BEGIN
        --modificamos primero los datos relevantes
        UPDATE Cliente 
        SET nombre = @nombre,
            domicilio = @domicilio,
            ciudad = @ciudad     
        WHERE idCliente = @idCliente;

        --lo mostramos
        SELECT 
            idCliente,
            nombre,
            domicilio,
            ciudad
        FROM Cliente
        WHERE rut = @rut
            
        RETURN;
    END    
    --si no existe lo creamos 
    INSERT INTO Cliente (idUser, nombre, rut, domicilio, ciudad)
    VALUES (NULL, @nombre, @rut, @domicilio, @ciudad);

    SET @idCliente = SCOPE_IDENTITY(); --lo capturamos si es que se necesita mas adelante
    --mostramos el registro recien creado
    SELECT 
        idCliente,
        nombre,
        rut,
        domicilio,
        ciudad
    FROM Cliente
    WHERE idCliente = @idCliente;
END;
-- buscar algun cliente en especifico, esto puede servir para un empleado que quiera buscar un cliente en especifico
CREATE PROCEDURE spBuscarCliente(
    @rut VARCHAR(10)
)
AS 
BEGIN 
    SET NOCOUNT ON;

    SELECT 
        idCliente,
        nombre,
        domicilio,
        ciudad
    FROM Cliente
    WHERE rut = @rut
END
GO        