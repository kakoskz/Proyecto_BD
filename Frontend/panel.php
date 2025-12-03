<?php
session_start();

// 1. Seguridad: Si no hay sesión, patada al login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Recuperamos datos de la sesión (guardados en login.php)
$email = $_SESSION['email'];
$rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : 'Empleado';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - Sistema ERP</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; background-color: #f0f2f5; }
        
        /* Header Superior */
        header { background-color: #343a40; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .user-info span { font-weight: bold; color: #ffc107; }
        .btn-logout { background-color: #dc3545; color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; font-size: 0.9em; transition: 0.3s; }
        .btn-logout:hover { background-color: #c82333; }

        /* Contenedor Principal */
        .container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        h2 { color: #333; border-bottom: 2px solid #ddd; padding-bottom: 10px; }

        /* Grid de Tarjetas */
        .grid-menu { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
        
        .card { background: white; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; text-decoration: none; color: inherit; display: block; border-top: 5px solid #ccc; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
        
        .card h3 { margin: 15px 0 10px 0; color: #2c3e50; }
        .card p { color: #7f8c8d; font-size: 0.9em; }
        .icon { font-size: 3em; margin-bottom: 10px; display: block; }

        /* Colores específicos por módulo */
        .card.productos { border-top-color: #007bff; }
        .card.empleados { border-top-color: #6f42c1; }
        .card.reportes { border-top-color: #28a745; }
        .card.ventas { border-top-color: #fd7e14; }

        /* Sección Admin */
        .admin-section { margin-top: 40px; background-color: #fff3cd; padding: 20px; border-radius: 8px; border: 1px solid #ffeeba; }
        .admin-badge { background: #dc3545; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.8em; vertical-align: middle; margin-left: 10px; }
    </style>
</head>
<body>

    <header>
        <div>
            Sistema de Gestión
        </div>
        <div class="user-info">
            Hola, <span><?php echo htmlspecialchars($email); ?></span> 
            (<?php echo htmlspecialchars($rol); ?>)
            <a href="logout.php" class="btn-logout" style="margin-left: 15px;">Cerrar Sesión</a>
        </div>
    </header>

    <div class="container">
        
        <h2>📦 Operaciones Diarias</h2>
        <div class="grid-menu">
            
            <a href="producto.php" class="card productos">
                <span class="icon">📦</span>
                <h3>Inventario</h3>
                <p>Gestionar productos, stock y precios.</p>
            </a>

            <a href="tienda.php" onclick="alert('Módulo de Punto de Venta en construcción')" class="card ventas">
                <span class="icon">🛒</span>
                <h3>Nueva Venta</h3>
                <p>Registrar ventas y facturación.</p>
            </a>
        </div>

        <?php if ($rol === 'Administrador'): ?>
            
            <div class="admin-section">
                <h2>🛠️ Administración <span class="admin-badge">Solo Admin</span></h2>
                
                <div class="grid-menu">
                    <a href="empleado.php" class="card empleados">
                        <span class="icon">👔</span>
                        <h3>Empleados</h3>
                        <p>Contrataciones, despidos y roles.</p>
                    </a>

                    <a href="reporte.php" class="card reportes">
                        <span class="icon">📊</span>
                        <h3>Reportes</h3>
                        <p>Ver ventas por fecha y totales.</p>
                    </a>
                </div>
            </div>

        <?php endif; ?>

    </div>

</body>
</html>