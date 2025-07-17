<?php
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

// El usuario está logueado, puede continuar
// (Removemos la restricción de solo admin para permitir acceso a cualquier usuario logueado)

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
    $doc_cliente = trim($_POST['doc_cliente']);
    $nombre = trim($_POST['nombre']);
    $correo = strtolower(trim($_POST['correo']));
    $telefono = trim($_POST['telefono']);
    $contraseña = password_hash(trim($_POST['contraseña']), PASSWORD_DEFAULT);

    if (
        empty($doc_cliente) || empty($nombre) || empty($correo) ||
        empty($telefono) || empty($contraseña)
    ) {
        $mensaje = "⚠️ Todos los campos son obligatorios";
        $mensaje_tipo = "mensaje-error";
    } elseif (!preg_match("/^\d{6,}$/", $doc_cliente)) {
        $mensaje = "⚠️ El documento debe contener solo números (mínimo 6 dígitos)";
        $mensaje_tipo = "mensaje-error";
    } elseif (!preg_match("/^[a-zA-ZÁÉÍÓÚáéíóúÑñ\s]{2,60}$/", $nombre)) {
        $mensaje = "⚠️ El nombre solo debe contener letras y espacios";
        $mensaje_tipo = "mensaje-error";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "⚠️ Formato de correo no válido";
        $mensaje_tipo = "mensaje-error";
    } elseif (!preg_match("/^\d{10,10}$/", $telefono)) {
        $mensaje = "⚠️ El teléfono debe tener 10 dígitos";
        $mensaje_tipo = "mensaje-error";
    } else {
        // Verificar si el documento ya existe
        $verificarDoc = $enlace->prepare("SELECT doc_cliente FROM cliente WHERE doc_cliente = ?");
        if ($verificarDoc) {
            $verificarDoc->bind_param("s", $doc_cliente);
            $verificarDoc->execute();
            $resultadoDoc = $verificarDoc->get_result();
            
            if ($resultadoDoc->num_rows > 0) {
                $mensaje = "❌ El documento ya está registrado";
                $mensaje_tipo = "mensaje-error";
            } else {
                // Verificar si el correo ya existe
                $verificarCorreo = $enlace->prepare("SELECT correo FROM cliente WHERE correo = ?");
                if ($verificarCorreo) {
                    $verificarCorreo->bind_param("s", $correo);
                    $verificarCorreo->execute();
                    $resultado = $verificarCorreo->get_result();

                    if ($resultado->num_rows > 0) {
                        $mensaje = "❌ El correo ya está registrado";
                        $mensaje_tipo = "mensaje-error";
                    } else {
                        $stmt = $enlace->prepare("INSERT INTO cliente (doc_cliente, nom_cliente, correo, telefono, contraseña) VALUES (?, ?, ?, ?, ?)");
                        if ($stmt) {
                            $stmt->bind_param("sssss", $doc_cliente, $nombre, $correo, $telefono, $contraseña);
                            if ($stmt->execute()) {
                                $mensaje = "✅ Cliente registrado exitosamente";
                                $mensaje_tipo = "mensaje-exito";
                            } else {
                                $mensaje = "❌ Error al registrar: " . $stmt->error;
                                $mensaje_tipo = "mensaje-error";
                            }
                            $stmt->close();
                        } else {
                            $mensaje = "❌ Error en la preparación de la consulta";
                            $mensaje_tipo = "mensaje-error";
                        }
                    }
                    $verificarCorreo->close();
                } else {
                    $mensaje = "❌ Error en la verificación del correo";
                    $mensaje_tipo = "mensaje-error";
                }
            }
            $verificarDoc->close();
        } else {
            $mensaje = "❌ Error en la verificación del documento";
            $mensaje_tipo = "mensaje-error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Clientes | Sultipan</title>
    <link rel="stylesheet" href="clientes.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
</head>
<body>
    <!-- Botón para editar registros -->
    <div class="top-right-button">
        <a href="crud_clientes/crud_clientes.php" class="btn-editar">
            <ion-icon name="create-outline"></ion-icon> 
            <span>Editar Clientes</span>
        </a>
    </div>

    <!-- Botón para regresar -->
    <div class="logo_boton">
        <a href="../admin.php" class="btn_regresar">
            <ion-icon name="arrow-back-outline"></ion-icon>
            <span>Regresar</span>
        </a>
    </div>

    <!-- Contenedor principal -->
    <section aria-label="Formulario de Registro de Clientes">
        <div class="form-box">
            <div class="form-value">
                <h1>Registro de Clientes</h1>

                <!-- Mostrar mensajes -->
                <?php if (!empty($mensaje)): ?>
                    <div class="<?php echo $mensaje_tipo; ?>"><?php echo $mensaje; ?></div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate>
                    <div class="inputbox">
                        <input type="text" name="doc_cliente" required placeholder=" " maxlength="15" pattern="[0-9]{6,15}">
                        <label>Documento</label>
                        <ion-icon name="card-outline"></ion-icon>
                    </div>

                    <div class="inputbox">
                        <input type="text" name="nombre" required placeholder=" " maxlength="60" pattern="[a-zA-ZÁÉÍÓÚáéíóúÑñ\s]{2,60}">
                        <label>Nombre Completo</label>
                        <ion-icon name="person-outline"></ion-icon>
                    </div>

                    <div class="inputbox">
                        <input type="email" name="correo" required placeholder=" " maxlength="100">
                        <label>Correo Electrónico</label>
                        <ion-icon name="mail-outline"></ion-icon>
                    </div>

                    <div class="inputbox">
                        <input type="tel" name="telefono" required placeholder=" " maxlength="10" pattern="[0-9]{10}">
                        <label>Teléfono</label>
                        <ion-icon name="call-outline"></ion-icon>
                    </div>

                    <div class="inputbox">
                        <input type="password" name="contraseña" required placeholder=" " minlength="6">
                        <label>Contraseña</label>
                        <ion-icon name="lock-closed-outline"></ion-icon>
                    </div>

                    <input type="submit" name="registrar" value="Registrar Cliente">
                    <input type="reset" value="Limpiar Campos">
                </form>
            </div>
        </div>
    </section>

    <script>
        // Mejorar la experiencia del usuario
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus en el primer campo
            const firstInput = document.querySelector('input[name="doc_cliente"]');
            if (firstInput) {
                firstInput.focus();
            }

            // Validación en tiempo real
            const docInput = document.querySelector('input[name="doc_cliente"]');
            const telInput = document.querySelector('input[name="telefono"]');
            
            // Solo números para documento
            docInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
            
            // Solo números para teléfono
            telInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            // Capitalizar nombre
            const nombreInput = document.querySelector('input[name="nombre"]');
            nombreInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^a-zA-ZÁÉÍÓÚáéíóúÑñ\s]/g, '');
            });

            // Convertir correo a minúsculas
            const correoInput = document.querySelector('input[name="correo"]');
            correoInput.addEventListener('input', function() {
                this.value = this.value.toLowerCase();
            });

            // Ocultar mensaje después de 5 segundos
            const mensaje = document.querySelector('.mensaje-error, .mensaje-exito');
            if (mensaje) {
                setTimeout(() => {
                    mensaje.style.opacity = '0';
                    mensaje.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        mensaje.style.display = 'none';
                    }, 300);
                }, 5000);
            }

            // Confirmación antes de limpiar
            const resetBtn = document.querySelector('input[type="reset"]');
            resetBtn.addEventListener('click', function(e) {
                if (!confirm('¿Estás seguro de que deseas limpiar todos los campos?')) {
                    e.preventDefault();
                }
            });

            // Validación de formulario antes de enviar
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const inputs = form.querySelectorAll('input[required]');
                let valid = true;
                
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        valid = false;
                        input.style.borderColor = '#FF6B6B';
                    } else {
                        input.style.borderColor = '#E5E7EB';
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    alert('Por favor, completa todos los campos requeridos.');
                }
            });
        });
    </script>
</body>
</html>