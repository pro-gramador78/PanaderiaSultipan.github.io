<?php
$id_factura = isset($_GET['id']) ? intval($_GET['id']) : 0;
$modo_pdf = isset($_GET['pdf']);

$conexion = new mysqli("localhost", "root", "", "sultipan");
if ($conexion->connect_error) die("Error de conexión: " . $conexion->connect_error);

$res = $conexion->query("
    SELECT f.*, c.nom_cliente, c.telefono, c.correo AS correo_cliente,
           COALESCE(ca.nombre,'') AS nom_cajero,
           COALESCE(ad.nombre,'') AS nom_admin
    FROM factura f
    JOIN cliente c ON f.doc_cliente=c.doc_cliente
    LEFT JOIN cajero ca ON f.id_empleado=ca.id_cajero
    LEFT JOIN administrador ad ON f.id_empleado=ad.id_admin
    WHERE f.id_factura = $id_factura
");
$factura = $res->fetch_assoc();

if (!$factura) {
    echo "Factura no encontrada.";
    exit;
}

$empleado = $factura['nom_cajero'] ?: $factura['nom_admin'];

$res_productos = $conexion->query("
    SELECT df.*, p.nom_producto
    FROM detalle_factura df
    JOIN inventario p ON df.id_producto=p.id_producto
    WHERE df.id_factura=$id_factura
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Factura #<?php echo $id_factura; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Roboto&display=swap" rel="stylesheet">
<style>
  body {
    font-family: 'Roboto', sans-serif;
    background: #fff5e0;
    padding: 20px;
    color: #3c2f2f;
  }

  .container {
    max-width: 800px;
    margin: auto;
    background: #fffdf8;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
  }

  .header {
    text-align: center;
    margin-bottom: 25px;
  }

  .header img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    box-shadow: 0 5px 10px rgba(0,0,0,0.1);
  }

  h1 {
    font-family: 'Pacifico', cursive;
    font-size: 32px;
    color: #d2691e;
    margin: 10px 0 5px;
  }

  h2 {
    color: #8b4513;
    font-weight: bold;
    margin: 0 0 20px;
  }

  .datos p {
    margin: 6px 0;
    font-size: 15px;
  }

  .datos hr {
    border: none;
    border-top: 1px solid #eee;
    margin: 15px 0;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
    font-size: 14px;
  }

  th, td {
    border: 1px solid #f3d5a3;
    padding: 10px;
    text-align: center;
  }

  th {
    background-color: #ffe2b3;
    color: #5c3b1e;
    font-weight: bold;
  }

  tbody tr:nth-child(even) {
    background-color: #fff8ec;
  }

  .total {
    text-align: right;
    font-size: 18px;
    font-weight: bold;
    margin-top: 20px;
    color: #a0522d;
  }

  @media print {
    body {
      background: white;
      padding: 0;
    }
    .container {
      box-shadow: none;
      border: none;
      padding: 0;
    }
  }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <img src="logo.jpeg" alt="Logo">
    <h1>Sultipan</h1>
    <h2>Factura #<?php echo $factura['id_factura']; ?></h2>
  </div>

  <div class="datos">
    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($factura['nom_cliente']); ?></p>
    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($factura['telefono']); ?></p>
    <p><strong>Correo:</strong> <?php echo htmlspecialchars($factura['correo_cliente']); ?></p>
    <p><strong>Empleado:</strong> <?php echo htmlspecialchars($empleado); ?></p>
    <hr>
    <p><strong>Recibido:</strong> $<?php echo number_format($factura['recibido'],2); ?></p>
    <p><strong>Vuelto:</strong> $<?php echo number_format($factura['cambio'],2); ?></p>
    <hr>
  </div>

  <table>
    <thead>
      <tr><th>Producto</th><th>Cantidad</th><th>Precio unitario</th><th>Subtotal</th></tr>
    </thead>
    <tbody>
      <?php while ($prod = $res_productos->fetch_assoc()): ?>
        <tr>
          <td><?php echo htmlspecialchars($prod['nom_producto']); ?></td>
          <td><?php echo $prod['cantidad']; ?></td>
          <td>$<?php echo number_format($prod['precio_unitario'], 2); ?></td>
          <td>$<?php echo number_format($prod['cantidad'] * $prod['precio_unitario'], 2); ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <p class="total">Total: $<?php echo number_format($factura['total'], 2); ?></p>
</div>

<?php if (!$modo_pdf): ?>
<script>
  window.onload = () => window.print();
</script>
<?php endif; ?>
</body>
</html>