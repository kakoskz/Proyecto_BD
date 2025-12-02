<?php
$serverName = "LOCALHOST\SQLEXPRESS"; // Ajusta esto a tu servidor
$connectionOptions = array(
    "Database" => "SPDCertamen",
    "Uid" => "sa",
    "PWD" => "1234"
);

try {
    $conn = new PDO("sqlsrv:server=$serverName;Database={$connectionOptions['Database']}", $connectionOptions['Uid'], $connectionOptions['PWD']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
