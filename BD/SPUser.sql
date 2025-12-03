--aqui ponlo
CREATE PROCEDURE sp_ValidarLogin
    @EmailInput varchar(300)
AS
BEGIN

    SET NOCOUNT ON;


    SELECT idUser, email, psswd, rol 
    FROM Users 
    WHERE email = @EmailInput;
END
GO