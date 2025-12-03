<?php
session_start();

if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

require_once "../includes/conexion.php";

// Asignar el id del usuario logueado
$conn->query("SET @id_usuario_actual = " . intval($idPersona));


// ===========================================
// CONSULTA PRINCIPAL DE RECETAS
// ===========================================
$sql = "
SELECT 
    r.idReceta,
    r.Instrucciones,
    r.FechaCreacion,
    CONCAT(pe.Nombre, ' ', pe.ApellidoPaterno) AS Creador
FROM Receta r
INNER JOIN Persona pe ON r.idPersona = pe.idPersona
ORDER BY r.FechaCreacion DESC
";

$result = $conn->query($sql);


// ===========================================
// CONSULTA DE INGREDIENTES POR RECETA
// ===========================================
$sqlDetalles = "
SELECT 
    idReceta,
    Ingrediente,
    CantidadRequerida,
    UnidadMedida
FROM VistaRecetas
ORDER BY idReceta
";

$detallesResult = $conn->query($sqlDetalles);

// Convertir ingredientes por receta a arreglo PHP
$ingredientes = [];
while ($row = $detallesResult->fetch_assoc()) {
    $id = $row["idReceta"];
    if (!isset($ingredientes[$id])) {
        $ingredientes[$id] = [];
    }

    $textoIngrediente =
        $row["CantidadRequerida"] . " " .
        ($row["UnidadMedida"] ?? "") . " — " .
        $row["Ingrediente"];

    $ingredientes[$id][] = $textoIngrediente;
}

// Pasamos las recetas también codificadas para evitar errores de comillas
$recetasJS = [];
if ($result->num_rows > 0) {
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
        $recetasJS[$row["idReceta"]] = [
            "autor" => $row["Creador"],
            "fecha" => $row["FechaCreacion"],
            "instrucciones" => $row["Instrucciones"]
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Lista de Recetas — Administrador</title>
    <link rel="stylesheet" href="ListaRecetasAdministradores.css" />
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
            <li class="nav-item"><a href="ListaPlatillosAdministradores.php">Platillos</a></li>
            <li class="nav-item active"><a href="ListaRecetasAdministradores.php">Recetas</a></li>
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
<main>

<header class="topbar">
    <h2 class="page-title">Lista de Recetas</h2>

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

<section class="filters">
    <a href="AgregarRecetaAdministradores.php" class="btn-add filter-btn">
        Agregar Receta
    </a>
</section>

<div class="table-card">
    <table class="styled-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha creación</th>
                <th>Autor</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>

<?php
$result->data_seek(0);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = $row["idReceta"];
        ?>

        <tr>
            <td><?= $id ?></td>
            <td><?= $row["FechaCreacion"] ?></td>
            <td><?= $row["Creador"] ?></td>

            <td class="actions">
                <button class="btn view" onclick="openModal(<?= $id ?>)">Ver</button>

                <a href="EditarRecetaAdministradores.php?id=<?= $id ?>" class="btn edit">Editar</a>


            </td>
        </tr>

<?php
    }
} else {
    echo "<tr><td colspan='4'>No hay recetas registradas.</td></tr>";
}
?>

        </tbody>
    </table>
</div>


    
</main>

</div>

<!-- MODAL -->
<div id="modalReceta" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>

        <h2 id="modalTitulo">Receta</h2>

        <p><strong>Autor:</strong> <span id="modalAutor"></span></p>
        <p><strong>Fecha:</strong> <span id="modalFecha"></span></p>

        <h3>Ingredientes</h3>
        <ul id="modalIngredientes"></ul>

        <h3>Instrucciones</h3>
        <p id="modalInstrucciones"></p>
    </div>
</div>

<script>
// Datos seguros desde PHP
const ingredientesPHP = <?= json_encode($ingredientes) ?>;
const recetasPHP = <?= json_encode($recetasJS) ?>;

function openModal(id) {

    id = String(id); // Convertir a string porque las llaves llegan como "1", "2", ...

    // ===========================
    // AUTOR, FECHA, INSTRUCCIONES
    // ===========================
    document.getElementById("modalAutor").innerText = recetasPHP[id]["autor"];
    document.getElementById("modalFecha").innerText = recetasPHP[id]["fecha"];

    // Mostrar instrucciones con saltos de línea
    const instrucciones = recetasPHP[id]["instrucciones"].replace(/\n/g, "<br>");
    document.getElementById("modalInstrucciones").innerHTML = instrucciones;

    // ===========================
    // INGREDIENTES
    // ===========================
    const lista = document.getElementById("modalIngredientes");
    lista.innerHTML = "";

    if (ingredientesPHP[id]) {
        ingredientesPHP[id].forEach(texto => {
            const li = document.createElement("li");
            li.textContent = texto;
            lista.appendChild(li);
        });
    } else {
        lista.innerHTML = "<li>No hay ingredientes registrados.</li>";
    }

    document.getElementById("modalReceta").classList.add("active");
}

function closeModal() {
    document.getElementById("modalReceta").classList.remove("active");
}
</script>

</body>
</html>