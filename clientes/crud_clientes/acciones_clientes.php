<?php
$conexion = new mysqli("localhost", "root", "", "sultipan");
if ($conexion->connect_error) {
  die("Error de conexión: " . $conexion->connect_error);
}

$doc = $_POST['doc_cliente'];

if (isset($_POST['guardar'])) {
  $nombre = $_POST['nom_cliente'];
  $correo = $_POST['correo'];
  $telefono = $_POST['telefono'];
  $contraseña = $_POST['contraseña'];

  $stmt = $conexion->prepare("UPDATE cliente SET nom_cliente=?, correo=?, telefono=?, contraseña=? WHERE doc_cliente=?");
  $stmt->bind_param("ssssi", $nombre, $correo, $telefono, $contraseña, $doc);
  $stmt->execute();
  $stmt->close();
}

if (isset($_POST['eliminar'])) {
  $conexion->query("DELETE FROM clientes WHERE doc_cliente = $doc");
}

$conexion->close();
header("Location: crud_clientes.php");
exit;
?>
