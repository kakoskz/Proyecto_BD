CREATE PROCEDURE spInsertarCliente(
    @email              VARCHAR(300),
    @psswd              VARCHAR(255),
    @nombre             VARCHAR(250),
    @alias              VARCHAR(50) = NULL,
    @domicilio          VARCHAR(500),
    @ciudad             VARCHAR(100),
    @genero             VARCHAR(50)  = NULL,
    @codigoPostal       VARCHAR(15)  = NULL,
    @preferencias       VARCHAR(500) = NULL
)

    