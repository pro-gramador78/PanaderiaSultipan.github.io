<?php
$conexion = new mysqli(
    "mysql.railway.internal",    // Host de Railway
    "root",                      // Usuario de DB
    "gOPOLYXyQriprVoobIDiDfdalHBeCEVcN", // Contraseña de DB
    "root",                      // Nombre de la base de datos
    3306                         // Puerto
);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($conexion->connect_error) {
    die("❌ Error de conexión: " . $conexion->connect_error);
}
?>
