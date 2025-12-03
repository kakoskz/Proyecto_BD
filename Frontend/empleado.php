<?php
session_start();
require_once '../backend/db.php';

// 1. VALIDAR SESIÓN Y ROL
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
    header("Location: panel.php?error=permiso");
    exit;
}

$mensaje = "";
$tipoMensaje = "";
$busqueda = isset($_POST['busqueda']) ? trim($_POST['busqueda']) : "";
$empleadoEditar = null;
$modoEdicion = false;

// 2. MANEJO DE FORMULARIO (CREAR / EDITAR)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    
    $accion = $_POST['action'];

    // Recogemos datos
    $email      = isset($_POST['email']) ? trim($_POST['email']) : "";
    $password   = isset($_POST['password']) ? $_POST['password'] : "";
    $rolUsuario = isset($_POST['rolUsuario']) ? $_POST['rolUsuario'] : "Empleado";
    $nombre     = isset($_POST['nombre']) ? trim($_POST['nombre']) : "";
    $rut        = isset($_POST['rut']) ? trim($_POST['rut']) : "";
    $cargo      = isset($_POST['cargo']) ? trim($_POST['cargo']) : "";
    $contrato   = isset($_POST['contrato']) ? trim($_POST['contrato']) : "";

    try {
        if ($accion === 'crear') {
            // === CREAR (Usa spInsertarEmpleado) ===
            // Orden SP: @email, @psswd, @rol, @nombreCompleto, @rut, @cargo, @contrato
            
            if (empty($email) || empty($password) || empty($rut)) {
                throw new Exception("Faltan datos obligatorios.");
            }

            $passHash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "EXEC spInsertarEmpleado ?, ?, ?, ?, ?, ?, ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$email, $passHash, $rolUsuario, $nombre, $rut, $cargo, $contrato]);

            $mensaje = "✅ Empleado creado correctamente.";
            $tipoMensaje = "success";

        } elseif ($accion === 'editar') {
            // === EDITAR (Usa spModificarEmpleado) ===
            // Orden SP: @nombre, @rut, @cargo, @contrato
            // NOTA: Tu SP usa el RUT para encontrar al empleado.
            
            $sql = "EXEC spModificarEmpleado ?, ?, ?, ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nombre, $rut, $cargo, $contrato]);

            $mensaje = "🔄 Datos actualizados (Cargo/Contrato).";
            $tipoMensaje = "success";
            
            // Limpiamos modo edición para volver a crear
            $modoEdicion = false;
        }

    } catch (PDOException $e) {
        $mensaje = "❌ Error BD: " . $e->getMessage();
        // Limpiar mensaje de SQL Server si viene sucio
        $errInfo = $stmt->errorInfo();
        if(isset($errInfo[2])) $mensaje = "❌ " . $errInfo[2];
        $tipoMensaje = "error";
    } catch (Exception $e) {
        $mensaje = "❌ " . $e->getMessage();
        $tipoMensaje = "error";
    }
}

// 3. ELIMINAR EMPLEADO (Usa spBorrarEmpleado que pide @rut)
if (isset($_GET['borrar_rut'])) {
    try {
        $rutBorrar = $_GET['borrar_rut']; // Recibimos RUT, no ID

        $sql = "EXEC spBorrarEmpleado ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$rutBorrar]);

        header("Location: empleado.php?msg=borrado");
        exit;
    } catch (PDOException $e) {
        $mensaje = "❌ Error al eliminar: " . $e->getMessage();
        $tipoMensaje = "error";
    }
}

// 4. CARGAR DATOS PARA EDICIÓN (Usa spObtenerEmpleado que pide @idEmpleado)
if (isset($_GET['editar_id'])) {
    try {
        $modoEdicion = true;
        $idEditar = $_GET['editar_id'];

        $sql = "EXEC spObtenerEmpleado ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$idEditar]);
        $empleadoEditar = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$empleadoEditar) {
            $modoEdicion = false;
            $mensaje = "Empleado no encontrado.";
        }
    } catch (Exception $e) {
        $mensaje = "Error al cargar: " . $e->getMessage();
    }
}

// 5. LISTAR O BUSCAR
// Si hay búsqueda, usamos tu spBuscarEmpleado (Busca por RUT)
// Si no, usamos spListarEmpleados (Trae todos)
$empleados = [];
try {
    if (!empty($busqueda)) {
        // Tu SP busca por RUT exacto
        $sql = "EXEC spBuscarEmpleado ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$busqueda]);
        // spBuscarEmpleado devuelve campos sin ID, así que la edición desde búsqueda
        // puede ser limitada si no ajustamos el SP.
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Listado General
        $sql = "EXEC spListarEmpleados"; 
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $mensaje = "Error al cargar lista.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Empleados</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background-color: #f4f6f9; }
        .container { max-width: 1100px; margin: auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .msg { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Formulario */
        .form-box { background: #fafafa; padding: 20px; border-radius: 8px; border-left: 5px solid <?php echo $modoEdicion ? '#007bff' : '#28a745'; ?>; margin-bottom: 30px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        
        /* Botones */
        .btn { padding: 10px 20px; border: none; border-radius: 4px; color: white; cursor: pointer; font-weight: bold; }
        .btn-green { background: #28a745; }
        .btn-blue { background: #007bff; }
        .btn-cancel { background: #6c757d; text-decoration: none; padding: 10px 20px; border-radius: 4px; color: white; font-size: 0.9em;}

        /* Tabla */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #343a40; color: white; }
        .actions a { margin-right: 10px; text-decoration: none; font-weight: bold; }
        .edit { color: #007bff; }
        .delete { color: #dc3545; }
    </style>
</head>
<body>

<div class="container">
    <a href="panel.php" style="text-decoration:none; color: #555;">⬅ Volver al Panel</a>
    <h1>Gestión de Empleados</h1>

    <?php if ($mensaje): ?>
        <div class="msg <?php echo $tipoMensaje; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <div class="form-box">
        <h3><?php echo $modoEdicion ? '✏️ Editar Empleado' : '➕ Nuevo Empleado'; ?></h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="<?php echo $modoEdicion ? 'editar' : 'crear'; ?>">
            
            <div class="form-row">
                <input type="email" name="email" placeholder="Email (Usuario)" required 
                       value="<?php echo $modoEdicion ? htmlspecialchars($empleadoEditar['email']) : ''; ?>"
                       <?php echo $modoEdicion ? 'readonly style="background:#e9ecef;"' : ''; ?>>
                
                <select name="rolUsuario" <?php echo $modoEdicion ? 'disabled' : ''; ?>>
                    <option value="Empleado">Rol: Empleado</option>
                    <option value="Administrador" <?php if($modoEdicion && $empleadoEditar['rol']=='Administrador') echo 'selected'; ?>>Rol: Administrador</option>
                </select>
            </div>

            <?php if (!$modoEdicion): ?>
                <div class="form-row">
                    <input type="password" name="password" placeholder="Contraseña" required>
                </div>
            <?php endif; ?>

            <div class="form-row">
                <input type="text" name="nombre" placeholder="Nombre Completo" required
                       value="<?php echo $modoEdicion ? htmlspecialchars($empleadoEditar['nombre']) : ''; ?>">
                
                <input type="text" name="rut" placeholder="RUT (ej: 12345678-9)" required
                       value="<?php echo $modoEdicion ? htmlspecialchars($empleadoEditar['rut']) : ''; ?>"
                       <?php echo $modoEdicion ? 'readonly style="background:#e9ecef;"' : ''; ?>>
            </div>

            <div class="form-row">
                <input type="text" name="cargo" placeholder="Cargo" required
                       value="<?php echo $modoEdicion ? htmlspecialchars($empleadoEditar['cargo']) : ''; ?>">
                
                <input type="text" name="contrato" placeholder="Tipo de Contrato" required
                       value="<?php echo $modoEdicion ? htmlspecialchars($empleadoEditar['contrato']) : ''; ?>">
            </div>

            <button type="submit" class="btn <?php echo $modoEdicion ? 'btn-blue' : 'btn-green'; ?>">
                <?php echo $modoEdicion ? 'Actualizar Datos' : 'Guardar Empleado'; ?>
            </button>
            
            <?php if ($modoEdicion): ?>
                <a href="empleado.php" class="btn-cancel">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>

    <hr>

    <form method="POST" style="display:flex; gap:10px; max-width: 400px; margin-bottom: 20px;">
        <input type="text" name="busqueda" placeholder="Buscar por RUT exacto..." value="<?php echo htmlspecialchars($busqueda); ?>">
        <button type="submit" class="btn btn-blue">🔍 Buscar</button>
        <?php if($busqueda): ?><a href="empleado.php" style="align-self:center;">Limpiar</a><?php endif; ?>
    </form>

    <table>
        <thead>
            <tr>
                <th>RUT</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Cargo</th>
                <th>Contrato</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($empleados) > 0): ?>
                <?php foreach ($empleados as $emp): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($emp['rut']); ?></td>
                        <td><?php echo htmlspecialchars(isset($emp['nombreCompleto']) ? $emp['nombreCompleto'] : $emp['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($emp['email']); ?></td>
                        <td><?php echo htmlspecialchars($emp['cargo']); ?></td>
                        <td><?php echo htmlspecialchars($emp['contrato']); ?></td>
                        <td class="actions">
                            <?php if(isset($emp['idEmpleado'])): ?>
                                <a href="empleado.php?editar_id=<?php echo $emp['idEmpleado']; ?>" class="edit">Editar</a>
                            <?php endif; ?>
                            
                            <a href="empleado.php?borrar_rut=<?php echo $emp['rut']; ?>" 
                               class="delete"
                               onclick="return confirm('¿Seguro que deseas eliminar al empleado con RUT <?php echo $emp['rut']; ?>?');">
                               Eliminar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">No se encontraron empleados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>