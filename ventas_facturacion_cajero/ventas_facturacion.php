<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Verificar tipo de usuario para el botón de regreso
$tipo_usuario = $_SESSION['tipo'] ?? '';
$pagina_regreso = ($tipo_usuario == 'admin') ? '../admin.php' : '../cajero.php';

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "sultipan");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Función para sanitizar entradas
function sanitizar($input) {
    global $conexion;
    return $conexion->real_escape_string(trim($input));
}

// --- ELIMINAR FACTURA ---
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    
    // Usar prepared statements para seguridad
    $stmt = $conexion->prepare("DELETE FROM detalle_factura WHERE id_factura = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $stmt = $conexion->prepare("DELETE FROM factura WHERE id_factura = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    echo "<script>alert('Factura eliminada correctamente'); location='ventas_facturacion.php';</script>";
    exit;
}

// --- PROCESAR NUEVA FACTURA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_factura'])) {
    $detalles = json_decode($_POST['detalles'], true);
    $doc_cliente = sanitizar($_POST['doc_cliente']);
    $id_empleado = intval($_POST['id_empleado']);
    $total = floatval($_POST['total']);
    $recibido = floatval($_POST['recibido']);
    $cambio = $recibido - $total;
    $fecha = date('Y-m-d');
    $hora = date('H:i:s');

    // Validaciones
    if ($total <= 0) {
        echo "<script>alert('❌ El total debe ser mayor a 0');</script>";
    } elseif (empty($detalles)) {
        echo "<script>alert('❌ Debe agregar productos a la factura');</script>";
    } elseif ($recibido < $total) {
        echo "<script>alert('❌ El monto recibido es menor al total');</script>";
    } else {
        $conexion->autocommit(false);
        
        try {
            // Insertar factura
            $stmt = $conexion->prepare("INSERT INTO factura (fecha, hora, doc_cliente, total, recibido, cambio, id_empleado) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssidddi", $fecha, $hora, $doc_cliente, $total, $recibido, $cambio, $id_empleado);
            
            if ($stmt->execute()) {
                $id_factura = $conexion->insert_id;
                
                $todo_ok = true;
                foreach ($detalles as $detalle) {
                    $id_producto = intval($detalle['id']);
                    $cantidad = intval($detalle['cantidad']);
                    $precio = floatval($detalle['precio']);
                    
                    // Verificar stock disponible
                    $stmt_stock = $conexion->prepare("SELECT cantidad_disponible FROM inventario WHERE id_producto = ?");
                    $stmt_stock->bind_param("i", $id_producto);
                    $stmt_stock->execute();
                    $resultado = $stmt_stock->get_result();
                    $stock = $resultado->fetch_assoc();
                    
                    if ($stock && $stock['cantidad_disponible'] >= $cantidad) {
                        // Actualizar inventario
                        $stmt_update = $conexion->prepare("UPDATE inventario SET cantidad_disponible = cantidad_disponible - ? WHERE id_producto = ?");
                        $stmt_update->bind_param("ii", $cantidad, $id_producto);
                        $stmt_update->execute();
                        
                        // Insertar detalle de factura
                        $stmt_detalle = $conexion->prepare("INSERT INTO detalle_factura (id_factura, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
                        $stmt_detalle->bind_param("iiid", $id_factura, $id_producto, $cantidad, $precio);
                        $stmt_detalle->execute();
                    } else {
                        $todo_ok = false;
                        break;
                    }
                }
                
                if ($todo_ok) {
                    $conexion->commit();
                    echo "<script>alert('✅ Factura registrada correctamente'); location='ventas_facturacion.php';</script>";
                    exit;
                } else {
                    $conexion->rollback();
                    echo "<script>alert('❌ Stock insuficiente para uno o más productos');</script>";
                }
            } else {
                throw new Exception("Error al insertar la factura");
            }
        } catch (Exception $e) {
            $conexion->rollback();
            echo "<script>alert('❌ Error al procesar la factura: " . $e->getMessage() . "');</script>";
        }
        
        $conexion->autocommit(true);
    }
}

// --- ENVIAR FACTURA POR CORREO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_factura_id'])) {
    $id_factura = intval($_POST['enviar_factura_id']);
    
    // Obtener datos de la factura
    $stmt = $conexion->prepare("SELECT f.*, c.nom_cliente, c.telefono, c.correo AS correo_cliente,
                                      COALESCE(ca.nombre, ad.nombre) AS nombre_empleado
                               FROM factura f
                               JOIN cliente c ON f.doc_cliente = c.doc_cliente
                               LEFT JOIN cajero ca ON f.id_empleado = ca.id_cajero
                               LEFT JOIN administrador ad ON f.id_empleado = ad.id_admin
                               WHERE f.id_factura = ?");
    $stmt->bind_param("i", $id_factura);
    $stmt->execute();
    $factura = $stmt->get_result()->fetch_assoc();
    
    if ($factura && !empty($factura['correo_cliente'])) {
        // Obtener productos de la factura
        $stmt_productos = $conexion->prepare("SELECT df.*, i.nom_producto
                                             FROM detalle_factura df
                                             JOIN inventario i ON df.id_producto = i.id_producto
                                             WHERE df.id_factura = ?");
        $stmt_productos->bind_param("i", $id_factura);
        $stmt_productos->execute();
        $productos = $stmt_productos->get_result();
        
        // Generar HTML para el PDF
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: 'Arial', sans-serif; background-color: #fff; color: #333; margin: 20px; }
                .header { text-align: center; color: #e28c00; margin-bottom: 20px; }
                .logo { display: block; margin: 0 auto; width: 120px; height: 120px; border-radius: 50%; object-fit: cover; }
                .info { margin: 20px 0; }
                .info p { margin: 5px 0; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 12px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; color: #000; font-weight: bold; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .total-row { background-color: #f8f9fa; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; font-style: italic; color: #666; }
            </style>
        </head>
        <body>
            <?php if (file_exists(__DIR__.'/logo.jpeg')): ?>
            <div class="header">
                <img src="data:image/jpeg;base64,<?= base64_encode(file_get_contents(__DIR__.'/logo.jpeg')) ?>" alt="Logo Sultipan" class="logo">
            </div>
            <?php endif; ?>
            
            <h1 class="header">PANADERÍA SULTIPAN</h1>
            <h2 class="header">Factura No. <?= $factura['id_factura'] ?></h2>
            
            <div class="info">
                <p><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($factura['fecha'])) ?></p>
                <p><strong>Hora:</strong> <?= $factura['hora'] ?></p>
                <p><strong>Cliente:</strong> <?= htmlspecialchars($factura['nom_cliente']) ?></p>
                <p><strong>Teléfono:</strong> <?= htmlspecialchars($factura['telefono']) ?></p>
                <p><strong>Atendido por:</strong> <?= htmlspecialchars($factura['nombre_empleado']) ?></p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-right">Precio Unit.</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($producto = $productos->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($producto['nom_producto']) ?></td>
                        <td class="text-center"><?= $producto['cantidad'] ?></td>
                        <td class="text-right">$<?= number_format($producto['precio_unitario'], 2) ?></td>
                        <td class="text-right">$<?= number_format($producto['cantidad'] * $producto['precio_unitario'], 2) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" class="text-right"><strong>TOTAL:</strong></td>
                        <td class="text-right"><strong>$<?= number_format($factura['total'], 2) ?></strong></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-right">Recibido:</td>
                        <td class="text-right">$<?= number_format($factura['recibido'], 2) ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-right">Cambio:</td>
                        <td class="text-right">$<?= number_format($factura['cambio'], 2) ?></td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="footer">
                <p>¡Gracias por su compra en Panadería Sultipan!</p>
                <p>Esperamos verle pronto</p>
            </div>
        </body>
        </html>
        <?php
        $html = ob_get_clean();
        
        // Generar PDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $pdfOutput = $dompdf->output();
        $pdfPath = __DIR__ . "/factura_{$factura['id_factura']}.pdf";
        file_put_contents($pdfPath, $pdfOutput);
        
        // Enviar por correo
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'duvangomez256@gmail.com';
            $mail->Password = 'onie mtmf dghn rvsk'; // Considera usar variables de entorno
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            $mail->setFrom('duvangomez256@gmail.com', 'Panadería Sultipan');
            $mail->addAddress($factura['correo_cliente'], $factura['nom_cliente']);
            
            $mail->isHTML(true);
            $mail->Subject = 'Factura No. ' . $factura['id_factura'] . ' - Panadería Sultipan';
            $mail->Body = "
                <h2>Estimado/a " . htmlspecialchars($factura['nom_cliente']) . "</h2>
                <p>Adjunto encontrará su factura en formato PDF.</p>
                <p><strong>Detalles de la compra:</strong></p>
                <ul>
                    <li>Factura No: " . $factura['id_factura'] . "</li>
                    <li>Fecha: " . date('d/m/Y', strtotime($factura['fecha'])) . "</li>
                    <li>Total: $" . number_format($factura['total'], 2) . "</li>
                </ul>
                <p>¡Gracias por su compra en Panadería Sultipan!</p>
                <p>Esperamos verle pronto.</p>
            ";
            
            $mail->addAttachment($pdfPath);
            $mail->send();
            
            // Eliminar archivo temporal
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
            
            echo "<script>alert('✅ Factura enviada exitosamente al correo " . $factura['correo_cliente'] . "'); location='ventas_facturacion.php';</script>";
            
        } catch (Exception $e) {
            echo "<script>alert('❌ Error al enviar el correo: " . $e->getMessage() . "');</script>";
        }
    } else {
        echo "<script>alert('❌ No se encontró la factura o el cliente no tiene correo registrado');</script>";
    }
}

// Obtener datos para el formulario
$clientes = $conexion->query("SELECT doc_cliente, nom_cliente, correo FROM cliente ORDER BY nom_cliente");
$productos = $conexion->query("SELECT id_producto, nom_producto, precio_unitario, cantidad_disponible FROM inventario WHERE cantidad_disponible > 0 ORDER BY nom_producto");
$cajeros = $conexion->query("SELECT id_cajero AS id, nombre FROM cajero ORDER BY nombre");
$admins = $conexion->query("SELECT id_admin AS id, nombre FROM administrador ORDER BY nombre");

$empleados = [];
if ($cajeros) {
    while ($c = $cajeros->fetch_assoc()) {
        $empleados[] = ['id' => $c['id'], 'nombre' => $c['nombre'] . " (Cajero)"];
    }
}
if ($admins) {
    while ($a = $admins->fetch_assoc()) {
        $empleados[] = ['id' => $a['id'], 'nombre' => $a['nombre'] . " (Admin)"];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas y Facturación - Sultipan</title>
    <link rel="stylesheet" href="ventas.css">
    <style>
        .btn-regresar {
            display: inline-block;
            padding: 10px 15px;
            background-color: #e28c00;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-regresar:hover {
            background-color: #c57600;
        }
        .stock-alert {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .stock-alert h3 {
            margin-top: 0;
            color: #856404;
        }
        .stock-alert ul {
            margin: 10px 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .btn-info {
            background-color: #17a2b8;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th,
        table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        table th {
            background-color: #000000;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .product-row {
            background-color: #000000;
        }
        .total-row {
            background-color: #000000;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Botón de regreso -->
    <a href="<?= $pagina_regreso ?>" class="btn-regresar">
        ← REGRESAR AL PANEL
    </a>
    
    <a href="reportes/resumen_ventas.php" class="btn-regresar">
        📊 VER REPORTE DE VENTAS
    </a>

    <?php
    // Mostrar productos con stock bajo
    $alerta_stock = [];
    $stock_check = $conexion->query("SELECT nom_producto, cantidad_disponible FROM inventario WHERE cantidad_disponible <= 10 ORDER BY cantidad_disponible ASC");
    if ($stock_check) {
        while ($r = $stock_check->fetch_assoc()) {
            $alerta_stock[] = ['producto' => $r['nom_producto'], 'stock' => $r['cantidad_disponible']];
        }
    }
    
    if (!empty($alerta_stock)) {
        echo '<div class="stock-alert">';
        echo '<h3>⚠️ Productos con poco stock:</h3>';
        echo '<ul>';
        foreach ($alerta_stock as $a) {
            $unidades = $a['stock'] == 1 ? 'unidad' : 'unidades';
            echo "<li><strong>{$a['producto']}</strong>: {$a['stock']} {$unidades} disponibles</li>";
        }
        echo '</ul>';
        echo '</div>';
    }
    ?>

    <h1>🧾 Registrar Nueva Factura</h1>
    
    <form method="post" onsubmit="return validarFormulario()">
        <input type="hidden" name="nueva_factura" value="1">
        
        <div class="form-group">
            <label for="doc_cliente">Cliente:</label>
            <select name="doc_cliente" id="doc_cliente" required>
                <option value="">-- Seleccione un cliente --</option>
                <?php if ($clientes): ?>
                    <?php mysqli_data_seek($clientes, 0); ?>
                    <?php while ($c = $clientes->fetch_assoc()): ?>
                        <option value="<?= $c['doc_cliente'] ?>" data-correo="<?= $c['correo'] ?>">
                            <?= htmlspecialchars($c['nom_cliente']) ?>
                            <?= $c['correo'] ? ' (' . $c['correo'] . ')' : ' (Sin correo)' ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="id_empleado">Empleado/Cajero:</label>
            <select name="id_empleado" id="id_empleado" required>
                <option value="">-- Seleccione un empleado --</option>
                <?php foreach ($empleados as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <h3>Agregar Productos</h3>
        <div class="form-group">
            <label for="producto_select">Producto:</label>
            <select id="producto_select">
                <option value="">-- Seleccione un producto --</option>
                <?php if ($productos): ?>
                    <?php mysqli_data_seek($productos, 0); ?>
                    <?php while ($p = $productos->fetch_assoc()): ?>
                        <option value="<?= $p['id_producto'] ?>" 
                                data-precio="<?= $p['precio_unitario'] ?>"
                                data-stock="<?= $p['cantidad_disponible'] ?>">
                            <?= htmlspecialchars($p['nom_producto']) ?> 
                            - $<?= number_format($p['precio_unitario'], 2) ?> 
                            (Stock: <?= $p['cantidad_disponible'] ?>)
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="cantidad">Cantidad:</label>
            <input type="number" id="cantidad" min="1" value="1" max="999">
            <button type="button" class="btn-primary" onclick="agregarProducto()">➕ Agregar Producto</button>
        </div>

        <div class="table-container">
            <table id="tabla_detalle">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-right">Precio Unit.</th>
                        <th class="text-right">Subtotal</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Los productos se agregarán dinámicamente aquí -->
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <th colspan="3" class="text-right">TOTAL:</th>
                        <th class="text-right">$<span id="total_display">0.00</span></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="form-group">
            <label for="recibido">Monto Recibido:</label>
            <input type="number" name="recibido" id="recibido" step="0.01" min="0" required>
        </div>

        <div class="form-group">
            <label>Cambio: $<span id="cambio_display">0.00</span></label>
        </div>

        <input type="hidden" name="total" id="total_hidden">
        <input type="hidden" name="detalles" id="detalles_hidden">
        
        <button type="submit" class="btn-primary">💾 Guardar Factura</button>
    </form>

    <h2>📋 Facturas Registradas</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Empleado</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th class="text-right">Total</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $facturas = $conexion->query("SELECT f.*, c.nom_cliente, c.correo, 
                                                    COALESCE(ca.nombre, ad.nombre) AS nombre_empleado
                                             FROM factura f
                                             JOIN cliente c ON f.doc_cliente = c.doc_cliente
                                             LEFT JOIN cajero ca ON f.id_empleado = ca.id_cajero
                                             LEFT JOIN administrador ad ON f.id_empleado = ad.id_admin
                                             ORDER BY f.id_factura DESC
                                             LIMIT 50");
                
                if ($facturas):
                    while ($f = $facturas->fetch_assoc()):
                ?>
                <tr>
                    <td><?= $f['id_factura'] ?></td>
                    <td><?= htmlspecialchars($f['nom_cliente']) ?></td>
                    <td><?= htmlspecialchars($f['nombre_empleado']) ?></td>
                    <td><?= date('d/m/Y', strtotime($f['fecha'])) ?></td>
                    <td><?= $f['hora'] ?></td>
                    <td class="text-right">$<?= number_format($f['total'], 2) ?></td>
                    <td class="text-center">
                        <button class="btn-info" onclick="window.open('imprimir_factura.php?id=<?= $f['id_factura'] ?>', '_blank')">
                            🖨️ Imprimir
                        </button>
                        
                        <?php if (!empty($f['correo'])): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="enviar_factura_id" value="<?= $f['id_factura'] ?>">
                            <button type="submit" class="btn-success">📧 Enviar</button>
                        </form>
                        <?php endif; ?>
                        
                        <button class="btn-danger" onclick="eliminarFactura(<?= $f['id_factura'] ?>)">
                            🗑️ Eliminar
                        </button>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="7" class="text-center">No hay facturas registradas</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
let productos = [];
let stockDisponible = {};

// Cargar stock disponible
<?php
mysqli_data_seek($productos, 0);
echo "stockDisponible = {";
while ($p = $productos->fetch_assoc()) {
    echo $p['id_producto'] . ": " . $p['cantidad_disponible'] . ",";
}
echo "};";
?>

function agregarProducto() {
    const select = document.getElementById("producto_select");
    const cantidadInput = document.getElementById("cantidad");
    
    if (!select.value) {
        alert("Por favor, seleccione un producto");
        return;
    }
    
    const id = parseInt(select.value);
    const cantidad = parseInt(cantidadInput.value);
    const nombre = select.options[select.selectedIndex].text.split(" - $")[0];
    const precio = parseFloat(select.options[select.selectedIndex].dataset.precio);
    const stockMax = parseInt(select.options[select.selectedIndex].dataset.stock);
    
    if (cantidad <= 0) {
        alert("La cantidad debe ser mayor a 0");
        return;
    }
    
    // Verificar stock disponible
    const productoExistente = productos.find(p => p.id === id);
    const cantidadActual = productoExistente ? productoExistente.cantidad : 0;
    
    if (cantidadActual + cantidad > stockMax) {
        alert(`Stock insuficiente. Disponible: ${stockMax}, En carrito: ${cantidadActual}`);
        return;
    }
    
    if (productoExistente) {
        productoExistente.cantidad += cantidad;
    } else {
        productos.push({ id, nombre, cantidad, precio });
    }
    
    actualizarTabla();
    
    // Limpiar formulario
    select.selectedIndex = 0;
    cantidadInput.value = 1;
}

function actualizarTabla() {
    const tbody = document.querySelector("#tabla_detalle tbody");
    tbody.innerHTML = "";
    
    let total = 0;
    
    productos.forEach((producto, index) => {
        const subtotal = producto.precio * producto.cantidad;
        total += subtotal;
        
        tbody.innerHTML += `
            <tr>
                <td>${producto.nombre}</td>
                <td class="text-center">${producto.cantidad}</td>
                <td class="text-right">${producto.precio.toFixed(2)}</td>
                <td class="text-right">${subtotal.toFixed(2)}</td>
                <td class="text-center">
                    <button type="button" class="btn-danger" onclick="eliminarProducto(${index})">
                        🗑️ Eliminar
                    </button>
                </td>
            </tr>
        `;
    });
    
    // Actualizar total
    document.getElementById("total_display").textContent = total.toFixed(2);
    document.getElementById("total_hidden").value = total.toFixed(2);
    document.getElementById("detalles_hidden").value = JSON.stringify(productos);
    
    // Calcular cambio
    calcularCambio();
}

function eliminarProducto(index) {
    if (confirm("¿Está seguro de eliminar este producto?")) {
        productos.splice(index, 1);
        actualizarTabla();
    }
}

function calcularCambio() {
    const total = parseFloat(document.getElementById("total_hidden").value) || 0;
    const recibido = parseFloat(document.getElementById("recibido").value) || 0;
    const cambio = recibido - total;
    
    document.getElementById("cambio_display").textContent = cambio.toFixed(2);
    
    // Cambiar color según el cambio
    const cambioSpan = document.getElementById("cambio_display");
    if (cambio < 0) {
        cambioSpan.style.color = "red";
    } else if (cambio > 0) {
        cambioSpan.style.color = "green";
    } else {
        cambioSpan.style.color = "black";
    }
}

function validarFormulario() {
    if (productos.length === 0) {
        alert("Debe agregar al menos un producto a la factura");
        return false;
    }
    
    const total = parseFloat(document.getElementById("total_hidden").value) || 0;
    const recibido = parseFloat(document.getElementById("recibido").value) || 0;
    
    if (total <= 0) {
        alert("El total debe ser mayor a 0");
        return false;
    }
    
    if (recibido < total) {
        alert("El monto recibido no puede ser menor al total de la factura");
        return false;
    }
    
    return confirm("¿Está seguro de registrar esta factura?");
}

function eliminarFactura(id) {
    if (confirm("¿Está seguro de eliminar esta factura? Esta acción no se puede deshacer.")) {
        window.location.href = "?eliminar=" + id;
    }
}

// Event listeners
document.getElementById("recibido").addEventListener("input", calcularCambio);
document.getElementById("cantidad").addEventListener("keypress", function(e) {
    if (e.key === "Enter") {
        e.preventDefault();
        agregarProducto();
    }
});

// Inicializar
document.addEventListener("DOMContentLoaded", function() {
    actualizarTabla();
});
</script>

</body>
</html>