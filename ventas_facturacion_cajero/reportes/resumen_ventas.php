<?php
session_start();
$conexion = new mysqli("localhost", "root", "", "sultipan");
if ($conexion->connect_error) die("Error de conexión: " . $conexion->connect_error);

// Ventas del día
$res_dia = $conexion->query("SELECT SUM(total) AS total_dia FROM factura WHERE fecha = CURDATE()");
$total_dia = $res_dia->fetch_assoc()['total_dia'] ?? 0;

// Ventas del mes
$res_mes = $conexion->query("SELECT SUM(total) AS total_mes FROM factura WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())");
$total_mes = $res_mes->fetch_assoc()['total_mes'] ?? 0;

// Ventas del año
$res_ano = $conexion->query("SELECT SUM(total) AS total_ano FROM factura WHERE YEAR(fecha) = YEAR(CURDATE())");
$total_ano = $res_ano->fetch_assoc()['total_ano'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Resumen de Ventas</title>
  <link rel="stylesheet" href="../ventas.css">
  <style>
    body {
      background: #fff8ec;
      font-family: 'Segoe UI', sans-serif;
      padding: 30px;
      color: #333;
    }
    h1 {
      color: #e28c00;
      text-align: center;
      margin-bottom: 40px;
    }
    .tarjetas {
      display: flex;
      gap: 30px;
      justify-content: center;
      flex-wrap: wrap;
    }
    .tarjeta {
      background: #fff3d4;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      padding: 25px 35px;
      text-align: center;
      min-width: 250px;
      transition: transform 0.3s;
    }
    .tarjeta:hover {
      transform: translateY(-5px);
    }
    .tarjeta h2 {
      font-size: 20px;
      color: #cc7700;
      margin-bottom: 15px;
    }
    .tarjeta p {
      font-size: 24px;
      font-weight: bold;
      color: #000;
    }
    .btn_volver {
      display: block;
      width: max-content;
      margin: 40px auto 0;
      padding: 10px 25px;
      background: #e28c00;
      color: white;
      border: none;
      border-radius: 10px;
      text-decoration: none;
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
      transition: background 0.3s;
    }
    .btn_volver:hover {
      background: #cc7700;
    }
  </style>
</head>
<body>

<h1>Resumen de Ventas</h1>

<div class="tarjetas">
  <div class="tarjeta">
    <h2>Ventas del Día</h2>
    <p>$<?= number_format($total_dia, 2) ?></p>
  </div>
  <div class="tarjeta">
    <h2>Ventas del Mes</h2>
    <p>$<?= number_format($total_mes, 2) ?></p>
  </div>
  <div class="tarjeta">
    <h2>Ventas del Año</h2>
    <p>$<?= number_format($total_ano, 2) ?></p>
  </div>
</div>

<a class="btn_volver" href="../ventas_facturacion.php"> Regresar al Panel</a>

</body>
</html>