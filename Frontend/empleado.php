<?php
session_start();
require_once '../backend/db.php';

// 1. VALIDAR SESIÓN Y ROL ADMINISTRADOR
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
    // Solo admins pueden entrar a esta página
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

    // Datos del formulario
    $email          = isset($_POST['email']) ? trim($_POST['email']) : "";
    $passwordRaw    = isset($_POST['password']) ? $_POST['password'] : "";
    $rolUsuario     = isset($_POST['rolUsuario']) ? trim($_POST['rolUsuario']) : "Empleado";
    $nombre         = isset($_POST['nombre']) ? trim($_POST['nombre']) : "";
    $rut            = isset($_POST['rut']) ? trim($_POST['rut']) : "";
    $cargo          = isset($_POST['cargo']) ? trim($_POST['cargo']) : "";
    $contrato       = isset($_POST['contrato']) ? trim($_POST['contrato']) : "";

    try {

        if ($accion === 'crear') {
            // Validaciones simples
            if (empty($email) || empty($passwordRaw) || empty($nombre) || empty($rut)) {
                throw new Exception("Faltan datos obligatorios para crear el empleado.");
            }

            // Hashear contraseña para Users
            $passwordHash = password_hash($passwordRaw, PASSWORD_DEFAULT);

            // SP PARA INSERTAR EMPLEADO (Users + Empleado)
            // AJUSTA el nombre del SP si en tu BD se llama distinto
            $sql = "EXEC spInsertarEmpleado ?, ?, ?, ?, ?, ?, ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $email,
                $passwordHash,
                $rolUsuario,     // Ej: 'Administrador' o 'Empleado'
                $nombre,
                $rut,
                $cargo,
                $contrato
            ]);

            $mensaje = "✅ Empleado creado correctamente.";
            $tipoMensaje = "success";

        } elseif ($accion === 'editar') {

            if (!isset($_POST['idEmpleado'])) {
                throw new Exception("ID de empleado no recibido.");
            }

            $idEmpleado = (int) $_POST['idEmpleado'];

            // OJO: aquí supongo un SP para actualizar empleado.
            // Si tu SP se llama distinto, cámbialo.
            // Ejemplo de firma: spActualizarEmpleado @idEmpleado, @nombre, @rut, @cargo, @contrato, @rol
            $sql = "EXEC spModificarEmpleado ?, ?, ?, ?, ?, ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $idEmpleado,
                $nombre,
                $rut,
                $cargo,
                $contrato,
                $rolUsuario
            ]);

            $mensaje = "🔄 Empleado actualizado correctamente.";
            $tipoMensaje = "success";
        }

    } catch (PDOException $e) {
        $mensaje = "Error de BD: " . $e->getMessage();
        $tipoMensaje = "error";
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipoMensaje = "error";
    }
}

// 3. ELIMINAR EMPLEADO
if (isset($_GET['borrar'])) {
    try {
        $idBorrar = (int) $_GET['borrar'];

        // Supuesto SP: spEliminarEmpleado @idEmpleado
        $sql = "EXEC spBorrarEmpleado ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$idBorrar]);

        header("Location: empleado.php?msg=borrado");
        exit;
    } catch (PDOException $e) {
        $mensaje = "Error al eliminar empleado: " . $e->getMessage();
        $tipoMensaje = "error";
    }
}

// 4. CARGAR DATOS PARA EDICIÓN
if (isset($_GET['editar'])) {
    try {
        $modoEdicion = true;
        $idEditar = (int) $_GET['editar'];

        // Supuesto SP: spObtenerEmpleado @idEmpleado
        $sql = "EXEC spObtenerEmpleado ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$idEditar]);
        $empleadoEditar = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$empleadoEditar) {
            $modoEdicion = false;
            $mensaje = "Empleado no encontrado.";
            $tipoMensaje = "error";
        }

    } catch (Exception $e) {
        $mensaje = "Error al cargar empleado: " . $e->getMessage();
        $tipoMensaje = "error";
    }
}

// 5. LISTAR / BUSCAR EMPLEADOS
try {
    // Supuesto SP: spBuscarEmpleado @textoBusqueda (si va vacío, lista todo)
    $stmtEmp = $conn->prepare("EXEC spBuscarEmpleado ?");
    $stmtEmp->execute([$busqueda]);
    $empleados = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $empleados = [];
    $mensaje = "Error al listar empleados: " . $e->getMessage();
    $tipoMensaje = "error";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Empleados</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background-color: #f0f2f5; }
        .container { max-width: 1100px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { margin-bottom: 10px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .msg { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .form-box { background: #fafafa; padding: 20px; border-radius: 8px; border: 2px solid <?php echo $modoEdicion ? '#007bff' : '#28a745'; ?>; margin-bottom: 20px; }
        .form-box h2 { margin-top: 0; }
        .form-row { display: flex; gap: 10px; margin-bottom: 10px; }
        .form-row > div { flex: 1; }
        input, select { padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 100%; box-sizing: border-box; }

        button { padding: 10px 20px; cursor: pointer; border: none; border-radius: 4px; color: white; font-weight: bold; }
        .btn-green { background-color: #28a745; }
        .btn-blue { background-color: #007bff; }
        .btn-gray { background-color: #6c757d; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #f8f9fa; }

        .action-link { text-decoration: none; margin-right: 10px; font-size: 0.9em; }
        .action-link.edit { color: #007bff; }
        .action-link.delete { color: #dc3545; }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <h1>👔 Gestión de Empleados</h1>
        <a href="panel.php" class="action-link">⬅ Volver al panel</a>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="msg <?php echo $tipoMensaje; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <!-- FORMULARIO CREAR / EDITAR -->
    <div class="form-box">
        <h2><?php echo $modoEdicion ? "Editar Empleado" : "Crear Nuevo Empleado"; ?></h2>
        <form method="POST">
            <input type="hidden" name="action" value="<?php echo $modoEdicion ? 'editar' : 'crear'; ?>">
            <?php if ($modoEdicion && $empleadoEditar): ?>
                <input type="hidden" name="idEmpleado" value="<?php echo $empleadoEditar['idEmpleado']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div>
                    <label>Email (usuario):</label>
                    <input type="email" name="email"
                        value="<?php echo $modoEdicion && isset($empleadoEditar['email']) ? htmlspecialchars($empleadoEditar['email']) : ''; ?>"
                        <?php echo $modoEdicion ? 'readonly' : ''; ?>>
                </div>
                <div>
                    <label>Rol:</label>
                    <select name="rolUsuario">
                        <?php
                        $rolActual = $modoEdicion && isset($empleadoEditar['rol']) ? $empleadoEditar['rol'] : 'Empleado';
                        ?>
                        <option value="Empleado"      <?php echo $rolActual === 'Empleado' ? 'selected' : ''; ?>>Empleado</option>
                        <option value="Administrador" <?php echo $rolActual === 'Administrador' ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                </div>
            </div>

            <?php if (!$modoEdicion): ?>
            <div class="form-row">
                <div>
                    <label>Contraseña:</label>
                    <input type="password" name="password" placeholder="Contraseña inicial">
                </div>
            </div>
            <?php else: ?>
            <p style="font-size: 0.9em; color:#555;">
                * La contraseña no se modifica desde aquí. Si necesitas cambiarla, hazlo en el módulo correspondiente.
            </p>
            <?php endif; ?>

            <div class="form-row">
                <div>
                    <label>Nombre completo:</label>
                    <input type="text" name="nombre"
                        value="<?php echo $modoEdicion ? htmlspecialchars($empleadoEditar['nombre']) : ''; ?>">
                </div>
                <div>
                    <label>RUT:</label>
                    <input type="text" name="rut"
                        value="<?php echo $modoEdicion ? htmlspecialchars($empleadoEditar['rut']) : ''; ?>">
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label>Cargo:</label>
                    <input type="text" name="cargo"
                        value="<?php echo $modoEdicion ? htmlspecialchars($empleadoEditar['cargo']) : ''; ?>">
                </div>
                <div>
                    <label>Tipo de contrato:</label>
                    <input type="text" name="contrato"
                        value="<?php echo $modoEdicion ? htmlspecialchars($empleadoEditar['contrato']) : ''; ?>"
                        placeholder="Indefinido, Plazo fijo, etc.">
                </div>
            </div>

            <div style="margin-top: 15px;">
                <button type="submit" class="<?php echo $modoEdicion ? 'btn-blue' : 'btn-green'; ?>">
                    <?php echo $modoEdicion ? 'Guardar cambios' : 'Crear empleado'; ?>
                </button>
                <?php if ($modoEdicion): ?>
                    <a href="empleado.php" class="action-link">Cancelar edición</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- BUSCADOR -->
    <form method="POST" style="display:flex; gap:10px; max-width: 400px; margin-bottom: 10px;">
        <input type="text" name="busqueda" placeholder="Buscar por nombre, RUT, cargo..."
               value="<?php echo htmlspecialchars($busqueda); ?>">
        <button type="submit" class="btn-blue">🔍 Buscar</button>
    </form>

    <!-- TABLA LISTADO EMPLEADOS -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>RUT</th>
                <th>Cargo</th>
                <th>Contrato</th>
                <th>Rol Usuario</th>
                <th>Desde</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($empleados)): ?>
            <?php foreach ($empleados as $emp): ?>
                <tr>
                    <td><?php echo htmlspecialchars($emp['idEmpleado']); ?></td>
                    <td><?php echo htmlspecialchars($emp['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($emp['rut']); ?></td>
                    <td><?php echo htmlspecialchars($emp['cargo']); ?></td>
                    <td><?php echo htmlspecialchars($emp['contrato']); ?></td>
                    <td><?php echo isset($emp['rol']) ? htmlspecialchars($emp['rol']) : ''; ?></td>
                    <td><?php echo isset($emp['desde']) ? htmlspecialchars($emp['desde']) : ''; ?></td>
                    <td>
                        <a href="empleado.php?editar=<?php echo $emp['idEmpleado']; ?>" class="action-link edit">Editar</a>
                        <a href="empleado.php?borrar=<?php echo $emp['idEmpleado']; ?>"
                           class="action-link delete"
                           onclick="return confirm('¿Seguro que quieres eliminar este empleado?');">
                           Eliminar
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8">No se encontraron empleados.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>
