CREATE PROCEDURE spInsertarEmpleado(
    @email VARCHAR(300),
    @psswd VARCHAR(255),
    @rol VARCHAR(250),
    @nombreCompleto VARCHAR(250) ,
    @rut VARCHAR(10) ,
    @cargo VARCHAR(50),
    @contrato VARCHAR(50) 
)
AS 
BEGIN 

    SET NOCOUNT ON;

    -- VERIFICAMOS SI ES QUE EL EMPLEADO EXISTE
    IF EXISTS(SELECT 1 FROM Empleado WHERE rut = @rut)
    BEGIN
        RAISERROR('Empleado ya esta ingresado en la empresa'. 16, 1);
        RETURN;
    END 
    --SI NO EXISTE , ESTE MISMO SERA INGRESADO A LA BASE DE DATOS 
    --ingresamos su usuario

    INSERT INTO Users(email, psswd, rol)
    VALUES (@email, @psswd, @rol)

    DECLARE @idUser INT;
    SET @idUser = SCOPE_IDENTITY();

    --ingresamos sus datos 
    INSERT INTO Empleado(idUser, nombre, rut, cargo, contrato)
    VALUES (@idUser, @nombre, @rut, @cargo, @contrato)

    PRINT('Empleado ingresado correctamente')

END;

CREATE PROCEDURE spModificarEmpleado(
    @nombre VARCHAR(250),
    @rut VARCHAR(10);
    @cargo  VARCHAR(50),
    @contrato   VARCHAR(50)
)
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @idEmpleado INT;

    SELECT @idEmpleado = idEmpleado
    FROM Empleado
    WHERE rut = @rut
    -- verificamos si existe el empleado
    IF EXISTS (SELECT 1 FROM Empleado rut = @rut);
    BEGIN
        UPDATE Cliente
        SET cargo = @cargo,
            contrato = @contrato
        WHERE idEmpleado = @idEmpleado;
    END    
END;

CREATE PROCEDURE spBuscarEmpleado(
    @rut VARCHAR(10)
)
AS 
BEGIN
    SET NOCOUNT ON;
    --VERIFICACION DE LA EXISTENCIA DE ESE EMPLEADO
    IF EXISTS(SELECT 1 FROM Empleado WHERE rut = @rut)
    BEGIN  
        SELECT 
            E.nombre,
            E.rut,
            U.email,
            E.cargo,
            E.contrato,
        FROM Empleado E
        INNER JOIN Users U
        ON E.idUser = U.idUser 
        WHERE rut = @rut;
    END
    ELSE
    BEGIN
        SELECT 'Empleado no encontrado, ingrese nuevamente' AS Mensaje
    END
END;            

CREATE PROCEDURE spBorrarEmpleado(
    @rut VARCHAR(10)
)
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @idEmpleado INT;
    DECLARE @idUser INT;


    SELECT 
        @idEmpleado = idEmpleado
        @idUser = idUser
    FROM Empleado 
    WHERE rut = @rut;
    
    IF @idEmpleado NULL
    BEGIN 
        RAISERROR('El empleado no existe', 16, 1);
        RETURN;        
    