<?php
$conexion = new mysqli("localhost", "root", "", "sultipan");
if ($conexion->connect_error) {
  die("Conexión fallida: " . $conexion->connect_error);
}

// Eliminar empleado
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['eliminar'], $_GET['rol'], $_GET['id'])) {
  $rol = $_GET['rol'];
  $id = (int) $_GET['id'];

  if ($rol === 'cajero') {
    $conexion->query("DELETE FROM cajero WHERE id_cajero = $id");
  } elseif ($rol === 'administrador') {
    $conexion->query("DELETE FROM administrador WHERE id_admin = $id");
  }

  header("Location: crud_empleados.php");
  exit;
}

// Actualizar empleado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
  $rol = $_POST['rol'];
  $id = (int) $_POST['id'];
  $nombre = $_POST['nombre'];
  $correo = $_POST['correo'];
  $contraseña = $_POST['contraseña'];

  if ($rol === 'cajero') {
    $conexion->query("UPDATE cajero SET nombre='$nombre', correo='$correo', contraseña='$contraseña' WHERE id_cajero = $id");
  } elseif ($rol === 'administrador') {
    $conexion->query("UPDATE administrador SET nombre='$nombre', correo='$correo', contraseña='$contraseña' WHERE id_admin = $id");
  }

  header("Location: crud_empleados.php");
  exit;
}
?>