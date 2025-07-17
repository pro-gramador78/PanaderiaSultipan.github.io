<?php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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
if ($conexion->connect_error) {
  die("Conexión fallida: " . $conexion->connect_error);
}

$alerta = '';

include '../proteger.php';

// Eliminar producto
if (isset($_GET['eliminar']) && isset($_GET['id'])) {
  $id = (int) $_GET['id'];
  try {
    $conexion->query("DELETE FROM inventario WHERE id_producto = $id");

    $alerta = "<script>
      Swal.fire({
        icon: 'success',
        title: 'Eliminado',
        text: 'Producto eliminado correctamente.'
      }).then(() => window.location.href = 'inventario.php');
    </script>";
  } catch (mysqli_sql_exception $e) {
    if (str_contains($e->getMessage(), 'a foreign key constraint fails')) {
      $alerta = "<script>
        Swal.fire({
          icon: 'error',
          title: 'No se puede eliminar',
          text: 'Este producto está asociado a una factura y no puede ser eliminado.'
        }).then(() => window.location.href = 'inventario.php');
      </script>";
    } else {
      $msg = addslashes($e->getMessage());
      $alerta = "<script>alert('Error: $msg');</script>";
    }
  }
}

// Actualizar producto
if (isset($_POST['actualizar'])) {
  $id = (int) $_POST['id'];
  $nombre = $conexion->real_escape_string($_POST['nombre']);
  $cantidad = (int) $_POST['cantidad'];
  $precio = (float) $_POST['precio'];
  $fecha_vencimiento = $conexion->real_escape_string($_POST['fecha_vencimiento']);
  $tipo = $conexion->real_escape_string($_POST['tipo_producto']);

  $conexion->query("UPDATE inventario SET
    nom_producto='$nombre',
    cantidad_disponible=$cantidad,
    precio_unitario=$precio,
    fecha_vencimiento='$fecha_vencimiento',
    tipo_producto='$tipo'
    WHERE id_producto = $id");

  header("Location: inventario.php");
  exit;
}

// Agregar nuevo producto
if (isset($_POST['agregar'])) {
  $nombre = $conexion->real_escape_string($_POST['nombre']);
  $cantidad = (int) $_POST['cantidad'];
  $precio = (float) $_POST['precio'];
  $fecha_vencimiento = $conexion->real_escape_string($_POST['fecha_vencimiento']);
  $tipo = $conexion->real_escape_string($_POST['tipo_producto']);
  $id_admin = 1;

  $resultado = $conexion->query("INSERT INTO inventario 
    (nom_producto, cantidad_disponible, precio_unitario, fecha_vencimiento, tipo_producto, id_admin)
    VALUES ('$nombre', $cantidad, $precio, '$fecha_vencimiento', '$tipo', $id_admin)");

  if (!$resultado) {
    die("Error al agregar producto: " . $conexion->error);
  }

  header("Location: inventario.php");
  exit;
}

// Tipos
$tipos_result = $conexion->query("SELECT DISTINCT tipo_producto FROM inventario ORDER BY tipo_producto ASC");
$tipos = [];
while ($fila = $tipos_result->fetch_assoc()) {
  $tipos[] = $fila['tipo_producto'];
}

// Filtro
$condicion = "";
if (isset($_GET['tipo']) && $_GET['tipo'] !== "") {
  $tipo = $conexion->real_escape_string($_GET['tipo']);
  $condicion = "WHERE tipo_producto = '$tipo'";
}
$productos = $conexion->query("SELECT * FROM inventario $condicion");
$productos_array = $productos->fetch_all(MYSQLI_ASSOC);
$no_hay_productos = empty($productos_array);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Inventario</title>
  <link rel="stylesheet" href="inventario.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<h1>Inventario de Productos</h1>

<button type="button" onclick="location.href='../admin.php'" style="margin-bottom: 20px; padding: 10px 20px; background-color:rgb(255, 155, 32); color: white; border: none; border-radius: 5px; cursor: pointer;">
  Regresar a Admin
</button>

<div class="filtro-categorias">
  <a href="inventario.php" class="<?= !isset($_GET['tipo']) ? 'activo' : '' ?>">Todos</a>
  <?php foreach ($tipos as $tipo_item): ?>
    <a href="inventario.php?tipo=<?= urlencode($tipo_item) ?>" class="<?= ($_GET['tipo'] ?? '') === $tipo_item ? 'activo' : '' ?>">
      <?= htmlspecialchars($tipo_item) ?>
    </a>
  <?php endforeach; ?>
</div>

<?php if ($no_hay_productos): ?>
  <div class="mensaje-alerta">
    🔴 No hay productos disponibles para esta categoría seleccionada.
  </div>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Cantidad</th>
      <th>Precio Unitario</th>
      <th>Fecha Vencimiento</th>
      <th>Tipo</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($productos_array as $producto): ?>
      <tr>
        <form method="post" class="inline-form">
          <input type="hidden" name="id" value="<?= $producto['id_producto'] ?>">
          <td><?= $producto['id_producto'] ?></td>
          <td><input type="text" name="nombre" value="<?= htmlspecialchars($producto['nom_producto']) ?>"></td>
          <td><input type="number" name="cantidad" value="<?= $producto['cantidad_disponible'] ?>"></td>
          <td><input type="number" name="precio" step="0.01" value="<?= $producto['precio_unitario'] ?>"></td>
          <td><input type="date" name="fecha_vencimiento" value="<?= $producto['fecha_vencimiento'] ?>"></td>
          <td><input type="text" name="tipo_producto" value="<?= htmlspecialchars($producto['tipo_producto']) ?>"></td>
          <td>
            <button type="submit" name="actualizar" class="btn btn-edit">Guardar</button>
            <a href="inventario.php?eliminar=1&id=<?= $producto['id_producto'] ?>" class="btn btn-delete" onclick="return confirm('¿Eliminar este producto?')">Eliminar</a>
          </td>
        </form>
      </tr>
    <?php endforeach; ?>
    <tr>
      <form method="post" class="inline-form">
        <td>—</td>
        <td><input type="text" name="nombre" placeholder="Nombre"></td>
        <td><input type="number" name="cantidad" placeholder="Cantidad"></td>
        <td><input type="number" name="precio" step="0.01" placeholder="Precio"></td>
        <td><input type="date" name="fecha_vencimiento"></td>
        <td><input type="text" name="tipo_producto" placeholder="Tipo"></td>
        <td>
          <button type="submit" name="agregar" class="btn btn-add">Agregar</button>
        </td>
      </form>
    </tr>
  </tbody>
</table>

<?= $alerta ?>

</body>
</html>


