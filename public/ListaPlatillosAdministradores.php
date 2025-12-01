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

// CARGAR PLATILLOS DESDE VistaPlatillos (agrupando ingredientes)
$sql = "
    SELECT 
        idPlatillo,
        Platillo,
        Descripcion,
        PrecioVenta,
        Imagen,
        Categoria,
        Ingrediente
    FROM VistaPlatillos
    ORDER BY idPlatillo
";

$result = $conn->query($sql);

/* ================================
   📦 OBTENER STOCK DE CADA PLATILLO
================================ */
$sqlStock = "
    SELECT idPlatillo, SUM(CantidadDisponible) AS Stock
    FROM DetallePlatillo
    GROUP BY idPlatillo
";
$resStock = $conn->query($sqlStock);

$stockPlatillos = [];
while ($st = $resStock->fetch_assoc()) {
    $stockPlatillos[$st["idPlatillo"]] = $st["Stock"];
}

// AGRUPAR PLATILLOS POR ID Y ACUMULAR SUS INGREDIENTES
$platillos = [];

while ($row = $result->fetch_assoc()) {
    $id = $row["idPlatillo"];

    if (!isset($platillos[$id])) {
        // Crear platillo si no existe
        $platillos[$id] = [
            "idPlatillo" => $row["idPlatillo"],
            "Platillo" => $row["Platillo"],
            "Descripcion" => $row["Descripcion"],
            "PrecioVenta" => $row["PrecioVenta"],
            "Imagen" => $row["Imagen"],
            "Categoria" => $row["Categoria"],
            "Stock" => $stockPlatillos[$id] ?? 0,   // <--- STOCK AGREGADO
            "Ingredientes" => []
        ];
    }

    // Agregar ingrediente
    if (!in_array($row["Ingrediente"], $platillos[$id]["Ingredientes"])) {
        $platillos[$id]["Ingredientes"][] = $row["Ingrediente"];
    }
}

//CATEGORÍAS PARA EL FILTRO
$sqlCategorias = "SELECT idCategoria, Nombre FROM VistaCategorias";
$resultCategorias = $conn->query($sqlCategorias);

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ChilMole — Platillos</title>
  <link rel="stylesheet" href="ListaPlatillosAdministradores.css">
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
        <h2 class="page-title">Lista de Platillos</h2>

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

    <!-- SEARCH + FILTERS -->
    <section class="filters">

        <input type="search" id="searchInput" class="search-input" placeholder="Buscar platillo...">

        <select id="filterCategoria" class="filter-select">
            <option value="">Todas las categorías</option>

            <?php while ($cat = $resultCategorias->fetch_assoc()): ?>
                <option value="<?= $cat["Nombre"] ?>"><?= $cat["Nombre"] ?></option>
            <?php endwhile; ?>

        </select>

        <a href="AgregarPlatilloAdministrador.php" class="btn-add filter-btn">
            Agregar Platillo
        </a>
        <a href="ListaCategoriasAdministradores.php" class="btn-add filter-btn">
            Ver Categoria
        </a>
    </section>

    <!-- GRID -->
    <section id="gridPlatillos" class="grid-platillos">

<?php
foreach ($platillos as $p):

    $img = !empty($p["Imagen"])
           ? "Imagenes/Platillos/" . $p["Imagen"]
           : "Imagenes/no-image.png";
?>

    <div class="card-flip"
         data-nombre="<?= strtolower($p['Platillo']) ?>"
         data-categoria="<?= $p['Categoria'] ?>">

        <div class="card-inner">

            <!-- FRONT -->
            <div class="card-front">
                <img src="<?= $img ?>" class="platillo-img">

                <h3 class="platillo-nombre"><?= $p["Platillo"] ?></h3>
                <p class="platillo-precio">$<?= $p["PrecioVenta"] ?></p>
                <p class="platillo-stock">Disponible: <?= $p["Stock"] ?></p> <!-- STOCK MOSTRADO -->

                <div class="card-actions">
                    <a href="EditarPlatilloAdministrador.php?id=<?= $p['idPlatillo'] ?>" class="btn-edit">✏️</a>
                    <a href="EliminarPlatilloAdministrador.php?id=<?= $p['idPlatillo'] ?>" class="btn-delete">🗑️</a>
                </div>
            </div>

            <!-- BACK -->
            <div class="card-back">
                <button class="btn-close">✖️</button>

                <h3 class="platillo-nombre"><?= $p["Platillo"] ?></h3>

                <p class="receta-title">Ingredientes:</p>
                <ul>
                    <?php foreach ($p["Ingredientes"] as $ing): ?>
                        <li><?= $ing ?></li>
                    <?php endforeach; ?>
                </ul>

                <p class="receta-title">Descripción:</p>
                <p><?= $p["Descripcion"] ?></p>

            </div>

        </div>
    </div>

<?php endforeach; ?>

    </section>


    
  </main>

</div>


<!-- ==============================
     SCRIPT BUSCADOR + FILTROS
     ============================== -->
<script>
const searchInput = document.getElementById("searchInput");
const filterCategoria = document.getElementById("filterCategoria");
const cards = document.querySelectorAll(".card-flip");

function filtrar() {
    let texto = searchInput.value.toLowerCase();
    let categoria = filterCategoria.value;

    cards.forEach(card => {
        let nombre = card.dataset.nombre;
        let cat = card.dataset.categoria;

        let coincideNombre = nombre.includes(texto);
        let coincideCategoria = categoria === "" || categoria === cat;

        card.style.display = (coincideNombre && coincideCategoria) ? "block" : "none";
    });
}

searchInput.addEventListener("keyup", filtrar);
filterCategoria.addEventListener("change", filtrar);

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
