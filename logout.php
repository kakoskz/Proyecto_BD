<?php
session_start();
session_destroy(); // Destruye la sesión
header("Location: login.php"); // Lo manda al login
exit;
?>