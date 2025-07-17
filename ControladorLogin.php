<?php
session_start();
include("conexion.php"); // conexión a la base de datos

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btningresar'])) {
    $correo = $_POST['correo'];
    $contrasena = $_POST['contraseña'];

    // 🔹 Verificar en administrador
    $sql = "SELECT * FROM administrador WHERE correo = '$correo' AND contraseña = '$contrasena'";
    $resultado = mysqli_query($conexion, $sql);
    if (mysqli_num_rows($resultado) > 0) {
        $fila = mysqli_fetch_assoc($resultado);
        $_SESSION['usuario'] = $correo;
        $_SESSION['rol'] = "administrador";
        $_SESSION['admin'] = [
            'nombre' => $fila['nombre'],
            'foto' => $fila['foto'] ?? 'img/default.jpg'
        ];
        header("Location: admin.php");
        exit();
    }

    // 🔹 Verificar en cajero
    $sql = "SELECT * FROM cajero WHERE correo = '$correo' AND contraseña = '$contrasena'";
    $resultado = mysqli_query($conexion, $sql);
    if (mysqli_num_rows($resultado) > 0) {
        $fila = mysqli_fetch_assoc($resultado);
        $_SESSION['usuario'] = $correo;
        $_SESSION['rol'] = "cajero";
        $_SESSION['cajero'] = [
            'nombre' => $fila['nombre'],
            'foto' => $fila['foto'] ?? 'img/default.jpg'
        ];
        header("Location: cajero.php");
        exit();
    }

    // 🔹 Verificar en cliente
    $sql = "SELECT * FROM cliente WHERE correo = '$correo' AND contraseña = '$contrasena'";
    $resultado = mysqli_query($conexion, $sql);
    if (mysqli_num_rows($resultado) > 0) {
        $fila = mysqli_fetch_assoc($resultado);
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

    // ❌ Si no coincide nada
    $_SESSION['error_login'] = "❌ Correo o contraseña incorrectos.";
    header("Location: login.php");
    exit();
}
?>
