<?php
session_start();
include("conexion.php");

// Verificar sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit();
}

// Configuración inicial
$correo_session = $_SESSION['usuario'];
$rol = $_SESSION['rol'];
$tabla = $rol;
$campo_nombre = ($rol == 'cliente') ? 'nom_cliente' : 'nombre';

// Obtener datos del formulario
$nombre = trim($_POST['nombre']);
$correo = trim($_POST['correo']);
$contrasena_actual = trim($_POST['contrasena_actual']);
$nueva_contrasena = trim($_POST['nueva_contrasena']);
$telefono = trim($_POST['telefono'] ?? '');

// 1. Validar contraseña actual
$sql = "SELECT contraseña, foto FROM $tabla WHERE correo = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $correo_session);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

if (!$usuario || $usuario['contraseña'] !== $contrasena_actual) {
    $_SESSION['error'] = "Contraseña actual incorrecta";
    header("Location: configuracion.php");
    exit();
}

// 2. Procesamiento de la imagen
$foto = $usuario['foto'] ?? 'img/default.jpg';

if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    // Configuración para la subida
    $directorio = 'img/usuarios/';
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }
    
    // Validar que sea una imagen
    $check = getimagesize($_FILES['foto']['tmp_name']);
    if ($check !== false) {
        // Generar nombre único para el archivo
        $extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $nombre_archivo = uniqid() . '_' . time() . '.' . $extension;
        $ruta_destino = $directorio . $nombre_archivo;
        
        // Mover el archivo subido
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino)) {
            // Eliminar la foto anterior si no es la default
            if ($foto != 'img/default.jpg' && file_exists($foto)) {
                unlink($foto);
            }
            $foto = $ruta_destino;
        } else {
            $_SESSION['error'] = "Error al subir la imagen";
            header("Location: configuracion.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "El archivo no es una imagen válida";
        header("Location: configuracion.php");
        exit();
    }
}

// 3. Actualizar datos en la base de datos
try {
    if (!empty($nueva_contrasena)) {
        $sql = "UPDATE $tabla SET $campo_nombre = ?, correo = ?, contraseña = ?, telefono = ?, foto = ? WHERE correo = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssssss", $nombre, $correo, $nueva_contrasena, $telefono, $foto, $correo_session);
    } else {
        $sql = "UPDATE $tabla SET $campo_nombre = ?, correo = ?, telefono = ?, foto = ? WHERE correo = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssss", $nombre, $correo, $telefono, $foto, $correo_session);
    }

    if ($stmt->execute()) {
        // Actualizar datos en sesión
        $_SESSION['usuario'] = $correo;
        $_SESSION[$campo_nombre] = $nombre;
        $_SESSION['foto'] = $foto;
        $_SESSION['telefono'] = $telefono;
        $_SESSION['success'] = "Perfil actualizado correctamente";
    } else {
        $_SESSION['error'] = "Error al actualizar el perfil";
    }
    $stmt->close();
    
    header("Location: configuracion.php");
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = "Error en la base de datos: " . $e->getMessage();
    header("Location: configuracion.php");
    exit();
}
?>