<?php
session_start();
require_once 'db.php'; // Traemos la conexión

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['username'];
    $password = $_POST['password'];

    if (!empty($usuario) && !empty($password)) {
        try {
            // 1. Preparamos la consulta (La ? es un marcador de posición seguro)
            $sql = "SELECT ID, NombreUsuario, PasswordHash FROM Usuarios WHERE NombreUsuario = ?";
            $stmt = $conn->prepare($sql);
            
            // 2. Ejecutamos pasando el dato
            $stmt->execute([$usuario]);
            
            // 3. Obtenemos el resultado
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 4. Verificamos si existe el usuario y si la contraseña coincide
            if ($user && password_verify($password, $user['PasswordHash'])) {
                // ¡LOGIN CORRECTO!
                $_SESSION['user_id'] = $user['ID'];
                $_SESSION['username'] = $user['NombreUsuario'];
                header("Location: panel.php"); // Redirigir al panel
                exit;
            } else {
                $mensaje = "Usuario o contraseña incorrectos.";
            }
        } catch (PDOException $e) {
            $mensaje = "Error en el sistema: " . $e->getMessage();
        }
    } else {
        $mensaje = "Por favor completa ambos campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login SQL Server</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; padding-top: 50px; }
        form { border: 1px solid #ccc; padding: 20px; border-radius: 5px; background: #f9f9f9; }
        input { display: block; margin-bottom: 10px; width: 100%; padding: 8px; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
        .error { color: red; font-size: 0.9em; }
    </style>
</head>
<body>

    <form method="POST" action="">
        <h2>Iniciar Sesión</h2>
        <?php if($mensaje): ?>
            <p class="error"><?php echo $mensaje; ?></p>
        <?php endif; ?>

        <label>Usuario:</label>
        <input type="text" name="username" required>

        <label>Contraseña:</label>
        <input type="password" name="password" required>

        <button type="submit">Entrar</button>
    </form>

</body>
</html>