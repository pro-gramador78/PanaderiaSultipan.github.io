<?php
session_start();

// Validar sesión del cajero
if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cajero') {
    header("Location: login.php");
    exit();
}

require 'conexion.php';
include 'proteger.php';

// Obtener datos del cajero
$correo = $_SESSION['usuario'];
$sql = "SELECT nombre, foto FROM cajero WHERE correo = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $cajero = $resultado->fetch_assoc();
    $nombreCajero = $cajero['nombre'];
    $fotoCajero = $cajero['foto'] ?: 'img/default.jpg';
} else {
    $nombreCajero = 'Cajero';
    $fotoCajero = 'img/default.jpg';
}

// Generar mensaje según hora del día
$hora = date("H");
if ($hora < 12) {
    $saludo = "¡Buenos días";
} elseif ($hora < 18) {
    $saludo = "¡Buenas tardes";
} else {
    $saludo = "¡Buenas noches";
}

// Obtener estadísticas dinámicas
$hoy = date('Y-m-d');

// Ventas realizadas hoy
$sqlVentasHoy = $conexion->prepare("SELECT COUNT(*) AS total FROM factura WHERE DATE(fecha) = ?");
$sqlVentasHoy->bind_param("s", $hoy);
$sqlVentasHoy->execute();
$totalVentasHoy = $sqlVentasHoy->get_result()->fetch_assoc()['total'] ?? 0;

// Total vendido hoy
$sqlTotalDinero = $conexion->prepare("SELECT SUM(total) AS total FROM factura WHERE DATE(fecha) = ?");
$sqlTotalDinero->bind_param("s", $hoy);
$sqlTotalDinero->execute();
$totalDineroHoy = $sqlTotalDinero->get_result()->fetch_assoc()['total'] ?? 0;

// Productos vendidos hoy
$sqlProductos = $conexion->prepare("
    SELECT SUM(df.cantidad) AS total 
    FROM detalle_factura df
    INNER JOIN factura f ON df.id_factura = f.id_factura
    WHERE DATE(f.fecha) = ?");
$sqlProductos->bind_param("s", $hoy);
$sqlProductos->execute();
$totalProductosHoy = $sqlProductos->get_result()->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Panel del Cajero</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Roboto', sans-serif;
    }
    body {
      background: linear-gradient(to bottom right, #fff7e6, #ffd591);
      padding-top: 80px;
      min-height: 100vh;
    }
    nav {
      background-color: #d97904;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 20px;
      position: fixed;
      top: 0;
      width: 100%;
      z-index: 1000;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }
    .usuario-contenedor {
      position: relative;
    }
    .usuario-icono {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 2px solid white;
      cursor: pointer;
    }
    .menu-desplegable {
      position: absolute;
      top: 100%;
      right: 0;
      background: white;
      color: black;
      width: 250px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
      display: none;
    }
    .menu-desplegable.abrir-menu {
      display: block;
    }
    .menu-desplegable div {
      padding: 12px;
      border-bottom: 1px solid #ddd;
      cursor: pointer;
    }
    .menu-desplegable div:hover {
      background-color: #f4f4f4;
    }

    main {
      max-width: 1200px;
      margin: auto;
      padding: 20px;
    }

    .bienvenida {
      text-align: center;
      margin-bottom: 20px;
    }

    .bienvenida h1 {
      font-size: 2em;
      color: #b35b00;
    }

    .estadisticas {
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
      justify-content: center;
      margin-bottom: 30px;
    }

    .estadistica {
      background: #fffbe9;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      text-align: center;
      width: 250px;
    }

    .estadistica h2 {
      font-size: 2em;
      color: #d97904;
    }

    .estadistica p {
      color: #6c4500;
      font-weight: bold;
    }

    .accesos-rapidos {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
    }

    .card {
      background: white;
      border-radius: 20px;
      padding: 30px;
      width: 280px;
      text-align: center;
      cursor: pointer;
      transition: 0.3s ease;
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
    }

    .card:hover {
      transform: scale(1.06);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .card img {
      width: 80px;
      height: 80px;
      margin-bottom: 15px;
    }

    .card h3 {
      font-size: 1.3em;
      color: #d97904;
      margin-top: 5px;
    }

    .avisos {
      background: #fff3cd;
      padding: 15px;
      margin: 30px auto;
      border-left: 6px solid #ffc107;
      max-width: 800px;
      border-radius: 8px;
    }

    .avisos h3 {
      color: #856404;
      margin-bottom: 10px;
    }
    .usuario-info {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 15px;
  border-bottom: 1px solid #eee;
}

.usuario-info img {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  border: 2px solid #d97904;
}

.usuario-info h2 {
  font-size: 1.1em;
  font-weight: bold;
  color: #d97904;
}

.menu-opciones {
  padding: 10px;
}

.menu-opciones div {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px;
  cursor: pointer;
  border-radius: 10px;
  transition: background 0.2s;
}

.menu-opciones div:hover {
  background: #f8f8f8;
}

.menu-opciones img {
  width: 24px;
  margin-right: 10px;
}

.menu-opciones p {
  flex-grow: 1;
  font-size: 0.95em;
  color: #333;
}

.menu-opciones span {
  color: #d97904;
  font-weight: bold;
}
  </style>
</head>
<body>

  <nav>
  <div>Panel Cajero</div>
  <div class="usuario-contenedor">
    <img src="<?= htmlspecialchars($fotoCajero) ?>" class="usuario-icono" onclick="toggleMenu()" />
    
    <!-- Menú desplegable -->
    <div class="menu-desplegable" id="menuUsuario">
      <div class="emboltorio-menu">
        <div class="usuario-info">
          <img src="<?= htmlspecialchars($fotoCajero) ?>" alt="Usuario" />
          <h2><?= htmlspecialchars($nombreCajero) ?></h2>
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
  </div>
</nav>
  <main>
    <section class="bienvenida">
      <h1><?= $saludo ?>, <?= htmlspecialchars($nombreCajero) ?> 👋</h1>
    </section>

    <section class="estadisticas">
      <div class="estadistica">
        <h2><?= $totalVentasHoy ?></h2>
        <p>Ventas hoy</p>
      </div>
      <div class="estadistica">
        <h2>$<?= number_format($totalDineroHoy, 0, ',', '.') ?></h2>
        <p>Total vendido</p>
      </div>
      <div class="estadistica">
        <h2><?= $totalProductosHoy ?></h2>
        <p>Productos vendidos</p>
      </div>
    </section>

    <section class="accesos-rapidos">
      <div class="card" onclick="location.href='ventas_facturacion_cajero/ventas_facturacion.php'">
        <img src="img/sale.jpg" alt="Venta">
        <h3>Realizar Venta</h3>
      </div>
      <div class="card" onclick="location.href='reportes_cajero/resumen_ventas.php'">
        <img src="img/report.png" alt="Reportes">
        <h3>Ver Reportes</h3>
      </div>
      <div class="card" onclick="location.href='historial_cajero.php'">
        <img src="img/historial.png" alt="Historial">
        <h3>Mis Ventas</h3>
      </div>
      
    </section>

    <section class="avisos">
      <h3>📢 Aviso importante</h3>
      <p>Recuerda revisar el inventario al finalizar tu turno y reportar inconsistencias.</p>
    </section>
  </main>

  <script>
    function toggleMenu() {
      document.getElementById("menuUsuario").classList.toggle("abrir-menu");
    }

    window.addEventListener("click", function(e) {
      const menu = document.getElementById("menuUsuario");
      if (!e.target.closest(".usuario-contenedor")) {
        menu.classList.remove("abrir-menu");
      }
    });
  </script>

</body>
</html>