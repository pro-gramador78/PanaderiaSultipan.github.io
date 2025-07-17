<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit();
}

// Incluir conexión a la base de datos
require 'conexion.php';

include 'proteger.php';


// Obtener datos actualizados del cliente desde la base de datos
$correo = $_SESSION['usuario'];
$sql = "SELECT nom_cliente, foto, telefono FROM cliente WHERE correo = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();
    // Actualizar datos en sesión
    $_SESSION['cliente'] = [
        'nombre' => $usuario['nom_cliente'],
        'foto' => $usuario['foto'] ?? 'img/default.jpg',
        'telefono' => $usuario['telefono'] ?? ''
    ];
} else {
    // Datos por defecto si no se encuentra el usuario
    $_SESSION['cliente'] = [
        'nombre' => 'Invitado',
        'foto' => 'img/default.jpg',
        'telefono' => ''
    ];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" href="iniciopagina.css" />
<title>Cliente - Panadería Sultipan</title>
<style>
  body {
    margin: 0;
    font-family: 'Roboto', sans-serif;
    background: linear-gradient(135deg, #fceabb 0%, #f8b500 100%);
    color: #5b3a00;
    min-height: 100vh;
  }
  
  header {
    position: relative;
    background-color: #d97e00;
    padding: 1rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  }
  
  .logo {
    font-family: 'Pacifico', cursive;
    color: white;
    font-size: 2rem;
    text-decoration: none;
    transition: transform 0.3s;
  }
  
  .logo:hover {
    transform: scale(1.05);
  }
  
  .container-menu {
    display: flex;
    gap: 1.5rem;
  }
  
  .container-menu a {
    color: white;
    text-decoration: none;
    font-weight: 700;
    padding: 0.5rem 0;
    position: relative;
    transition: color 0.3s;
  }
  
  .container-menu a:hover {
    color: #fceabb;
  }
  
  .usuario-icono {
    width: 3.3rem;
    height: 3.3rem;
    border-radius: 50%;
    border: 3px solid white;
    cursor: pointer;
    object-fit: cover;
    transition: all 0.3s ease;
  }
  
  .usuario-icono:hover {
    transform: scale(1.1);
    box-shadow: 0 0 15px rgba(255,255,255,0.7);
  }
  
  .menu-desplegable {
    position: fixed;
    top: 80px;
    right: 20px;
    width: 280px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 30px rgba(0,0,0,0.15);
    padding: 1.5rem;
    opacity: 0;
    pointer-events: none;
    transform: translateY(-20px);
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    z-index: 1000;
  }
  
  .menu-desplegable.abrir-menu {
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0);
  }
  
  .emboltorio-menu {
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
  }
  
  .usuario-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding-bottom: 1rem;
  }
  
  .usuario-info img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #d97e00;
  }
  
  .usuario-info h2 {
    font-family: 'Pacifico', cursive;
    margin: 0;
    color: #5b3a00;
    font-size: 1.3rem;
  }
  
  .menu-opciones {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
  }
  
  .menu-opciones div {
    display: flex;
    align-items: center;
    padding: 0.8rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
  }
  
  .menu-opciones div:hover {
    background-color: #f5f5f5;
  }
  
  .menu-opciones div p {
    margin: 0;
    flex-grow: 1;
  }
  
  .cerrar-sesion {
    color: #d97e00;
    font-weight: 700;
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid #eee;
  }
  
  /* [Resto de tus estilos CSS] */
</style>
</head>
<body>
<header>
  <a href="#" class="logo" aria-label="Inicio Sultipan">Sultipan</a>
  <nav class="container-menu" aria-label="Navegación principal">
    <a href="#productos">Productos</a>
    <a href="#mision">Misión</a>
    <a href="#vision">Visión</a>
  </nav>
  <img 
    src="<?= htmlspecialchars($_SESSION['cliente']['foto']) ?>" 
    alt="Foto de perfil de <?= htmlspecialchars($_SESSION['cliente']['nombre']) ?>"
    class="usuario-icono" 
    aria-haspopup="true"
    aria-expanded="false"
    aria-controls="menuUsuario"
    onerror="this.src='img/default.jpg'"
  />
</header>

<!-- Menú desplegable -->
<div id="menuUsuario" class="menu-desplegable" role="menu" aria-hidden="true">
  <div class="emboltorio-menu">
    <div class="usuario-info">
      <img src="<?= htmlspecialchars($_SESSION['cliente']['foto']) ?>" 
           alt="Foto de perfil"
           onerror="this.src='img/default.jpg'" />
      <h2><?= htmlspecialchars($_SESSION['cliente']['nombre']) ?></h2>
    </div>
    <div class="menu-opciones">
      <div role="menuitem" tabindex="0">
        <p>Enviar comentarios</p>
        <span>&gt;</span>
      </div>
      <div role="menuitem" tabindex="0" onclick="location.href='configuracion.php';">
        <p>Configuración</p>
        <span>&gt;</span>
      </div>
      <div role="menuitem" tabindex="0">
        <p>Ayuda</p>
        <span>&gt;</span>
      </div>
      <div role="menuitem" tabindex="0" class="cerrar-sesion" onclick="location.href='logout.php';">
        <p>Cerrar sesión</p>
        <span>&gt;</span>
      </div>
    </div>
  </div>
</div>

<!-- [Resto de tu contenido HTML] -->
<!-- Resto de tu contenido -->
<section class="hero" role="banner" aria-labelledby="hero-title hero-desc">
  <h1 id="hero-title">Panadería y Cafetería Sultipan</h1>
  <p id="hero-desc">Deléitate con nuestros panes artesanales y sabores tradicionales de la panadería colombiana.</p>
  <a href="#productos" class="btn" role="button" aria-label="Ver nuestros productos">Ver nuestros productos</a>
</section>

<section id="productos" class="productos" aria-label="Nuestros productos de panadería colombiana">
  <h2>Productos Destacados</h2>
  <div class="productos-grid" role="list">
    <!-- Aquí tus productos -->
  </div>
</section>

<div style="text-align: center;">
  <a href="login.php" class="btn" role="button" aria-label="Deseas Comprar Productos?">Deseas Comprar Productos?</a>
</div>

<section id="mision" class="mision" aria-labelledby="mision-title">
  <h2 id="mision-title">Nuestra Misión</h2>
  <p>Brindar productos de panadería y cafetería de alta calidad...</p>
</section>

<section id="vision" class="vision" aria-labelledby="vision-title">
  <h2 id="vision-title">Nuestra Visión</h2>
  <p>Ser la panadería y cafetería referente en la región...</p>
</section>

<footer>
  <small>&copy; 2024 Panadería y Cafetería Sultipan. Todos los derechos reservados.</small>
  <form action="#" method="post">
    <input type="email" placeholder="Tu correo electrónico" required aria-label="Correo electrónico" />
    <input type="submit" value="Suscribirse" />
  </form>
</footer>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const usuarioIcono = document.querySelector('.usuario-icono');
  const menu = document.getElementById('menuUsuario');

  if (usuarioIcono && menu) {
    // Abrir/cerrar menú al hacer clic en el icono
    usuarioIcono.addEventListener('click', function(e) {
      e.stopPropagation();
      const isOpen = menu.classList.toggle('abrir-menu');
      usuarioIcono.setAttribute('aria-expanded', isOpen);
      menu.setAttribute('aria-hidden', !isOpen);
    });

    // Cerrar menú al hacer clic fuera
    document.addEventListener('click', function() {
      if (menu.classList.contains('abrir-menu')) {
        menu.classList.remove('abrir-menu');
        usuarioIcono.setAttribute('aria-expanded', false);
        menu.setAttribute('aria-hidden', true);
      }
    });

    // Cerrar menú con tecla Escape
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && menu.classList.contains('abrir-menu')) {
        menu.classList.remove('abrir-menu');
        usuarioIcono.setAttribute('aria-expanded', false);
        menu.setAttribute('aria-hidden', true);
        usuarioIcono.focus();
      }
    });
  }
});
</script>
</body>
</html>