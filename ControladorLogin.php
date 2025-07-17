<?php
session_start();
require_once 'conexion.php'; // conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btningresar'])) {
    $correo = trim($_POST['correo']);
    $clave = trim($_POST['contraseña']);
    
    $roles = [
        ['tabla' => 'administrador', 'id' => 'id_admin', 'nombre' => 'nombre', 'foto' => 'foto', 'redirect' => 'admin.php', 'session_key' => 'admin', 'rol' => 'administrador'],
        ['tabla' => 'cajero', 'id' => 'id_cajero', 'nombre' => 'nombre', 'foto' => 'foto', 'redirect' => 'cajero.php', 'session_key' => 'cajero', 'rol' => 'cajero'],
        ['tabla' => 'cliente', 'id' => 'doc_cliente', 'nombre' => 'nom_cliente', 'foto' => 'foto', 'redirect' => 'cliente/cliente.php', 'session_key' => 'cliente', 'rol' => 'cliente']
    ];

    foreach ($roles as $rol) {
        $stmt = $conexion->prepare("SELECT * FROM {$rol['tabla']} WHERE correo = ?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($fila = $resultado->fetch_assoc()) {
            if (password_verify($clave, $fila['contraseña'])) {
                $_SESSION['usuario'] = $correo;
                $_SESSION['rol'] = $rol['rol'];
                $_SESSION[$rol['session_key']] = [
                    'id' => $fila[$rol['id']],
                    'nombre' => $fila[$rol['nombre']],
                    'foto' => $fila[$rol['foto']] ?? 'img/default.jpg'
                ];
                header("Location: {$rol['redirect']}");
                exit;
            }
        }
    }

    // ❌ Credenciales incorrectas
    $_SESSION['error_login'] = "❌ Correo o contraseña incorrectos.";
    header("Location: login.php");
    exit();
}
?>
