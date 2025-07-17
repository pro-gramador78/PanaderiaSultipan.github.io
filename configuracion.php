<?php
session_start();
if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit();
}

$destino = "#"; // Por defecto

if (isset($_SESSION['rol'])) {
    switch ($_SESSION['rol']) {
        case 'administrador':
            $destino = 'admin.php';
            break;
        case 'cajero':
            $destino = 'cajero.php';
            break;
        case 'cliente':
            $destino = 'cliente.php';
            break;
    }
}

include("conexion.php");

$correo = $_SESSION['usuario'];
$rol = $_SESSION['rol'];
$tabla = $rol;
$campo_nombre = ($rol == 'cliente') ? 'nom_cliente' : 'nombre';

$sql = "SELECT * FROM $tabla WHERE correo = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();
$stmt->close();

$foto = $usuario['foto'] ?? 'img/default.jpg';
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Configuración de Perfil</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');

    body {
      font-family: 'Roboto', sans-serif;
      background: linear-gradient(to right, #fff4d6, #ffe8aa);
      padding: 2rem;
      margin: 0;
      color: #4b2e00;
    }

    .volver {
      text-align: center;
      margin-bottom: 2rem;
    }

    .volver a {
      padding: 12px 25px;
      border-radius: 30px;
      background: #d97904;
      color: white;
      font-weight: bold;
      text-decoration: none;
      box-shadow: 0 6px 12px rgba(217, 121, 4, 0.3);
      transition: background 0.3s;
    }

    .volver a:hover {
      background: #b45c00;
    }

    h1 {
      text-align: center;
      color: #b35b00;
      margin-bottom: 1.5rem;
      font-size: 2rem;
    }

    form {
      max-width: 500px;
      margin: auto;
      background: #fff9ed;
      padding: 2rem;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .foto-perfil {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      object-fit: cover;
      display: block;
      margin: 0 auto 1.5rem;
      border: 3px solid #ffd28a;
    }

    label {
      display: block;
      margin-top: 1rem;
      font-weight: 700;
      color: #5b3a00;
    }

    input[type='text'],
    input[type='email'],
    input[type='password'],
    input[type='file'] {
      width: 100%;
      padding: 12px;
      margin-top: 0.5rem;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 1rem;
      background: #fffdf6;
      transition: box-shadow 0.2s ease;
    }

    input:focus {
      outline: none;
      box-shadow: 0 0 0 2px #ffd28a;
    }

    button {
      margin-top: 2rem;
      width: 100%;
      padding: 14px;
      background: #e67e00;
      color: white;
      border: none;
      border-radius: 30px;
      font-size: 1rem;
      font-weight: bold;
      cursor: pointer;
      box-shadow: 0 5px 12px rgba(230, 126, 0, 0.4);
      transition: background 0.3s, transform 0.2s;
    }

    button:hover {
      background: #cc6d00;
      transform: translateY(-2px);
    }

    @media (max-width: 600px) {
      body { padding: 1rem; }
      form { padding: 1.5rem; }
    }
  </style>
</head>
<body>

  <div class="volver">
    <a href="<?= $destino ?>">← Volver al menú</a>
  </div>

  <h1>Editar Perfil</h1>

  <form action="actualizar_perfil.php" method="POST" enctype="multipart/form-data">
    <img src="<?= htmlspecialchars($foto) ?>" alt="Foto de perfil" class="foto-perfil">

    <label for="nombre">Nombre:</label>
    <input type="text" name="nombre" id="nombre" required value="<?= htmlspecialchars($usuario[$campo_nombre]) ?>">

    <label for="correo">Correo:</label>
    <input type="email" name="correo" id="correo" required value="<?= htmlspecialchars($usuario['correo']) ?>">

    <label for="contrasena_actual">Contraseña actual:</label>
    <input type="password" name="contrasena_actual" required>

    <label for="nueva_contrasena">Nueva contraseña:</label>
    <input type="password" name="nueva_contrasena" placeholder="Déjala vacía si no quieres cambiarla">

    <label for="foto">Cambiar foto de perfil:</label>
    <input type="file" name="foto" id="foto">

    <button type="submit">Guardar cambios</button>
  </form>
</body>
</html>
