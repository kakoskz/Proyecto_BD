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
--primero hay que validar si es que existe el cliente
    IF EXISTS


    