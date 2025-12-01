<?php
session_start();

// Verificar si el usuario está logueado y es cliente
if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 3) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado
$conn->query("SET @id_usuario_actual = " . intval($idPersona));

// Obtener categorías desde la vista VistaCategorias
$sql = "SELECT * FROM VistaCategorias ORDER BY Nombre";
$result = $conn->query($sql);

$categorias = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
    $result->free();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ChilMole — Inicio Cliente</title>
  <link rel="stylesheet" href="InicioClientes.css" />
</head>
<body>
  <div class="app">

    <!-- MAIN -->
    <main class="main">

      <!-- TOP BAR -->
      <header class="topbar">
        <div class="top-buttons">
          <a href="InicioClientes.php" class="btn-top">Inicio</a>
          <a href="ListaPedidosClientes.php" class="btn-top">Mis Pedidos</a>
          <a href="BolsaClientes.php" class="btn-top">Mi Bolsa</a>
          <a href="Login.php" class="btn-top">Salir</a>
        </div>

        <div class="profile">
          <div>
            <div class="profile-name"><?= htmlspecialchars($_SESSION['Nombre'] ?? 'Cliente'); ?></div>
            <div class="profile-role">Cliente</div>
          </div>
          <div class="avatar">🧑‍🍳</div>
        </div>
      </header>

      <!-- CATEGORÍAS -->
      <section class="categories-section">
        <h2>Categorías de Platillos</h2>
        <div class="categories-grid">
          <?php foreach ($categorias as $cat): ?>
            <?php 
              $img = !empty($cat['Imagen']) ? "Imagenes/Categoria/" . $cat['Imagen'] : "Imagenes/no-image.png";
            ?>
            <a href="ListaPlatillosClientes.php?idCategoria=<?= intval($cat['idCategoria']); ?>" class="category-card">
              <img src="<?= htmlspecialchars($img); ?>" alt="<?= htmlspecialchars($cat['Nombre']); ?>" />
              <div class="category-name"><?= htmlspecialchars($cat['Nombre']); ?> (<?= intval($cat['TotalPlatillos']); ?>)</div>
            </a>
          <?php endforeach; ?>
        </div>

    
      </section>

    </main>
  </div>
</body>
</html>
