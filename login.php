<?php
session_start();

// Incluir configuración de base de datos
require_once 'config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btningresar'])) {
    $correo = $_POST['correo'];
    $clave = $_POST['contraseña'];

    // Buscar en cliente
    $sql = "SELECT * FROM cliente WHERE correo = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($fila = $resultado->fetch_assoc()) {
        if ($fila['correo'] === $correo && password_verify($clave, $fila['contraseña'])) {
            $_SESSION['cliente'] = $fila['doc_cliente'];
            $_SESSION['nombre_cliente'] = $fila['nom_cliente'];
            $_SESSION['rol'] = 'cliente';
            header("Location: ../cliente/cliente.php");
            exit;
        }
    }

    // Buscar en administrador
    $sql_admin = "SELECT * FROM administrador WHERE correo = ?";
    $stmt = $conexion->prepare($sql_admin);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($fila = $resultado->fetch_assoc()) {
        if ($fila['correo'] === $correo && password_verify($clave, $fila['contraseña'])) {
            $_SESSION['admin'] = $fila['id_admin'];
            $_SESSION['rol'] = 'admin';
            header("Location: ../admin.php");
            exit;
        }
    }

    // Buscar en cajero
    $sql_cajero = "SELECT * FROM cajero WHERE correo = ?";
    $stmt = $conexion->prepare($sql_cajero);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($fila = $resultado->fetch_assoc()) {
        if ($fila['correo'] === $correo && password_verify($clave, $fila['contraseña'])) {
            $_SESSION['cajero'] = $fila['id_cajero'];
            $_SESSION['rol'] = 'cajero';
            header("Location: ../cajero.php");
            exit;
        }
    }

    // Si no coincide con ningún usuario
    $error = "Correo o contraseña incorrectos.";
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

        <form action="ControladorLogin.php" method="post" novalidate>
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
