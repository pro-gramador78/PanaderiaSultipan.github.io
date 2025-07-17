<?php
session_start();

// ✅ Configuración para Railway usando variables de entorno
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btningresar'])) {
    $correo = $_POST['correo'];
    $contrasena = $_POST['contraseña'];

    // 🔹 Verificar en administrador
    $sql = "SELECT * FROM administrador WHERE correo = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($fila = $resultado->fetch_assoc()) {
        // Si las contraseñas están hasheadas, usar password_verify
        if (password_verify($contrasena, $fila['contraseña']) || $fila['contraseña'] === $contrasena) {
            $_SESSION['usuario'] = $correo;
            $_SESSION['rol'] = "administrador";
            $_SESSION['admin'] = [
                'nombre' => $fila['nombre'],
                'foto' => $fila['foto'] ?? 'img/default.jpg'
            ];
            header("Location: admin.php");
            exit();
        }
    }

    // 🔹 Verificar en cajero
    $sql = "SELECT * FROM cajero WHERE correo = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($fila = $resultado->fetch_assoc()) {
        // Si las contraseñas están hasheadas, usar password_verify
        if (password_verify($contrasena, $fila['contraseña']) || $fila['contraseña'] === $contrasena) {
            $_SESSION['usuario'] = $correo;
            $_SESSION['rol'] = "cajero";
            $_SESSION['cajero'] = [
                'nombre' => $fila['nombre'],
                'foto' => $fila['foto'] ?? 'img/default.jpg'
            ];
            header("Location: cajero.php");
            exit();
        }
    }

    // 🔹 Verificar en cliente
    $sql = "SELECT * FROM cliente WHERE correo = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($fila = $resultado->fetch_assoc()) {
        // Si las contraseñas están hasheadas, usar password_verify
        if (password_verify($contrasena, $fila['contraseña']) || $fila['contraseña'] === $contrasena) {
            $_SESSION['usuario'] = $correo;
            $_SESSION['rol'] = "cliente";
            $_SESSION['cliente'] = [
                'nombre' => $fila['nom_cliente'],
                'correo' => $fila['correo'],
                'foto' => $fila['foto'] ?? 'img/default.jpg'
            ];
            header("Location: cliente.php");
            exit();
        }
    }

    // ❌ Si no coincide nada
    $_SESSION['error_login'] = "❌ Correo o contraseña incorrectos.";
    header("Location: login.php");
    exit();
}
?>
