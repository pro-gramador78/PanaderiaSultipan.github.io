<?php
// config.php - Configuración de base de datos para Railway

// Configuración de base de datos usando variables de entorno de Railway
$host = $_ENV['MYSQLHOST'] ?? 'localhost';
$username = $_ENV['MYSQLUSER'] ?? 'root';
$password = $_ENV['MYSQLPASSWORD'] ?? '';
$database = $_ENV['MYSQL_DATABASE'] ?? 'sultipan';
$port = $_ENV['MYSQLPORT'] ?? 3306;

// Crear conexión
$conexion = new mysqli($host, $username, $password, $database, $port);

// Verificar conexión
if ($conexion->connect_error) {
    error_log("Error de conexión: " . $conexion->connect_error);
    die("Error de conexión a la base de datos");
}

// Configurar charset
$conexion->set_charset("utf8");

// Debug temporal (eliminar después)
error_log("Conexión exitosa a: " . $host . ":" . $port . " - DB: " . $database);
?>
