<?php
session_start();

if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar id del usuario logueado
$conn->query("SET @id_usuario_actual = " . intval($idPersona));

// CARGAR CATEGORÍAS DESDE VistaCategorias
$sql = "SELECT * FROM VistaCategorias ORDER BY idCategoria";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ChilMole — Categorías</title>
  <link rel="stylesheet" href="ListaCategoriasAdministradores.css">
</head>
<body>

<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-img">
                <img src="Imagenes/Lele.png" alt="Logo ChilMole" />
            </div>
            <div>
                <div class="brand-title">ChilMole</div>
                <div class="brand-sub">Moles Tradicionales</div>
            </div>
        </div>

        <nav class="nav">
            <ul>
                <li class="nav-item"><a href="InicioAdministradores.php">Inicio</a></li>
                <li class="nav-item active"><a href="ListaPlatillosAdministradores.php">Platillos</a></li>
                <li class="nav-item"><a href="ListaRecetasAdministradores.php">Recetas</a></li>
                <li class="nav-item"><a href="ListaIngredienteAdministradores.php">Ingredientes</a></li>
                <li class="nav-item"><a href="ListaPedidosAdministradores.php">Pedidos</a></li>
                <li class="nav-item"><a href="ListaUsuariosAdministradores.php">Usuarios</a></li>
                <li class="nav-item"><a href="ListaVentasAdministradores.php">Ventas</a></li>
                <li class="nav-item"><a href="ListaNotificacionesAdministradores.php">Notificaciones</a></li>
                <li class="nav-item"><a href="Login.php">Salir</a></li>
            </ul>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main">

        <!-- TOP BAR -->
        <header class="topbar">
            <h2 class="page-title">Lista de Categorías</h2>
            <div class="top-actions">
                <div class="profile">
                    <div>
                        <div class="profile-name">Administrador</div>
                        <div class="profile-role">Superadmin</div>
                    </div>
                    <div class="avatar">👩‍🍳</div>
                </div>
            </div>
        </header>

        <!-- BOTÓN AGREGAR CATEGORÍA -->
        <section class="filters">
            <a href="AgregarCategoriaAdministradores.php" class="btn-add filter-btn">
                Agregar Categoría
            </a>
        </section>

        <!-- GRID DE CATEGORÍAS -->
        <section class="grid-platillos">
            <?php while($cat = $result->fetch_assoc()): 
                $img = !empty($cat["Imagen"]) ? "Imagenes/Categoria/" . $cat["Imagen"] : "Imagenes/no-image.png";
            ?>
            <div class="card-flip" data-nombre="<?= strtolower($cat['Nombre']) ?>">
                <div class="card-inner">

                    <!-- FRONT -->
                    <div class="card-front">
                        <img src="<?= $img ?>" class="platillo-img">
                        <h3 class="platillo-nombre"><?= $cat["Nombre"] ?></h3>
                        <p class="platillo-precio">Platillos: <?= $cat["TotalPlatillos"] ?></p>

                        <div class="card-actions">
                            <a href="EditarCategoriaAdministradores.php?id=<?= $cat['idCategoria'] ?>" class="btn-edit">✏️</a>
                            <a href="EliminarCategoriaAdministradores.php?id=<?= $cat['idCategoria'] ?>" class="btn-delete">🗑️</a>
                        </div>
                    </div>

                    <!-- BACK -->
                    <div class="card-back">
                        <button class="btn-close">✖️</button>
                        <h3 class="platillo-nombre"><?= $cat["Nombre"] ?></h3>
                        <p class="receta-title">Descripción:</p>
                        <p><?= $cat["Descripcion"] ?></p>
                        <p class="receta-title">Total de platillos:</p>
                        <p><?= $cat["TotalPlatillos"] ?></p>
                    </div>

                </div>
            </div>
            <?php endwhile; ?>
        </section>


    
    </main>

</div>

<script>
document.querySelectorAll(".card-flip").forEach(card => {
  card.addEventListener("click", () => {
    card.classList.add("active");
  });
});

document.querySelectorAll(".btn-close").forEach(btn => {
  btn.addEventListener("click", (e) => {
    e.stopPropagation();
    btn.closest(".card-flip").classList.remove("active");
  });
});
</script>

</body>
</html>
