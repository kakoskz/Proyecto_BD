<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];
$rol = $_SESSION['rol']; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Control</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .admin-panel { background-color: #ffcccc; padding: 15px; border: 1px solid red; margin-top: 20px;}
        .user-panel { background-color: #e6f7ff; padding: 15px; border: 1px solid blue; margin-top: 20px;}
        a { color: red; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <h1>Bienvenido, <?php echo htmlspecialchars($email); ?></h1>
    
    <p>Tu rol en el sistema es: <strong><?php echo htmlspecialchars($rol); ?></strong></p>

    <?php if ($rol == 'Administrador'): ?>
        
        <div class="admin-panel">
            <h3>🛠️ Panel de Administrador</h3>
            <p>Tienes permisos para:</p>
            <ul>
                <li><a href="">Crear nuevos usuarios</a></li>
                <li>Editar productos</li>
                <li>Ver reportes de ventas</li>
            </ul>
        </div>

    <?php else: ?>

        <div class="user-panel">
            <h3>👤 Zona de Cliente</h3>
            <p>Aquí puedes ver tus compras recientes y estado de pedidos.</p>
        </div>

    <?php endif; ?>

    <br><br>
    <hr>
    <a href="logout.php">Cerrar Sesión</a>

</body>
</html>