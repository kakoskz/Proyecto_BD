<?php
session_start();
require_once '../backend/db.php'; 

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']); 
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        try {
            $sql = "EXEC sp_ValidarLogin ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['psswd'])) {
                
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['idUser'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['rol'] = $user['rol'];
                
                header("Location: panel.php"); 
                exit;
            } else {
                $mensaje = "Correo o contraseña incorrectos.";
            }
        
        } catch (PDOException $e) {
     
            $mensaje = "Error al conectar con la base de datos.";
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
    <title>Login - Proyecto BD</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; padding-top: 50px; background-color: #f4f4f4;}
        form { border: 1px solid #ddd; padding: 30px; border-radius: 8px; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px;}
        input { display: block; margin-bottom: 15px; width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;}
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; cursor: pointer; border-radius: 4px; font-size: 16px;}
        button:hover { background: #218838; }
        .error { color: #dc3545; font-size: 0.9em; margin-bottom: 15px; text-align: center;}
        h2 { text-align: center; color: #333; }
    </style>
</head>
<body>

    <form method="POST" action="">
        <h2>Iniciar Sesión</h2>
        
        <?php if($mensaje): ?>
            <p class="error"><?php echo $mensaje; ?></p>
        <?php endif; ?>

        <label>Correo Electrónico:</label>
        <input type="email" name="email" required placeholder="ejemplo@correo.com">

        <label>Contraseña:</label>
        <input type="password" name="password" required placeholder="********">

        <button type="submit">Entrar</button>
    </form>

</body>
</html>