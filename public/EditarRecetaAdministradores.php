<?php
session_start();

if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado
$conn->query("SET @id_usuario_actual = " . intval($idPersona));

// VALIDAR ID RECIBIDO
if (!isset($_GET["id"])) {
    die("ID de receta no especificado.");
}

$idReceta = intval($_GET["id"]);

// CARGAR DATOS DE LA RECETA
$qReceta = $conn->prepare("
    SELECT Instrucciones 
    FROM Receta 
    WHERE idReceta = ?
");
$qReceta->bind_param("i", $idReceta);
$qReceta->execute();
$resultReceta = $qReceta->get_result();

if ($resultReceta->num_rows === 0) {
    die("Receta no encontrada.");
}

$receta = $resultReceta->fetch_assoc();

// CARGAR INGREDIENTES DE LA RECETA
$qIngredientes = $conn->prepare("
    SELECT DR.idIngrediente, I.Nombre, DR.CantidadRequerida
    FROM DetalleReceta DR
    INNER JOIN Ingrediente I ON DR.idIngrediente = I.idIngrediente
    WHERE DR.idReceta = ?
");
$qIngredientes->bind_param("i", $idReceta);
$qIngredientes->execute();
$listaIngredientes = $qIngredientes->get_result();

// GUARDAR CAMBIOS (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $instrucciones = $_POST["Instrucciones"];
    $ingredientes  = $_POST["ingredientes"];
    $cantidades    = $_POST["cantidades"];

    // Construir JSON
    $ingredientesJSON = [];

    for ($i = 0; $i < count($ingredientes); $i++) {
        if (!empty($ingredientes[$i]) && !empty($cantidades[$i])) {
            $ingredientesJSON[] = [
                "idIngrediente" => intval($ingredientes[$i]),
                "CantidadRequerida" => floatval($cantidades[$i])
            ];
        }
    }

    $jsonFinal = json_encode($ingredientesJSON, JSON_UNESCAPED_UNICODE);

    // LLAMAR PROCEDIMIENTO ALMACENADO
    $stmt = $conn->prepare("CALL EditarReceta(?, ?, ?)");
    $stmt->bind_param("iss", $idReceta, $instrucciones, $jsonFinal);

    if ($stmt->execute()) {
        echo "<script>
                alert('Receta actualizada exitosamente');
                window.location='ListaRecetasAdministradores.php';
              </script>";
    } else {
        echo "<script>alert('Error al actualizar receta');</script>";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Receta — ChilMole</title>
    <link rel="stylesheet" href="EditarRecetaAdministradores.css">
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
    <main class="main">

        <header class="topbar">
            <h2 class="page-title">Modificar Receta</h2>
        </header>

        <form class="form-edit" method="POST">

            <!-- Instrucciones -->
            <label class="textarea-wrap">
                <span class="label-text">Instrucciones</span>
                <textarea name="Instrucciones" required><?= htmlspecialchars($receta["Instrucciones"]) ?></textarea>
            </label>

            <!-- Ingredientes -->
            <div id="ingredientes-list" class="ingredientes-box">
                <h3>Ingredientes</h3>

                <?php
                while ($ing = $listaIngredientes->fetch_assoc()) {
                    echo "
                    <div class='ingrediente-row'>
                        <select name='ingredientes[]' required>
                            <option value=''>Seleccione ingrediente</option>";

                    // Cargar ingredientes
                    $q = $conn->query("SELECT idIngrediente, Nombre FROM Ingrediente ORDER BY Nombre");
                    while ($row = $q->fetch_assoc()) {
                        $sel = ($row['idIngrediente'] == $ing['idIngrediente']) ? "selected" : "";
                        echo "<option value='{$row['idIngrediente']}' $sel>{$row['Nombre']}</option>";
                    }

                    echo "
                        </select>

                        <input type='number' name='cantidades[]' value='{$ing['CantidadRequerida']}' min='0.01' step='0.01' required>

                        <button type='button' class='btn remove' onclick='this.parentElement.remove()'>X</button>
                    </div>";
                }
                ?>

                <button type="button" class="btn add" onclick="agregarIngrediente()">+ Agregar Ingrediente</button>
            </div>

            <!-- Botones -->
            <div class="btn-row">
                <button class="btn save" type="submit">Guardar Cambios</button>
                <button class="btn cancel" type="button" onclick="history.back()">Cancelar</button>
            </div>

        </form>
        


    </main>

</div>

<script>
// Agregar nuevo ingrediente dinámico
function agregarIngrediente() {
    let contenedor = document.getElementById("ingredientes-list");

    let item = document.createElement("div");
    item.classList.add("ingrediente-row");

    item.innerHTML = `
        <select name="ingredientes[]" required>
            <option value="">Seleccione ingrediente</option>
            <?php
                $q = $conn->query("SELECT idIngrediente, Nombre FROM Ingrediente ORDER BY Nombre");
                while ($row = $q->fetch_assoc()) {
                    echo "<option value='{$row['idIngrediente']}'>{$row['Nombre']}</option>";
                }
            ?>
        </select>

        <input type="number" name="cantidades[]" placeholder="Cantidad" min="0.01" step="0.01" required>

        <button type="button" class="btn remove" onclick="this.parentElement.remove()">X</button>
    `;

    contenedor.appendChild(item);
}
</script>

</body>
</html>
