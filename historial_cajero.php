<?php
session_start();
if (!isset($_SESSION['cajero'])) {
    header("Location: login.php");
    exit;
}

$conexion = new mysqli("localhost", "root", "", "sultipan");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$id_cajero = $_SESSION['cajero'];
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$sql = "SELECT f.id_factura, f.fecha, f.hora, c.nom_cliente, f.total 
        FROM factura f
        JOIN cliente c ON f.doc_cliente = c.doc_cliente
        WHERE f.id_empleado = ?
        AND f.fecha BETWEEN ? AND ?
        ORDER BY f.fecha DESC, f.hora DESC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("iss", $id_cajero, $desde, $hasta);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Historial de Ventas</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Roboto', sans-serif; }
    body {
      background: linear-gradient(135deg, #fff4e6, #ffd28a);
      min-height: 100vh;
      padding: 20px;
      color: #5b3a00;
    }
    h1 {
      text-align: center;
      margin-bottom: 20px;
      color: #d97904;
    }
    form {
      text-align: center;
      margin-bottom: 20px;
    }
    form input, form button {
      padding: 8px 12px;
      margin: 5px;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
    }
    form button {
      background-color: #d97904;
      color: white;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: #fffdf6;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    th, td {
      padding: 12px;
      border-bottom: 1px solid #ddd;
      text-align: center;
    }
    th {
      background-color: #ffe0b2;
    }
    tr:hover {
      background-color: #fff3e0;
    }
    a.btn {
      background: #fceabb;
      padding: 6px 12px;
      border-radius: 6px;
      text-decoration: none;
      color: #b45c00;
      font-weight: bold;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>
  <h1>🧾 Historial de Ventas - Cajero</h1>
  <form method="get">
    <label>Desde: <input type="date" name="desde" value="<?php echo $desde; ?>"></label>
    <label>Hasta: <input type="date" name="hasta" value="<?php echo $hasta; ?>"></label>
    <button type="submit">Filtrar</button>
    <a href="cajero.php" class="btn">⬅️ Volver</a>
  </form>

  <table>
    <thead>
      <tr>
        <th>Factura #</th>
        <th>Fecha</th>
        <th>Hora</th>
        <th>Cliente</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($fila = $resultado->fetch_assoc()): ?>
      <tr>
        <td><?php echo $fila['id_factura']; ?></td>
        <td><?php echo $fila['fecha']; ?></td>
        <td><?php echo $fila['hora']; ?></td>
        <td><?php echo $fila['nom_cliente']; ?></td>
        <td>$ <?php echo number_format($fila['total'], 0, ',', '.'); ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</body>
</html>
