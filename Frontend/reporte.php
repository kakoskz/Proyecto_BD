<?php
session_start();
require_once '../backend/db.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$ventas = [];
$totalGeneral = 0;

$fechaInicio = date('Y-m-01');
$fechaFin = date('Y-m-d');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fechaInicio = $_POST['fecha_inicio'];
    $fechaFin = $_POST['fecha_fin'];

    try {
        $sql = "SELECT * FROM fn_ReporteVentasPorFecha(?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fechaInicio, $fechaFin]);
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background-color: #f4f6f9; }
        .container { max-width: 900px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; text-align: center; }
        
        .filters { display: flex; gap: 20px; justify-content: center; margin-bottom: 20px; background: #ecf0f1; padding: 15px; border-radius: 5px; }
        .filters label { font-weight: bold; margin-right: 5px; }
        .filters input { padding: 8px; border: 1px solid #bdc3c7; border-radius: 4px; }
        button { background-color: #3498db; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #2980b9; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #2c3e50; color: white; }
        tr:hover { background-color: #f1f1f1; }
        
        .total-row { background-color: #e8f6f3; font-weight: bold; font-size: 1.1em; }
        .back-link { display: inline-block; margin-bottom: 15px; text-decoration: none; color: #7f8c8d; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <a href="panel.php" class="back-link">⬅ Volver al Panel</a>
    <h1>📊 Reporte de Ventas</h1>

    <form method="POST" class="filters">
        <div>
            <label>Desde:</label>
            <input type="date" name="fecha_inicio" value="<?php echo $fechaInicio; ?>" required>
        </div>
        <div>
            <label>Hasta:</label>
            <input type="date" name="fecha_fin" value="<?php echo $fechaFin; ?>" required>
        </div>
        <button type="submit">Generar Reporte</button>
    </form>

    <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Cant.</th>
                    <th>Precio Unit.</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($ventas) > 0): ?>
                    <?php foreach ($ventas as $row): ?>
                        <?php $totalGeneral += $row['TotalVenta']; ?>
                        <tr>
                            <td><?php echo $row['FechaVenta']; ?></td>
                            <td><?php echo htmlspecialchars($row['Producto']); ?></td>
                            <td><?php echo htmlspecialchars($row['Categoria']); ?></td>
                            <td><?php echo $row['Cantidad']; ?></td>
                            <td>$<?php echo number_format($row['PrecioUnitario'], 0); ?></td>
                            <td>$<?php echo number_format($row['TotalVenta'], 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <tr class="total-row">
                        <td colspan="5" style="text-align: right;">TOTAL INGRESOS:</td>
                        <td>$<?php echo number_format($totalGeneral, 0); ?></td>
                    </tr>

                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">No hay ventas en este rango de fechas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

</body>
</html>