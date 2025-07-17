<?php
$conexion = new mysqli(
    getenv("DB_HOST"),
    getenv("DB_USER"),
    getenv("DB_PASS"),
    getenv("DB_NAME"),
    getenv("DB_PORT")
);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
?>
