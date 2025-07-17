<?php
$servidor = "localhost";
$usuario = "root";
$clave = "";
$baseDeDatos = "sultipan";
$enlace = mysqli_connect($servidor, $usuario, $clave, $baseDeDatos);

if (!$enlace) {
    die("Error en la conexión: " . mysqli_connect_error());
}

$mensaje = "";
$mensaje_tipo = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar'])) {
    $documento = trim($_POST['doc_cliente']);
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $contraseña = trim($_POST['contraseña']);

    // Validaciones
    if (empty($documento) || empty($nombre) || empty($correo) || empty($contraseña)) {
        $mensaje = "⚠ Todos los campos son obligatorios.";
        $mensaje_tipo = "mensaje-error";
    } elseif (!preg_match("/^[a-zA-ZÁÉÍÓÚáéíóúÑñ\s]{2,60}$/", $nombre)) {
        $mensaje = "⚠ El nombre solo puede contener letras y espacios.";
        $mensaje_tipo = "mensaje-error";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "⚠ El correo ingresado no es válido.";
        $mensaje_tipo = "mensaje-error";
    } elseif (!preg_match("/^\d{6,20}$/", $documento)) {
        $mensaje = "⚠ El documento debe contener solo números (mínimo 6 dígitos).";
        $mensaje_tipo = "mensaje-error";
    } else {
        // Verificar si documento existe
        $verificarDocumento = $enlace->prepare("SELECT doc_cliente FROM cliente WHERE doc_cliente = ?");
        $verificarDocumento->bind_param("s", $documento);
        $verificarDocumento->execute();
        $resultadoDocumento = $verificarDocumento->get_result();

        if ($resultadoDocumento->num_rows > 0) {
            $mensaje = "❌ El documento ya está registrado.";
            $mensaje_tipo = "mensaje-error";
        } else {
            // Verificar si correo existe
            $verificarCorreo = $enlace->prepare("SELECT correo FROM cliente WHERE correo = ?");
            $verificarCorreo->bind_param("s", $correo);
            $verificarCorreo->execute();
            $resultadoCorreo = $verificarCorreo->get_result();

            if ($resultadoCorreo->num_rows > 0) {
                $mensaje = "❌ El correo ya está registrado.";
                $mensaje_tipo = "mensaje-error";
            } else {
                $stmt = $enlace->prepare("INSERT INTO cliente (doc_cliente, nom_cliente, correo, contraseña) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $documento, $nombre, $correo, $contraseña);

                if ($stmt->execute()) {
                    $mensaje = "✅ Registro exitoso. ¡Bienvenido a Sultipan!";
                    $mensaje_tipo = "mensaje-exito";
                } else {
                    $mensaje = "❌ Error al registrar: " . $stmt->error;
                    $mensaje_tipo = "mensaje-error";
                }
                $stmt->close();
            }
            $verificarCorreo->close();
        }
        $verificarDocumento->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>REGISTRO - Panadería Sultipan</title>
  <link rel="stylesheet" href="login_registro.css" />
  <style>
    .mensaje-error {
      background-color: #f8d7da;
      color: #842029;
      padding: 10px;
      margin: 10px 0;
      border-radius: 6px;
      text-align: center;
      font-weight: bold;
    }

    .mensaje-exito {
      background-color: #d1e7dd;
      color: #0f5132;
      padding: 10px;
      margin: 10px 0;
      border-radius: 6px;
      text-align: center;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <section aria-label="Formulario de Registro">
    <div class="form-box">
      <div class="form-value">
        <h1>Registrarse</h1>

        <?php if (!empty($mensaje)): ?>
          <div class="<?= $mensaje_tipo ?>"><?= $mensaje ?></div>
        <?php endif; ?>

        <form action="registro.php" method="post" novalidate>
          <div class="inputbox">
            <input type="text" name="doc_cliente" required placeholder=" " />
            <label>Documento</label>
            <ion-icon name="person-outline"></ion-icon>
          </div>

          <div class="inputbox">
            <input type="text" name="nombre" required placeholder=" " />
            <label>Nombre</label>
            <ion-icon name="person-circle-outline"></ion-icon>
          </div>

          <div class="inputbox">
            <input type="email" name="correo" required placeholder=" " />
            <label>Correo</label>
            <ion-icon name="mail-outline"></ion-icon>
          </div>

          <div class="inputbox">
            <input type="password" name="contraseña" required placeholder=" " />
            <label>Contraseña</label>
            <ion-icon name="lock-closed-outline"></ion-icon>
          </div>

          <input type="submit" name="registrar" value="REGISTRARSE" />
          <input type="reset" value="LIMPIAR" />

          <div class="register">
            <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
            <p>Ir a <a href="index.php">Inicio</a></p>
          </div>
        </form>
      </div>
    </div>
  </section>
</body>
</html>
