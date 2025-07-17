<?php
session_start();

// Validar que exista la sesión del administrador
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include 'proteger.php';
// Obtener datos del admin
$admin = $_SESSION['admin'] ?? ['nombre' => 'Administrador', 'foto' => 'img/default.jpg'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Panel Administrador</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Roboto', sans-serif;
    }

    body {
      background: linear-gradient(135deg, #fff4e6, #ffd28a);
      min-height: 100vh;
      overflow-x: hidden;
      font-size: 16px;
      color: #5b3a00;
    }

    nav {
      background-color: #d97904;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 20px;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1000;
      box-shadow: 0 2px 10px rgba(217, 121, 4, 0.6);
      font-weight: 700;
    }

    .btn-menu label {
      font-size: 30px;
      color: white;
      cursor: pointer;
    }

    .texto-menu {
      color: white;
      font-weight: bold;
      text-align: center;
      flex-grow: 1;
    }

    .usuario-icono {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      cursor: pointer;
      border: 2px solid #fff5e1;
      transition: transform 0.2s ease;
    }

    .usuario-icono:hover {
      transform: scale(1.1);
      border-color: #fceabb;
    }

    .menu-desplegable {
      position: absolute;
      top: 60px;
      right: 10px;
      background-color: white;
      width: 280px;
      max-height: 0;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(217, 121, 4, 0.5);
      border-radius: 12px;
      transition: max-height 0.3s ease-out;
      z-index: 1001;
    }

    .menu-desplegable.abrir-menu {
      max-height: 500px;
    }

    .emboltorio-menu {
      padding: 20px;
    }

    .usuario-info {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .usuario-info img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      border: 2px solid #d97904;
    }

    .usuario-info h2 {
      font-size: 1.3em;
      font-weight: 700;
      color: #d97904;
    }

    .menu-opciones div {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 18px;
      cursor: pointer;
      padding: 10px;
      border-radius: 12px;
      transition: background-color 0.3s ease;
      color: #000;
    }

    .menu-opciones div:hover {
      background-color: #fceabbaa;
    }

    .menu-opciones div img {
      width: 30px;
    }

    .menu-opciones div p {
      margin-left: 10px;
      flex-grow: 1;
      color: #000;
      font-weight: 500;
    }

    /* Menú lateral */
    #btn-menu {
      display: none;
    }

    .container-menu {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100vh;
      background: rgba(0, 0, 0, 0.6);
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
      z-index: 999;
    }

    #btn-menu:checked ~ .container-menu {
      opacity: 1;
      visibility: visible;
    }

    .cont-menu {
      background: #d97904;
      width: 250px;
      height: 100vh;
      transform: translateX(-100%);
      transition: transform 0.3s ease;
      padding-top: 80px;
      color: white;
    }

    #btn-menu:checked ~ .container-menu .cont-menu {
      transform: translateX(0);
    }

    .cont-menu nav {
      display: flex;
      flex-direction: column;
      gap: 20px;
      padding-left: 20px;
    }

    .cont-menu nav a {
      padding: 15px 25px;
      color: #fffbe6;
      text-decoration: none;
      font-size: 18px;
      transition: all 0.3s ease;
      border-left: 5px solid transparent;
      font-weight: 700;
    }

    .cont-menu nav a:hover {
      background: #fceabb;
      border-left-color: #b26603;
      color: #5b3a00;
    }

    .cont-menu label {
      position: absolute;
      top: 20px;
      right: 20px;
      font-size: 28px;
      color: white;
      cursor: pointer;
    }

    .contenido-central {
      margin-top: 120px;
      text-align: center;
      padding: 20px;
    }

    .contenido-central h1 {
      font-size: 2em;
      margin-bottom: 10px;
      color: #b45c00;
    }

    .contenido-central p {
      font-size: 1.1em;
      margin-bottom: 30px;
    }

    .tarjetas {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 30px;
    }

    .tarjeta {
      background-color: #fff8dc;
      border-radius: 15px;
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
      padding: 25px;
      width: 220px;
      text-align: center;
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.3s ease;
    }

    .tarjeta:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 18px rgba(0, 0, 0, 0.2);
    }

    .tarjeta img {
      width: 64px;
      height: 64px;
      margin-bottom: 10px;
    }

    .tarjeta h3 {
      color: #d97904;
      font-size: 1.1em;
    }
  </style>
</head>
<body>

  <nav>
    <div class="btn-menu">
      <label for="btn-menu">☰</label>
    </div>
    <div class="texto-menu">
      Bienvenido Administrador, controla tu sistema
    </div>
    <img src="<?= htmlspecialchars($admin['foto']) ?>" alt="Usuario" class="usuario-icono" />

    <!-- Menú desplegable -->
    <div class="menu-desplegable" id="menuUsuario">
      <div class="emboltorio-menu">
        <div class="usuario-info">
          <img src="<?= htmlspecialchars($admin['foto']) ?>" alt="Usuario" />
          <h2><?= htmlspecialchars($admin['nombre']) ?></h2>
        </div>
        <div class="menu-opciones">
          <div><img src="img/feedback.png" alt=""><p>Enviar comentarios</p><span>&gt;</span></div>
          <div><img src="img/setting.png" alt=""><p>Configuración</p><span>&gt;</span></div>
          <div><img src="img/help.png" alt=""><p>Ayuda</p><span>&gt;</span></div>
          <div><img src="img/display.png" alt=""><p>Pantalla</p><span>&gt;</span></div>
          <div onclick="location.href='logout.php';"><img src="img/logout.png" alt=""><p>Cerrar sesión</p><span>&gt;</span></div>
        </div>
      </div>
    </div>
  </nav>

  <!-- Contenido principal -->
  <div class="contenido-central">
    <h1>¡Hola, <?= htmlspecialchars($admin['nombre']) ?>! 👋</h1>
    <p>Desde aquí puedes registrar empleados, gestionar clientes, ventas e inventario.</p>

    <div class="tarjetas" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 20px;">
      <div class="tarjeta" onclick="location.href='empleados/registro_empleados.php'" style="flex: 1 1 30%; max-width: 30%;">
        <img src="img/empleados.png" alt="Empleados">
        <h3>Registro Empleados</h3>
      </div>
      <div class="tarjeta" onclick="location.href='clientes/registro_clientes.php'" style="flex: 1 1 30%; max-width: 30%;">
        <img src="img/cliente.png" alt="Clientes">
        <h3>Registro Clientes</h3>
      </div>
      <div class="tarjeta" onclick="location.href='modulo_inventario/inventario.php'" style="flex: 1 1 30%; max-width: 30%;">
        <img src="img/inventory.svg" alt="Inventario">
        <h3>Inventario</h3>
      </div>
      <div class="tarjeta" onclick="location.href='modulo_ventas_facturacion/ventas_facturacion.php'" style="flex: 1 1 30%; max-width: 30%;">
        <img src="img/sale.jpg" alt="Venta">
        <h3>Realizar Venta</h3>
      </div>
      <div class="tarjeta" onclick="location.href='reportes/resumen_ventas.php'" style="flex: 1 1 30%; max-width: 30%;">
        <img src="img/report.png" alt="Reportes">
        <h3>Reportes</h3>
      </div>
      <div class="tarjeta" onclick="location.href='historial_admin/historial_cajero.php'" style="flex: 1 1 30%; max-width: 30%;">
        <img src="img/historial.png" alt="Historial de ventas">
        <h3>Mis Ventas</h3>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const usuarioIcono = document.querySelector('.usuario-icono');
      const menu = document.getElementById('menuUsuario');
      if (usuarioIcono && menu) {
        usuarioIcono.addEventListener('click', () => {
          menu.classList.toggle('abrir-menu');
        });
      }
    });
  </script>
</body>
</html>
