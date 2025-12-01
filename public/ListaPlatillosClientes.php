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

/* ========================================
   Obtener la categoría seleccionada
======================================== */
$idCategoria = isset($_GET['idCategoria']) ? intval($_GET['idCategoria']) : 0;
if ($idCategoria <= 0) {
    die("Categoría no válida.");
}

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
   🔥 Obtener platillos de la categoría
======================================== */
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
    WHERE idCategoria = ?
    ORDER BY Platillo
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idCategoria);
$stmt->execute();
$result = $stmt->get_result();

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
            "Ingredientes" => []
        ];
    }

    if(!in_array($row["Ingrediente"], array_column($platillos[$id]["Ingredientes"], "Ingrediente"))) {
        $platillos[$id]["Ingredientes"][] = ["Ingrediente" => $row["Ingrediente"]];
    }
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ChilMole — Platillos Cliente</title>
<link rel="stylesheet" href="ListaPlatillosClientes.css">
<style>
.alert { padding:10px; margin:10px 0; background:#d4edda; color:#155724; border-radius:5px; }
</style>
</head>

<body>
<div class="app">

  <!-- MAIN -->
  <main class="main">

    <!-- TOPBAR -->
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

    <?php if(!empty($msg)): ?>
      <div class="alert"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- SEARCH -->
    <section class="filters">
      <input type="search" id="searchInput" class="search-input" placeholder="Buscar platillo...">
    </section>

    <!-- GRID PLATILLOS -->
    <section id="gridPlatillos" class="grid-platillos">

    <?php foreach ($platillos as $p): ?>
      <div class="card-flip" data-nombre="<?= strtolower($p['Platillo']) ?>">

        <div class="card-inner">

          <!-- FRONT -->
          <div class="card-front">
            <img src="<?= htmlspecialchars($p['Imagen']) ?>" class="platillo-img">
            <h3 class="platillo-nombre"><?= htmlspecialchars($p["Platillo"]) ?></h3>
            <p class="platillo-precio">$<?= number_format($p["PrecioVenta"], 2) ?></p>
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
            <h3 class="platillo-nombre"><?= htmlspecialchars($p["Platillo"]) ?></h3>
            <p class="receta-title">Ingredientes:</p>
            <ul>
              <?php foreach ($p["Ingredientes"] as $ing): ?>
                <li><?= htmlspecialchars($ing["Ingrediente"]) ?></li>
              <?php endforeach; ?>
            </ul>
            <p class="receta-title">Descripción:</p>
            <p><?= htmlspecialchars($p["Descripcion"]) ?></p>
          </div>

        </div>
      </div>
    <?php endforeach; ?>

    </section>


    
  </main>
</div>

<script>
// Flip tarjetas
document.querySelectorAll(".card-flip").forEach(card => {
    card.addEventListener("click", () => card.classList.add("active"));
});
document.querySelectorAll(".btn-close").forEach(btn => {
    btn.addEventListener("click", e => {
        e.stopPropagation();
        btn.closest(".card-flip").classList.remove("active");
    });
});

// Búsqueda por nombre
const searchInput = document.getElementById("searchInput");
const cards = document.querySelectorAll(".card-flip");

searchInput.addEventListener("input", () => {
    const texto = searchInput.value.toLowerCase();
    cards.forEach(card => {
        const nombre = card.dataset.nombre;
        card.style.display = nombre.includes(texto) ? "block" : "none";
    });
});
</script>

</body>
</html>
