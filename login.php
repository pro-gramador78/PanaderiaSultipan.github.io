<?php
session_start();

// Mostrar errores (solo durante desarrollo, quítalo en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CONEXIÓN A RAILWAY
$DB_HOST = 'containers-us-west-190.railway.app';
$DB_USER = 'root';
$DB_PASS = 'tu_contraseña_aquí';
$DB_NAME = 'sultipan';
$DB_PORT = 5678; // reemplaza con el puerto real de tu base de datos

$conexion = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
if ($conexion->connect_error) {
    die("❌ Error de conexión: " . $conexion->connect_error);
}

$error = "";

// Autenticación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btningresar'])) {
    $correo = trim($_POST['correo']);
    $clave = trim($_POST['contraseña']);

    function verificar_usuario($conexion, $tabla, $campo_id, $campo_nombre, $correo, $clave, $redirect, $rol) {
        $sql = "SELECT * FROM $tabla WHERE correo = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($fila = $resultado->fetch_assoc()) {
            if (password_verify($clave, $fila['contraseña'])) {
                $_SESSION[$rol] = $fila[$campo_id];
                $_SESSION['nombre_' . $rol] = $fila[$campo_nombre];
                $_SESSION['rol'] = $rol;
                header("Location: $redirect");
                exit;
            }
        }
    }

    // Verificar roles
    verificar_usuario($conexion, "cliente", "doc_cliente", "nom_cliente", $correo, $clave, "../cliente/cliente.php", "cliente");
    verificar_usuario($conexion, "administrador", "id_admin", "nombre", $correo, $clave, "../admin.php", "admin");
    verificar_usuario($conexion, "cajero", "id_cajero", "nombre", $correo, $clave, "../cajero.php", "cajero");

    // Si ninguno coincidió
    $error = "❌ Correo o contraseña incorrectos.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>INICIAR SESIÓN - Panadería Sultipan</title>
  <link rel="stylesheet" href="login_registro.css" />
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</head>
<body>
  <section aria-label="Formulario de Inicio de Sesión">
    <div class="form-box">
      <div class="form-value">
        <h1>Iniciar Sesión</h1>

        <?php if (!empty($error)): ?>
          <div class="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="" method="post" novalidate>
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

          <input name="btningresar" type="submit" value="INICIAR SESIÓN" />
          <input type="reset" value="LIMPIAR" />

          <div class="register">
            <p>¿No tienes cuenta? <a href="REGISTRO.php">Registrarme</a></p>
            <p>Ir a <a href="index.php">Inicio</a></p>
          </div>

          <div style="margin-top: 10px;">
            <a href="recuperar_contraseña/solicitar.php" style="color: #e28c00; text-decoration: none; font-weight: bold;">
              ¿Olvidaste tu contraseña?
            </a>
          </div>
        </form>
      </div>
    </div>
  </section>
</body>
</html>
