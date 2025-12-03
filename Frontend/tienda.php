<?php
session_start();
require_once '../backend/db.php';

// ===============================
// 1. VALIDAR SESIÓN
// ===============================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// OJO: necesitas guardar idEmpleado en la sesión cuando el empleado inicia sesión
// Para pruebas puedes descomentar esto:
// if (!isset($_SESSION['idEmpleado'])) { $_SESSION['idEmpleado'] = 1; }

if (!isset($_SESSION['idEmpleado'])) {
    // Si no hay idEmpleado, redirige o maneja el error como quieras
    // Por ahora:
    $_SESSION['idEmpleado'] = 1; // quita esto en producción y hazlo bien desde el login
}

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$carrito = &$_SESSION['carrito'];
$mensaje = "";
$tipoMensaje = "";

// ===============================
// 2. ACCIONES: AGREGAR / QUITAR / VACIAR / FINALIZAR
// ===============================

// Agregar producto al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $idProducto = (int) $_POST['idProducto'];
    $nombre     = $_POST['nombre'];
    $precio     = (int) $_POST['precio'];
    $cantidad   = isset($_POST['cantidad']) ? (int) $_POST['cantidad'] : 1;

    if ($cantidad < 1) $cantidad = 1;

    if (isset($carrito[$idProducto])) {
        $carrito[$idProducto]['cantidad'] += $cantidad;
    } else {
        $carrito[$idProducto] = [
            'idProducto' => $idProducto,
            'nombre'     => $nombre,
            'precio'     => $precio,
            'cantidad'   => $cantidad
        ];
    }

    $mensaje = "Producto agregado al carrito.";
    $tipoMensaje = "success";
}

// Eliminar producto del carrito
if (isset($_GET['remove'])) {
    $idRemove = (int) $_GET['remove'];
    if (isset($carrito[$idRemove])) {
        unset($carrito[$idRemove]);
        $mensaje = "Producto eliminado del carrito.";
        $tipoMensaje = "success";
    }
}

// Vaciar carrito
if (isset($_GET['vaciar']) && $_GET['vaciar'] == 1) {
    $carrito = [];
    $mensaje = "Carrito vaciado.";
    $tipoMensaje = "success";
}

// Finalizar venta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'finalizar') {
    try {
        if (empty($carrito)) {
            throw new Exception("El carrito está vacío, no se puede procesar la venta.");
        }

        $tipoComprador = $_POST['tipoComprador'] ?? 'invitado';
        $idCliente     = null;

        if ($tipoComprador === 'invitado') {
            // Debe existir un cliente "Invitado" con este id
            $idCliente = 1;
        } else {
            if (empty($_POST['idCliente'])) {
                throw new Exception("Debes indicar un ID de cliente registrado.");
            }
            $idCliente = (int) $_POST['idCliente'];
        }

        $idEmpleado = (int) $_SESSION['idEmpleado'];
        $sucursal   = "Sucursal Central"; // hazlo dinámico si quieres

        $conn->beginTransaction();

        // Cabecera
        $sql = "EXEC spInsertarTransaccion ?, ?, ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$idCliente, $idEmpleado, $sucursal]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !isset($row['idTransaccion'])) {
            throw new Exception("No se pudo obtener el idTransaccion.");
        }

        $idTransaccion = (int) $row['idTransaccion'];

        // Detalles
        foreach ($carrito as $item) {
            $idProducto     = $item['idProducto'];
            $cantidad       = $item['cantidad'];
            $precioUnitario = $item['precio'];
            $descuento      = 0;

            $sqlDet = "EXEC spInsertarDetalleVenta ?, ?, ?, ?, ?";
            $stmtDet = $conn->prepare($sqlDet);
            $stmtDet->execute([
                $idTransaccion,
                $idProducto,
                $cantidad,
                $descuento,
                $precioUnitario
            ]);
        }

        $conn->commit();
        $carrito = [];

        $mensaje = "Venta realizada correctamente. ID Transacción: " . $idTransaccion;
        $tipoMensaje = "success";

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $mensaje = "Error al procesar la venta: " . $e->getMessage();
        $tipoMensaje = "error";
    }
}

// ===============================
// 3. LISTAR PRODUCTOS
// ===============================
try {
    $sqlProd = "SELECT idProducto, nombre, descProducto, stock, precio FROM Producto WHERE stock > 0";
    $stmtProd = $conn->prepare($sqlProd);
    $stmtProd->execute();
    $productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $productos = [];
    $mensaje = "Error al obtener productos: " . $e->getMessage();
    $tipoMensaje = "error";
}

// ===============================
// 4. TOTAL CARRITO
// ===============================
$totalCarrito = 0;
$totalItems   = 0;
foreach ($carrito as $item) {
    $totalCarrito += $item['precio'] * $item['cantidad'];
    $totalItems   += $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tienda - Carrito Slider</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background:#f0f2f5; margin:0; }
        .container { max-width: 1200px; margin: 30px auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1); position:relative; }
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .cart-toggle {
            border:none;
            background:#007bff;
            color:#fff;
            border-radius:20px;
            padding:8px 15px;
            cursor:pointer;
            font-weight:bold;
        }
        .msg { padding:10px 15px; border-radius:5px; margin-bottom:15px; }
        .success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

        h2 { margin-top:20px; }

        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { padding:10px; border-bottom:1px solid #ddd; text-align:left; }
        th { background:#f8f9fa; }

        input[type="number"] {
            padding:5px;
            border-radius:4px;
            border:1px solid #ccc;
            width:70px;
        }

        button {
            padding:7px 12px;
            border:none;
            border-radius:4px;
            cursor:pointer;
            font-weight:bold;
            color:#fff;
        }
        .btn-add { background:#28a745; }
        .btn-remove { background:#dc3545; }
        .btn-finish { background:#17a2b8; margin-top:10px; width:100%; }

        .link-remove {
            color:#dc3545;
            text-decoration:none;
            font-size:0.9em;
        }

        .flex { display:flex; gap:10px; align-items:center; }
        .small { font-size:0.9em; color:#555; }

        /* ========== SLIDER CARRITO ========== */
        .cart-panel {
            position:fixed;
            top:0;
            right:-420px; /* oculto */
            width:380px;
            height:100vh;
            background:#ffffff;
            box-shadow:-2px 0 8px rgba(0,0,0,0.15);
            transition:right 0.3s ease-in-out;
            padding:20px;
            z-index:999;
            display:flex;
            flex-direction:column;
        }
        .cart-panel.open {
            right:0;
        }
        .cart-header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:10px;
        }
        .cart-header h2 { margin:0; }
        .cart-close {
            background:none;
            border:none;
            font-size:1.3rem;
            cursor:pointer;
        }
        .cart-body {
            flex:1;
            overflow-y:auto;
            margin-top:10px;
        }
        .cart-footer {
            border-top:1px solid #ddd;
            padding-top:10px;
        }
        .cart-table th, .cart-table td {
            font-size:0.9em;
            padding:6px;
        }
        .cart-total-row td {
            font-weight:bold;
        }

        .cart-badge {
            background:#ffc107;
            color:#000;
            border-radius:50%;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:22px;
            height:22px;
            font-size:0.8em;
            margin-left:5px;
        }

        @media (max-width: 768px) {
            .container { margin:10px; }
            .cart-panel { width:100%; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <h1>🛍️ Tienda</h1>
        <button class="cart-toggle" onclick="toggleCart()">
            🛒 Carrito
            <span class="cart-badge"><?php echo $totalItems; ?></span>
        </button>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="msg <?php echo $tipoMensaje; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <!-- LISTADO DE PRODUCTOS -->
    <h2>Productos disponibles</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Producto</th>
                <th>Descripción</th>
                <th>Stock</th>
                <th>Precio</th>
                <th>Agregar</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($productos)): ?>
            <?php foreach ($productos as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['idProducto']); ?></td>
                    <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($p['descProducto']); ?></td>
                    <td><?php echo htmlspecialchars($p['stock']); ?></td>
                    <td><?php echo htmlspecialchars($p['precio']); ?></td>
                    <td>
                        <form method="POST" class="flex">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="idProducto" value="<?php echo $p['idProducto']; ?>">
                            <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($p['nombre']); ?>">
                            <input type="hidden" name="precio" value="<?php echo $p['precio']; ?>">
                            <input type="number" name="cantidad" value="1" min="1">
                            <button type="submit" class="btn-add">Agregar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">No hay productos disponibles.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ========== PANEL LATERAL DEL CARRITO ========== -->
<div id="cartPanel" class="cart-panel">
    <div class="cart-header">
        <h2>🛒 Carrito</h2>
        <button class="cart-close" onclick="toggleCart()">✕</button>
    </div>

    <div class="cart-body">
        <?php if (!empty($carrito)): ?>
            <table class="cart-table" width="100%">
                <thead>
                    <tr>
                        <th>Prod.</th>
                        <th>Cant.</th>
                        <th>Precio</th>
                        <th>Subt.</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($carrito as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($item['cantidad']); ?></td>
                        <td><?php echo htmlspecialchars($item['precio']); ?></td>
                        <td><?php echo $item['precio'] * $item['cantidad']; ?></td>
                        <td>
                            <a class="link-remove"
                               href="venta.php?remove=<?php echo $item['idProducto']; ?>"
                               onclick="return confirm('¿Quitar este producto del carrito?');">
                               X
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="cart-total-row">
                    <td colspan="3" style="text-align:right;">Total:</td>
                    <td colspan="2"><?php echo $totalCarrito; ?></td>
                </tr>
                </tbody>
            </table>

            <p>
                <a href="venta.php?vaciar=1" class="link-remove"
                   onclick="return confirm('¿Vaciar todo el carrito?');">
                    Vaciar carrito
                </a>
            </p>

            <div class="cart-footer">
                <h3>Continuar con la venta</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="finalizar">

                    <div class="flex">
                        <label>
                            <input type="radio" name="tipoComprador" value="invitado" checked>
                            Invitado
                        </label>
                        <label>
                            <input type="radio" name="tipoComprador" value="cliente">
                            Cliente registrado
                        </label>
                    </div>

                    <div class="small" style="margin:5px 0 10px 0;">
                        Si eliges "Cliente registrado", indica el ID del cliente.
                    </div>

                    <div style="margin-bottom:10px;">
                        <label>ID Cliente:</label>
                        <input type="number" name="idCliente" placeholder="Ej: 5" style="width:100%;">
                    </div>

                    <button type="submit" class="btn-finish">
                        Continuar con la venta
                    </button>
                </form>
            </div>
        <?php else: ?>
            <p>El carrito está vacío.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleCart() {
    const panel = document.getElementById('cartPanel');
    panel.classList.toggle('open');
}
</script>

</body>
</html>
