<?php
session_start();

if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 2) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar id del usuario logueado
$conn->query("SET @id_usuario_actual = " . intval($idPersona));

/* ========================================
   ➕ AGREGAR PLATILLO A LA BOLSA
======================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idPlatillo'])) {
    $idPlatillo = intval($_POST['idPlatillo']);
    $stmt = $conn->prepare("CALL AgregarPlatilloCarrito(?, ?)");
    $stmt->bind_param("ii", $idPersona, $idPlatillo);

    if ($stmt->execute()) {
        $msg = "Platillo agregado a la bolsa ✔️";
    } else {
        $msg = "Error al agregar al carrito: " . $conn->error;
    }
    $stmt->close();
}


/* ========================================
   🔥 STOCK POR PLATILLO
======================================== */
$sqlStock = "
    SELECT idPlatillo, SUM(CantidadDisponible) AS Stock
    FROM DetallePlatillo
    GROUP BY idPlatillo
";
$resStock = $conn->query($sqlStock);

$stockPlatillos = [];
if ($resStock) {
    while ($st = $resStock->fetch_assoc()) {
        $stockPlatillos[$st["idPlatillo"]] = $st["Stock"];
    }
}


/* ========================================
   🔥 AGRUPAR PLATILLOS
======================================== */
$sql = "
    SELECT 
        idPlatillo,
        Platillo,
        Descripcion,
        PrecioVenta,
        Imagen,
        Categoria,
        Ingrediente,
        CantidadRequerida,
        UnidadMedida
    FROM VistaPlatillos
    ORDER BY idPlatillo
";

$result = $conn->query($sql);

$platillos = [];

while ($row = $result->fetch_assoc()) {

    $id = $row["idPlatillo"];

    if (!isset($platillos[$id])) {
        $img = !empty($row["Imagen"])
               ? "Imagenes/Platillos/" . $row["Imagen"]
               : "Imagenes/no-image.png";

        $platillos[$id] = [
            "idPlatillo" => $row["idPlatillo"],
            "Platillo" => $row["Platillo"],
            "Descripcion" => $row["Descripcion"],
            "PrecioVenta" => $row["PrecioVenta"],
            "Imagen" => $img,
            "Categoria" => $row["Categoria"],
            "Stock" => $stockPlatillos[$id] ?? 0,   // ← AGREGADO
            "Ingredientes" => []
        ];
    }

    $platillos[$id]["Ingredientes"][] = [
        "Ingrediente" => $row["Ingrediente"],
        "Cantidad" => $row["CantidadRequerida"],
        "Unidad" => $row["UnidadMedida"]
    ];
}


/* ========================================
   📌 CATEGORÍAS PARA FILTRO
======================================== */
$categorias = [];
$resultCategorias = $conn->query("SELECT idCategoria, Nombre FROM VistaCategorias");
if ($resultCategorias) {
    $categorias = $resultCategorias->fetch_all(MYSQLI_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ChilMole — Menú</title>
  <link rel="stylesheet" href="ListaPlatillosEmpleados.css">
  <style>
    .alert { padding:10px; margin:10px 0; background:#d4edda; color:#155724; border-radius:5px; }
  </style>
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
            <li class="nav-item"><a href="InicioEmpleado.php">Inicio</a></li>
            <li class="nav-item active"><a href="ListaPlatillosEmpleados.php">Platillos</a></li>
            <li class="nav-item"><a href="ListaPedidosEmpleados.php">Pedidos</a></li>
            <li class="nav-item"><a href="ListaVentasEmpleados.php">Ventas</a></li>
            <li class="nav-item"><a href="Login.php">Salir</a></li>
        </ul>
    </nav>
</aside>

<!-- MAIN -->
<main class="main">

<header class="topbar">
    <h2 class="page-title">Menú de Platillos</h2>

    <div class="top-actions">
        <a href="BolsaEmpleados.php">
             <button class="icon-btn">👜</button>
        </a>


        <div class="profile">
            <div>
                <div class="profile-name"><?= $_SESSION['Nombre'] ?? 'Empleado'; ?></div>
                <div class="profile-role">Empleado</div>
            </div>
            <div class="avatar">👨‍🍳</div>
        </div>
    </div>
</header>

<?php if(!empty($msg)): ?>
<div class="alert"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- SEARCH & FILTERS -->
<section class="filters">
    <input type="search" id="searchInput" class="search-input" placeholder="Buscar platillo...">

    <select id="categoryFilter" class="filter-select">
        <option value="">Todas las categorías</option>

        <?php foreach($categorias as $cat): ?>
            <option value="<?= $cat["Nombre"] ?>"><?= $cat["Nombre"] ?></option>
        <?php endforeach; ?>
    </select>
</section>

<!-- GRID PLATILLOS -->
<section id="gridPlatillos" class="grid-platillos">

<?php foreach ($platillos as $p): ?>

<div class="card-flip"
     data-nombre="<?= strtolower($p['Platillo']) ?>"
     data-categoria="<?= $p['Categoria'] ?>">

    <div class="card-inner">

        <!-- FRONT -->
        <div class="card-front">
            <img src="<?= htmlspecialchars($p['Imagen']) ?>" class="platillo-img">

            <h3 class="platillo-nombre"><?= $p["Platillo"] ?></h3>
            <p class="platillo-precio">$<?= $p["PrecioVenta"] ?></p>

            <!-- STOCK MOSTRADO -->
            <p class="platillo-stock">Disponible: <?= $p["Stock"] ?></p>

            <div class="card-actions">
                <form method="POST">
                    <input type="hidden" name="idPlatillo" value="<?= $p['idPlatillo']; ?>">
                    <button class="btn-add-bag" type="submit">🛒 Agregar</button>
                </form>
            </div>
        </div>

        <!-- BACK -->
        <div class="card-back">
            <button class="btn-close">✖️</button>

            <h3 class="platillo-nombre"><?= $p["Platillo"] ?></h3>

            <p class="receta-title">Ingredientes:</p>
            <ul>
                <?php foreach ($p["Ingredientes"] as $ing): ?>
                    <li><?= $ing["Ingrediente"] ?></li>
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

<script>
// Activar flip
document.querySelectorAll(".card-flip").forEach(card => {
    card.addEventListener("click", () => card.classList.add("active"));
});
document.querySelectorAll(".btn-close").forEach(btn => {
    btn.addEventListener("click", e => {
        e.stopPropagation();
        btn.closest(".card-flip").classList.remove("active");
    });
});

// Filtro
const searchInput = document.getElementById("searchInput");
const categoryFilter = document.getElementById("categoryFilter");
const cards = document.querySelectorAll(".card-flip");

function filtrar() {
    let texto = searchInput.value.toLowerCase();
    let categoria = categoryFilter.value;

    cards.forEach(card => {
        let n = card.dataset.nombre;
        let c = card.dataset.categoria;

        let coincideNombre = n.includes(texto);
        let coincideCategoria = categoria === "" || categoria === c;

        card.style.display = (coincideNombre && coincideCategoria) ? "block" : "none";
    });
}

searchInput.addEventListener("input", filtrar);
categoryFilter.addEventListener("change", filtrar);
</script>

</body>
</html>
