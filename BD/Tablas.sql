CREATE TABLE Users(
    idUser INT IDENTITY(1,1) PRIMARY KEY, -- se da automaticamente el id con un incremento de 1 en 1 por usuario.
    email VARCHAR(300) NOT NULL,
    psswd VARCHAR(255) NOT NULL,
    since DATETIME DEFAULT GETDATE() --el dia en que se creo el usuario
    rol VARCHAR(250) NOT NULL --"cliente""admin"
);
--cliente debe de tener un fk desde users
CREATE TABLE Client(
    idCliente INT IDENTITY(1,1) PRIMARY KEY, --id asignado automaticamente
    idUser INT NOT NULL,
    nombre VARCHAR(250) NOT NULL,
    alias VARCHAR(50), -- mas facil poder filtrar personas
    domicilio VARCHAR(500) NOT NULL,
    ciudad VARCHAR(100) NOT NULL,
    genero VARCHAR(50),
    codigoPostal VARCHAR(15), 
    preferencias VARCHAR(500),         --muchas cosas se pueden omitir ya que no es muy relevante pero sirve
    
    CONSTRAINT fk_users
        FOREIGN KEY (idUser) REFERENCES Users(idUser)
);
--transaccion necesita fk de cliente
--transaccion necesita fk de detalle
CREATE TABLE Transaccion(
    idTransaccion INT IDENTITY(1,1) PRIMARY KEY,
    idCliente INT NOT NULL,
    fecha DATETIME DEFAULT GETDATE(),
    sucursal VARCHAR(500) NOT NULL,
    total INT NOT NULL,

    CONSTRAINT fk_cliente
        FOREIGN KEY (idCliente) REFERENCES Client(idCliente)
);
--detalle necesita fk de producto
CREATE TABLE DetalleVenta(
    idDetalle INT IDENTITY(1,1) PRIMARY KEY,
    idTransaccion INT NOT NULL,
    idProducto INT NOT NULL,
    cantidad INT NOT NULL,
    descuento INT NOT NULL DEFAULT 0,
    precioUnitario INT NOT NULL,
    precioFinal INT NOT NULL,
    totaLinea INT NOT NULL,

    CONSTRAINT fk_detalle_venta
        FOREIGN KEY (idTransaccion) REFERENCES Transaccion(idTransaccion),

    CONSTRAINT fk_producto
        FOREIGN KEY (idProducto) REFERENCES Producto(idProducto)
);
--Producto necesita fk de categoria
CREATE TABLE Producto(
    idProducto INT IDENTITY(1,1) PRIMARY KEY,
    idCategoria INT NOT NULL,
    nombre VARCHAR(500) NOT NULL,
    stock INT NOT NULL,
    precio_unitario INT,
    descripcion VARCHAR(500),

    CONSTRAINT fk_categoria
        FOREIGN KEY (idCategoria) REFERENCES Categoria(idCategoria)
);

CREATE TABLE Categoria(
    idCategoria INT IDENTITY(1,1) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
)