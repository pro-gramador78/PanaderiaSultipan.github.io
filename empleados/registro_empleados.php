<?php
// Iniciar sesión
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario'])) {
    header("Location: ../login.php");  // Cambiado de "../login.php" a "login.php"
    exit();
}

// Headers para evitar caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

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

if (isset($_POST['registrar'])) {
    $documento = trim($_POST['doc_cliente']);
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $contraseña = trim($_POST['contraseña']);
    $rol = trim($_POST['rol']);

    if (
        empty($documento) || empty($nombre) ||
        empty($correo) || empty($contraseña) || empty($rol)
    ) {
        $mensaje = "⚠ TODOS LOS CAMPOS SON OBLIGATORIOS";
        $mensaje_tipo = "mensaje-error";
    } elseif (!preg_match("/^\d{6,20}$/", $documento)) {
        $mensaje = "⚠ El documento debe contener solo números (mínimo 6 dígitos)";
        $mensaje_tipo = "mensaje-error";
    } elseif (!preg_match("/^[a-zA-ZÁÉÍÓÚáéíóúÑñ\s]{2,60}$/", $nombre)) {
        $mensaje = "⚠ El nombre solo debe contener letras y espacios";
        $mensaje_tipo = "mensaje-error";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "⚠ El correo electrónico no es válido";
        $mensaje_tipo = "mensaje-error";
    } elseif (strlen($contraseña) < 6) {
        $mensaje = "⚠ La contraseña debe tener al menos 6 caracteres";
        $mensaje_tipo = "mensaje-error";
    } elseif (!in_array($rol, ['cajero', 'administrador'])) {
        $mensaje = "❌ ROL INVÁLIDO";
        $mensaje_tipo = "mensaje-error";
    } else {
        $correoExiste = false;

        $verificarCorreoCajero = $enlace->prepare("SELECT correo FROM cajero WHERE correo = ?");
        $verificarCorreoCajero->bind_param("s", $correo);
        $verificarCorreoCajero->execute();
        $resultadoCajero = $verificarCorreoCajero->get_result();
        if ($resultadoCajero->num_rows > 0) {
            $correoExiste = true;
        }
        $verificarCorreoCajero->close();

        $verificarCorreoAdmin = $enlace->prepare("SELECT correo FROM administrador WHERE correo = ?");
        $verificarCorreoAdmin->bind_param("s", $correo);
        $verificarCorreoAdmin->execute();
        $resultadoAdmin = $verificarCorreoAdmin->get_result();
        if ($resultadoAdmin->num_rows > 0) {
            $correoExiste = true;
        }
        $verificarCorreoAdmin->close();

        if ($correoExiste) {
            $mensaje = "❌ EL CORREO YA ESTÁ REGISTRADO EN EL SISTEMA";
            $mensaje_tipo = "mensaje-error";
        } else {
            $contraseñaHash = password_hash($contraseña, PASSWORD_DEFAULT);

            if ($rol === "cajero") {
                $stmt = $enlace->prepare("INSERT INTO cajero (nombre, correo, contraseña) VALUES (?, ?, ?)");
            } else {
                $stmt = $enlace->prepare("INSERT INTO administrador (nombre, correo, contraseña) VALUES (?, ?, ?)");
            }

            $stmt->bind_param("sss", $nombre, $correo, $contraseñaHash);

            if ($stmt->execute()) {
                $mensaje = "✅ REGISTRO EXITOSO COMO " . strtoupper($rol);
                $mensaje_tipo = "mensaje-exito";
            } else {
                $mensaje = "❌ ERROR AL REGISTRAR: " . $stmt->error;
                $mensaje_tipo = "mensaje-error";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>REGISTRO - Panadería Sultipan</title>
  <link rel="stylesheet" href="empleados.css" />
  <style>
    .mensaje-error {
      background-color: #f8d7da;
      color: #842029;
      padding: 10px;
      border-radius: 6px;
      margin: 10px 0;
      text-align: center;
      font-weight: bold;
    }

    .mensaje-exito {
      background-color: #d1e7dd;
      color: #0f5132;
      padding: 10px;
      border-radius: 6px;
      margin: 10px 0;
      text-align: center;
      font-weight: bold;
    }

    .admin-info {
      position: fixed;
      top: 10px;
      right: 10px;
      background-color: #e3f2fd;
      color: #1565c0;
      padding: 8px 12px;
      border-radius: 4px;
      font-size: 14px;
      font-weight: bold;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .logout-btn {
      position: fixed;
      top: 10px;
      right: 200px;
      background-color: #dc3545;
      color: white;
      padding: 8px 12px;
      border-radius: 4px;
      text-decoration: none;
      font-size: 14px;
      font-weight: bold;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .logout-btn:hover {
      background-color: #c82333;
    }
  </style>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</head>
<body>


<!-- 🔙 Botón flotante de regresar -->
<div class="logo_boton">
  <a href="../admin.php" class="btn_regresar">
    <img src="devolver.png" alt=" " width="20"> Regresar
  </a>
</div>

<div class="top-right-button">
  <a href="registro empleados/crud_empleados.php" class="btn-editar">
    <ion-icon name="create-outline"></ion-icon> Editar registros de empleados
  </a>
</div>

<section aria-label="Formulario de Registro">
  <div class="form-box">
    <div class="form-value">
      <h1 id="parrafo">Registrar Empleado</h1>
      <p style="text-align: center; color: #666; margin-bottom: 20px;">
        Solo administradores pueden registrar empleados
      </p>

      <?php if (!empty($mensaje)): ?>
        <div class="<?php echo $mensaje_tipo; ?>"><?php echo $mensaje; ?></div>
      <?php endif; ?>

      <form action="#" method="post" novalidate>
        <div class="inputbox">
          <input type="text" name="doc_cliente" required placeholder=" " pattern="\d{6,20}" title="Solo números, mínimo 6 dígitos" />
          <label id="parrafo">Documento</label>
          <ion-icon name="person-outline"></ion-icon>
        </div>

        <div class="inputbox">
          <input type="text" name="nombre" required placeholder=" " pattern="[a-zA-ZÁÉÍÓÚáéíóúÑñ\s]{2,60}" title="Solo letras y espacios" />
          <label id="parrafo">Nombre</label>
          <ion-icon name="person-circle-outline"></ion-icon>
        </div>

        <div class="inputbox">
          <input type="email" name="correo" required placeholder=" " />
          <label id="parrafo">Correo</label>
          <ion-icon name="mail-outline"></ion-icon>
        </div>

        <div class="inputbox">
          <input type="password" name="contraseña" required placeholder=" " minlength="6" title="Mínimo 6 caracteres" />
          <label id="parrafo">Contraseña</label>
          <ion-icon name="lock-closed-outline"></ion-icon>
        </div>

        <div class="inputbox">
          <label id="parrafo" for="rol"></label>
          <select name="rol" required>
            <option value="">Selecciona un rol</option>
            <option value="cajero">Cajero</option>
            <option value="administrador">Administrador</option>
          </select>
          <ion-icon name="briefcase-outline"></ion-icon>
        </div>

        <input type="submit" name="registrar" value="REGISTRAR EMPLEADO" />
        <input type="reset" value="LIMPIAR" />

      </form>
    </div>
  </div>
</section>

</body>
</html>