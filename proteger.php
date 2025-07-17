<?php
// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔒 Evitar que se pueda volver a la página después del logout (botón atrás)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// 🔐 Verificar que haya un usuario logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php"); // Cambia a "iniciopagina.php" si prefieres
    exit;
}
?>
