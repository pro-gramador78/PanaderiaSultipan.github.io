<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario'])) {
    header("Location: ../login.php");  // Cambiado de "../login.php" a "login.php"
    exit();
}

// Headers para evitar caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$conexion = new mysqli("localhost", "root", "", "sultipan");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($conexion->connect_error) {
  die("Conexión fallida: " . $conexion->connect_error);
}


// Eliminar empleado
$alerta = '';
if (isset($_GET['eliminar'], $_GET['rol'], $_GET['id'])) {
  $rol = $_GET['rol'];
  $id = (int) $_GET['id'];

  try {
    if ($rol === "cajero") {
      $conexion->query("DELETE FROM cajero WHERE id_cajero = $id");
    } elseif ($rol === "administrador") {
      $conexion->query("DELETE FROM administrador WHERE id_admin = $id");
    }

    // Mensaje de éxito
    $alerta = "<script>
      Swal.fire({
        icon: 'success',
        title: 'Eliminado',
        text: 'Empleado eliminado correctamente'
      }).then(() => {
        window.location.href = 'crud_empleados.php';
      });
    </script>";

  } catch (mysqli_sql_exception $e) {
    if (str_contains($e->getMessage(), "a foreign key constraint fails")) {
      // Error por clave foránea
      $alerta = "<script>
        Swal.fire({
          icon: 'error',
          title: 'No se puede eliminar',
          text: 'Este empleado está asociado a una factura y no puede ser eliminado.'
        }).then(() => {
          window.location.href = 'crud_empleados.php';
        });
      </script>";
    } else {
      // Otro error
      $msg = addslashes($e->getMessage());
      $alerta = "<script>alert('Error: $msg');</script>";
    }
  }
}

// Actualizar empleado
if (isset($_POST['actualizar'])) {
  $rol = $_POST['rol'];
  $id = (int) $_POST['id'];
  $nombre = $_POST['nombre'];
  $correo = $_POST['correo'];
  $contraseña = password_hash(trim($_POST['contraseña']), PASSWORD_DEFAULT);

  if ($rol === "cajero") {
    $conexion->query("UPDATE cajero SET nombre='$nombre', correo='$correo', contraseña='$contraseña' WHERE id_cajero = $id");
  } elseif ($rol === "administrador") {
    $conexion->query("UPDATE administrador SET nombre='$nombre', correo='$correo', contraseña='$contraseña' WHERE id_admin = $id");
  }

  header("Location: crud_empleados.php");
  exit;
}

// Buscar
$buscar = isset($_GET['buscar']) ? $conexion->real_escape_string($_GET['buscar']) : '';

$cajeros = $conexion->query("SELECT id_cajero AS id, nombre, correo, contraseña, 'cajero' AS rol FROM cajero WHERE nombre LIKE '%$buscar%'");
$administradores = $conexion->query("SELECT id_admin AS id, nombre, correo, contraseña, 'administrador' AS rol FROM administrador WHERE nombre LIKE '%$buscar%'");
$empleados = array_merge($cajeros->fetch_all(MYSQLI_ASSOC), $administradores->fetch_all(MYSQLI_ASSOC));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Empleados</title>
  <link rel="stylesheet" href="style_crud_empleados.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<!-- Botón Regresar -->
<div class="logo_boton">
  <a href="../registro_empleados.php" class="btn_regresar">
    <img src="devolver.png" alt="" width="20"> Regresar
  </a>
</div>

<h1>Editar Registros de Empleados</h1>

<!-- Búsqueda -->
<form method="get" class="busqueda-form">
  <input type="text" name="buscar" placeholder="Buscar por nombre" value="<?= htmlspecialchars($buscar) ?>">
  <button type="submit" class="btn btn-search">Buscar</button>
</form>

<table>
  <thead>
    <tr>
      <th>Rol</th>
      <th>Nombre</th>
      <th>Correo</th>
      <th>Contraseña</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($empleados as $index => $empleado): ?>
      <tr>
        <form method="post" class="inline-form">
          <input type="hidden" name="id" value="<?= $empleado['id'] ?>">
          <input type="hidden" name="rol" value="<?= $empleado['rol'] ?>">
          <td><?= ucfirst($empleado['rol']) ?></td>
          <td><input type="text" name="nombre" value="<?= htmlspecialchars($empleado['nombre']) ?>"></td>
          <td><input type="email" name="correo" value="<?= htmlspecialchars($empleado['correo']) ?>"></td>
          <td class="password-field">
            <input type="password" name="contraseña" id="pass<?= $index ?>" value="<?= htmlspecialchars($empleado['contraseña']) ?>">
            <button type="button" class="toggle-pass" onclick="togglePassword('pass<?= $index ?>')">👁</button>
          </td>
          <td>
            <button type="submit" name="actualizar" class="btn btn-edit">Guardar</button>
            <a href="crud_empleados.php?eliminar=1&rol=<?= $empleado['rol'] ?>&id=<?= $empleado['id'] ?>" class="btn btn-delete" onclick="return confirm('¿Eliminar este empleado?')">Eliminar</a>
          </td>
        </form>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<script>
function togglePassword(id) {
  const campo = document.getElementById(id);
  campo.type = campo.type === "password" ? "text" : "password";
}
</script>

<?= $alerta ?>

</body>
</html>