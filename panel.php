<?php
session_start();

// Si no existe la variable de sesión 'user_id', lo mandamos fuera
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<body>
    <h1>Bienvenido, <?php echo $_SESSION['username']; ?></h1>
    <p>Estás en una zona segura.</p>
    <a href="logout.php">Cerrar Sesión</a>
</body>
</html>