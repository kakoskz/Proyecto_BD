<?php
session_start();
require_once '../backend/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$mensaje = "";
$tipoMensaje = "";
$busqueda = isset($_POST['busqueda']) ? $_POST['busqueda'] : "";
$productoEditar = null;
$modoEdicion = false;


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    try {
        $nombre = $_POST['nombre'];
        $idCategoria = $_POST['categoria'];
        $stock = $_POST['stock'];
        $precio = $_POST['precio'];
        $desc = $_POST['descripcion'];

        if ($_POST['action'] == 'crear') {
            $sql = "EXEC spIngresarProducto ?, ?, ?, ?, ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$idCategoria, $nombre, $stock, $precio, $desc]);
            $mensaje = "✅ Producto creado correctamente.";
            $tipoMensaje = "success";

        } elseif ($_POST['action'] == 'editar') {
            $idProducto = $_POST['idProducto']; 
            $sql = "EXEC spModificarProducto ?, ?, ?, ?, ?, ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$idProducto, $idCategoria, $nombre, $stock, $precio, $desc]);
            $mensaje = "🔄 Producto actualizado correctamente.";
            $tipoMensaje = "success";
        }

    } catch (PDOException $e) {
        $mensaje = "Error: " . $e->getMessage();
        $errorInfo = $stmt->errorInfo();
        if(isset($errorInfo[2])) { $mensaje = $errorInfo[2]; }
        $tipoMensaje = "error";
    }
}


if (isset($_GET['borrar'])) {
    try {
        $sql = "EXEC spEliminarProducto ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$_GET['borrar']]);
        header("Location: producto.php?msg=borrado"); 
        exit;
    } catch (PDOException $e) {
        $mensaje = "⚠️ No se pudo eliminar: " . $e->getMessage();
        if(strpos($e->getMessage(), 'ventas') !== false){
             $mensaje = "⚠️ No puedes eliminar este producto porque tiene ventas asociadas.";
        }
        $tipoMensaje = "error";
    }
}

// SI DAMOS CLIC EN EDITAR
if (isset($_GET['editar'])) {
    try {
        $modoEdicion = true;
        $sql = "EXEC spObtenerProducto ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$_GET['editar']]);
        $productoEditar = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$productoEditar) { 
            $modoEdicion = false; $mensaje = "Producto no encontrado"; 
        }
    } catch (Exception $e) {
        $mensaje = "Error al cargar producto: " . $e->getMessage();
    }
}


// Categorías
$stmtCat = $conn->prepare("EXEC spListarCategorias");
$stmtCat->execute();
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

// Productos (Buscador)
$stmtProd = $conn->prepare("EXEC spBuscarProducto ?");
$stmtProd->execute([$busqueda]); 
$productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Productos</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background-color: #f0f2f5; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .msg { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .form-box { background: #fafafa; padding: 20px; border: 1px solid #eee; border-radius: 8px; border-left: 5px solid <?php echo $modoEdicion ? '#007bff' : '#28a745'; ?>; }
        .form-row { display: flex; gap: 10px; margin-bottom: 10px; }
        input, select, textarea { padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 100%; box-sizing: border-box;}
        
        button { padding: 10px 20px; cursor: pointer; border: none; border-radius: 4px; color: white; font-weight: bold; }
        .btn-green { background-color: #28a745; }
        .btn-blue { background-color: #007bff; }
        .btn-cancel { background-color: #6c757d; text-decoration: none; padding: 10px 20px; border-radius: 4px; display:inline-block;}
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #343a40; color: white; }
        
        .action-link { text-decoration: none; font-weight: bold; margin-right: 10px; }
        .edit { color: #007bff; }
        .delete { color: #dc3545; }
    </style>
</head>
<body>

<div class="container">
    <a href="panel.php" style="text-decoration:none;">⬅ Volver al Panel</a>
    <h1>Gestión de Productos</h1>

    <?php if(!empty($mensaje)): ?>
        <div class="msg <?php echo $tipoMensaje; ?>"> <?php echo $mensaje; ?> </div>
    <?php endif; ?>

    <div class="form-box">
        <h3><?php echo $modoEdicion ? '✏️ Editar Producto' : '➕ Nuevo Producto'; ?></h3>
        
        <form method="POST" action="producto.php">
            <input type="hidden" name="action" value="<?php echo $modoEdicion ? 'editar' : 'crear'; ?>">
            
            <?php if($modoEdicion): ?>
                <input type="hidden" name="idProducto" value="<?php echo $productoEditar['idProducto']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <input type="text" name="nombre" placeholder="Nombre del producto" required 
                       value="<?php echo $modoEdicion ? htmlspecialchars($productoEditar['nombre']) : ''; ?>">
                
                <select name="categoria" required>
                    <option value="">-- Selecciona Categoría --</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['idCategoria']; ?>"
                            <?php if($modoEdicion && $cat['idCategoria'] == $productoEditar['idCategoria']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cat['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <input type="number" name="stock" placeholder="Stock" required
                       value="<?php echo $modoEdicion ? $productoEditar['stock'] : ''; ?>">
                
                <input type="number" name="precio" placeholder="Precio Unitario" required
                       value="<?php echo $modoEdicion ? $productoEditar['precio_unitario'] : ''; ?>">
            </div>

            <textarea name="descripcion" placeholder="Descripción (Opcional)" rows="2"><?php echo $modoEdicion ? htmlspecialchars($productoEditar['descripcion']) : ''; ?></textarea>
            
            <br><br>
            
            <?php if($modoEdicion): ?>
                <button type="submit" class="btn-blue">Actualizar Producto</button>
                <a href="producto.php" class="btn-cancel">Cancelar Edición</a>
            <?php else: ?>
                <button type="submit" class="btn-green">Guardar Producto</button>
            <?php endif; ?>
        </form>
    </div>

    <br><hr><br>

    <form method="POST" style="display:flex; gap:10px; max-width: 400px;">
        <input type="text" name="busqueda" placeholder="Buscar..." value="<?php echo htmlspecialchars($busqueda); ?>">
        <button type="submit" class="btn-blue">🔍</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Categoría</th> <th>Stock</th>
                <th>Precio</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $p): ?>
                <tr>
                    <td><?php echo $p['idProducto']; ?></td>
                    <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($p['Categoria']); ?></td>
                    <td><?php echo $p['stock']; ?></td>
                    <td>$<?php echo number_format($p['precio_unitario'], 0); ?></td>
                    <td>
                        <a href="producto.php?editar=<?php echo $p['idProducto']; ?>" class="action-link edit">Editar</a>
                        
                        <a href="producto.php?borrar=<?php echo $p['idProducto']; ?>" 
                           class="action-link delete"
                           onclick="return confirm('¿Seguro?');">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>