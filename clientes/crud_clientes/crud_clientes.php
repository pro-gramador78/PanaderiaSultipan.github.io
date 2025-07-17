<?php
session_start();

// Verificar autenticación y permisos de administrador
// Solo los administradores pueden acceder a este módulo
if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../../login.php");
    exit();
}

// Configuración de base de datos
$host = "localhost";
$usuario = "root";
$password = "";
$base_datos = "sultipan";

try {
    $conexion = new mysqli($host, $usuario, $password, $base_datos);
    $conexion->set_charset("utf8");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
} catch (mysqli_sql_exception $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Función para validar y sanitizar entrada
function validarEntrada($data) {
    return trim(htmlspecialchars(strip_tags($data)));
}

// Función para validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función para validar teléfono (solo números y algunos caracteres especiales)
function validarTelefono($telefono) {
    return preg_match('/^[0-9+\-\s()]+$/', $telefono);
}

$alerta = '';
$errores = [];

// Eliminar cliente
if (isset($_GET['eliminar']) && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    
    if ($id === false || $id <= 0) {
        $errores[] = "ID de cliente inválido";
    } else {
        try {
            $stmt = $conexion->prepare("DELETE FROM cliente WHERE doc_cliente = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $alerta = "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Cliente eliminado',
                        text: 'El cliente fue eliminado correctamente.',
                        timer: 2000
                    }).then(() => {
                        window.location.href = 'crud_clientes.php';
                    });
                </script>";
            } else {
                $alerta = "<script>
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cliente no encontrado',
                        text: 'El cliente no existe en la base de datos.'
                    });
                </script>";
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if (str_contains($e->getMessage(), "a foreign key constraint fails")) {
                $alerta = "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'No se puede eliminar',
                        text: 'Este cliente está asociado a una factura o pedido y no puede ser eliminado.'
                    });
                </script>";
            } else {
                $alerta = "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error en la base de datos',
                        text: 'Ocurrió un error al eliminar el cliente.'
                    });
                </script>";
            }
        }
    }
}

// Actualizar cliente
if (isset($_POST['actualizar'])) {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $nombre = validarEntrada($_POST['nombre']);
    $correo = validarEntrada($_POST['correo']);
    $telefono = validarEntrada($_POST['telefono']);
    $contraseña = trim($_POST['contraseña']);
    
    // Validaciones
    if ($id === false || $id <= 0) {
        $errores[] = "ID de cliente inválido";
    }
    
    if (empty($nombre) || strlen($nombre) < 2) {
        $errores[] = "El nombre debe tener al menos 2 caracteres";
    }
    
    if (empty($correo) || !validarEmail($correo)) {
        $errores[] = "El correo electrónico no es válido";
    }
    
    if (empty($telefono) || !validarTelefono($telefono)) {
        $errores[] = "El teléfono solo puede contener números y algunos caracteres especiales";
    }
    
    if (empty($contraseña) || strlen($contraseña) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    // Verificar si el correo ya existe para otro cliente
    if (empty($errores)) {
        try {
            $stmt = $conexion->prepare("SELECT doc_cliente FROM cliente WHERE correo = ? AND doc_cliente != ?");
            $stmt->bind_param("si", $correo, $id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                $errores[] = "El correo electrónico ya está registrado para otro cliente";
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $errores[] = "Error al verificar el correo";
        }
    }
    
    // Si no hay errores, actualizar
    if (empty($errores)) {
        try {
            $contraseña_hash = password_hash($contraseña, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("UPDATE cliente SET nom_cliente = ?, correo = ?, telefono = ?, contraseña = ? WHERE doc_cliente = ?");
            $stmt->bind_param("ssssi", $nombre, $correo, $telefono, $contraseña_hash, $id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $_SESSION['mensaje_exito'] = "Cliente actualizado correctamente";
                header("Location: crud_clientes.php");
                exit();
            } else {
                $errores[] = "No se realizaron cambios o el cliente no existe";
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $errores[] = "Error al actualizar el cliente";
        }
    }
}

// Búsqueda
$filtro = "";
$clientes_result = null;

if (isset($_GET['buscar'])) {
    $filtro = validarEntrada($_GET['buscar']);
    if (!empty($filtro)) {
        try {
            $stmt = $conexion->prepare("SELECT * FROM cliente WHERE doc_cliente LIKE ? OR nom_cliente LIKE ? ORDER BY nom_cliente");
            $busqueda = "%$filtro%";
            $stmt->bind_param("ss", $busqueda, $busqueda);
            $stmt->execute();
            $clientes_result = $stmt->get_result();
        } catch (mysqli_sql_exception $e) {
            $errores[] = "Error en la búsqueda";
        }
    }
} else {
    try {
        $stmt = $conexion->prepare("SELECT * FROM cliente ORDER BY nom_cliente");
        $stmt->execute();
        $clientes_result = $stmt->get_result();
    } catch (mysqli_sql_exception $e) {
        $errores[] = "Error al cargar los clientes";
    }
}

// Mostrar mensaje de éxito si existe
if (isset($_SESSION['mensaje_exito'])) {
    $alerta = "<script>
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: '{$_SESSION['mensaje_exito']}',
            timer: 2000
        });
    </script>";
    unset($_SESSION['mensaje_exito']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Clientes - Panel de Administración</title>
    <link rel="stylesheet" href="style_crud_clientes.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .error-message {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .admin-info {
            font-size: 1em;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logout-btn {
            background-color: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 20px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .logout-btn:hover {
            background-color: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        .form-row {
            margin-bottom: 10px;
        }
        .form-row input {
            margin-right: 10px;
        }
        .btn-group {
            display: flex;
            gap: 5px;
        }
        .password-strength {
            font-size: 0.8em;
            color: #666;
        }
    </style>
</head>
<body>

<div class="admin-header">
    <div class="admin-info">
        Bienvenido, <?= htmlspecialchars($_SESSION['usuario']) ?> (<?= ucfirst($_SESSION['rol']) ?>)
        <a href="../../logout.php" class="logout-btn">Cerrar Sesión</a>
    </div>
</div>

<div class="logo_boton">
    <a href="../registro_clientes.php" class="btn_regresar">
        <img src="devolver.png" alt="Volver" width="30"> Regresar
    </a>
</div>

<h1>Gestión de Clientes - Panel de Administración</h1>

<?php if (!empty($errores)): ?>
    <div class="error-message">
        <strong>Errores encontrados:</strong>
        <ul>
            <?php foreach ($errores as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="get" class="busqueda-form">
    <input type="text" name="buscar" placeholder="Buscar por documento o nombre..." value="<?= htmlspecialchars($filtro) ?>" maxlength="50">
    <button type="submit" class="btn btn-search">🔍 Buscar</button>
    <?php if (!empty($filtro)): ?>
        <a href="crud_clientes.php" class="btn">Mostrar Todos</a>
    <?php endif; ?>
</form>

<?php if ($clientes_result && $clientes_result->num_rows > 0): ?>
    <p>Se encontraron <?= $clientes_result->num_rows ?> cliente(s)</p>
    
    <table>
        <thead>
            <tr>
                <th>Documento</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Contraseña</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($cliente = $clientes_result->fetch_assoc()): ?>
                <tr>
                    <form method="post" class="inline-form" onsubmit="return validarFormulario(this)">
                        <input type="hidden" name="id" value="<?= $cliente['doc_cliente'] ?>">
                        <td><?= htmlspecialchars($cliente['doc_cliente']) ?></td>
                        <td>
                            <input type="text" name="nombre" value="<?= htmlspecialchars($cliente['nom_cliente']) ?>" 
                                   required minlength="2" maxlength="100">
                        </td>
                        <td>
                            <input type="email" name="correo" value="<?= htmlspecialchars($cliente['correo']) ?>" 
                                   required maxlength="100">
                        </td>
                        <td>
                            <input type="text" name="telefono" value="<?= htmlspecialchars($cliente['telefono']) ?>" 
                                   required pattern="[0-9+\-\s()]+" maxlength="20">
                        </td>
                        <td>
                            <div class="password-field">
                                <input type="password" name="contraseña" value="" 
                                       placeholder="Nueva contraseña" required minlength="6" maxlength="255">
                                <button type="button" class="toggle-pass">👁️</button>
                            </div>
                            <div class="password-strength">Mín. 6 caracteres</div>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button type="submit" name="actualizar" class="btn btn-edit">Guardar</button>
                                <a href="?eliminar=1&id=<?= $cliente['doc_cliente'] ?>" 
                                   class="btn btn-delete" 
                                   onclick="return confirmarEliminacion('<?= htmlspecialchars($cliente['nom_cliente']) ?>')">
                                   Eliminar
                                </a>
                            </div>
                        </td>
                    </form>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No se encontraron clientes<?= !empty($filtro) ? " que coincidan con la búsqueda" : "" ?>.</p>
<?php endif; ?>

<script>
// Mostrar/Ocultar contraseña
document.querySelectorAll('.toggle-pass').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = btn.previousElementSibling;
        input.type = input.type === 'password' ? 'text' : 'password';
        btn.textContent = input.type === 'password' ? '👁️' : '🙈';
    });
});

// Validar formulario antes de enviar
function validarFormulario(form) {
    const nombre = form.nombre.value.trim();
    const correo = form.correo.value.trim();
    const telefono = form.telefono.value.trim();
    const contraseña = form.contraseña.value;
    
    if (nombre.length < 2) {
        Swal.fire('Error', 'El nombre debe tener al menos 2 caracteres', 'error');
        return false;
    }
    
    if (!correo || !correo.includes('@')) {
        Swal.fire('Error', 'Ingrese un correo electrónico válido', 'error');
        return false;
    }
    
    if (!telefono || !/^[0-9+\-\s()]+$/.test(telefono)) {
        Swal.fire('Error', 'El teléfono solo puede contener números y algunos caracteres especiales', 'error');
        return false;
    }
    
    if (contraseña.length < 6) {
        Swal.fire('Error', 'La contraseña debe tener al menos 6 caracteres', 'error');
        return false;
    }
    
    return true;
}

// Confirmar eliminación
function confirmarEliminacion(nombre) {
    return confirm(`¿Está seguro que desea eliminar al cliente "${nombre}"?\n\nEsta acción no se puede deshacer.`);
}

// Prevenir envío accidental del formulario
document.addEventListener('keydown', function(event) {
    if (event.key === 'Enter' && event.target.type !== 'submit') {
        event.preventDefault();
    }
});

// Auto-logout después de inactividad (30 minutos)
let inactivityTimer;
function resetInactivityTimer() {
    clearTimeout(inactivityTimer);
    inactivityTimer = setTimeout(() => {
        Swal.fire({
            title: 'Sesión expirada',
            text: 'Por seguridad, su sesión ha expirado debido a inactividad.',
            icon: 'warning',
            confirmButtonText: 'Iniciar sesión nuevamente'
        }).then(() => {
            window.location.href = '../../logout.php';
        });
    }, 30 * 60 * 1000); // 30 minutos
}

// Detectar actividad del usuario
['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
    document.addEventListener(event, resetInactivityTimer, true);
});

// Iniciar el timer
resetInactivityTimer();
</script>

<?= $alerta ?>

</body>
</html>

<?php
// Cerrar la conexión
if (isset($conexion)) {
    $conexion->close();
}
?>