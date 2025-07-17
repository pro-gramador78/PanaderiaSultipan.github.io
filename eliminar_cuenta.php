l<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['cliente'])) {
    header("Location: login.php");
    exit();
}

$doc_cliente = $_SESSION['usuario'];

$sql = "DELETE FROM cliente WHERE doc_cliente = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $doc_cliente);

if ($stmt->execute()) {
    session_destroy();
    header("Location: login.php?cuenta_eliminada=1");
} else {
    echo "Error al eliminar la cuenta: " . $stmt->error;
}
$stmt->close();