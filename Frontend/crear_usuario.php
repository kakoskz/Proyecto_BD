<?php
require_once '../backend/db.php'; 
$email = "admin@prueba.com";   
$passwordRaw = "12345";        
$rol = "Administrador";        
$fechaActual = date('Y-m-d H:i:s');

$hash = password_hash($passwordRaw, PASSWORD_DEFAULT);

try {

    $sql = "INSERT INTO Users (email, psswd, rol, since) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if($stmt->execute([$email, $hash, $rol, $fechaActual])) {
        echo "Usuario creado con éxito.<br>";
        echo "Email: " . $email . "<br>";
        echo "Rol: " . $rol . "<br>";
        echo "Hash guardado: " . $hash;
    } else {
        echo "Error al crear usuario.";
    }

} catch (PDOException $e) {
    echo "Error de base de datos: " . $e->getMessage();
}
?>