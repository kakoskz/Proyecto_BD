<?php
require_once 'db.php';

$nuevoUsuario = "admin";
$nuevaPass = "12345"; // La contraseña que quieras
// Encriptamos la contraseña
$hash = password_hash($nuevaPass, PASSWORD_DEFAULT);

$sql = "INSERT INTO Usuarios (NombreUsuario, PasswordHash) VALUES (?, ?)";
$stmt = $conn->prepare($sql);

if($stmt->execute([$nuevoUsuario, $hash])) {
    echo "Usuario creado con éxito. Password encriptada: " . $hash;
} else {
    echo "Error al crear usuario.";
}
?>