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

// PROCESAR ENVÍO DEL FORMULARIO
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $instrucciones = $_POST["Instrucciones"];
    $ingredientes = $_POST["ingredientes"];
    $cantidades = $_POST["cantidades"];

    $ingredientesJSON = [];

    for ($i = 0; $i < count($ingredientes); $i++) {
        if (!empty($ingredientes[$i]) && !empty($cantidades[$i])) {
            $ingredientesJSON[] = [
                "idIngrediente" => intval($ingredientes[$i]),
                "CantidadRequerida" => floatval($cantidades[$i])
            ];
        }
    }

    if (empty($ingredientesJSON)) {
        echo "<script>alert('Debe agregar al menos un ingrediente con cantidad válida.');</script>";
        exit;
    }

    $jsonFinal = json_encode($ingredientesJSON, JSON_UNESCAPED_UNICODE);

    // LLAMAR AL PROCEDIMIENTO
    $stmt = $conn->prepare("CALL CrearReceta(?, ?, ?)");
    if (!$stmt) {
        die("Error al preparar la consulta: " . $conn->error);
    }

    $stmt->bind_param("sis", $instrucciones, $idPersona, $jsonFinal);

    if ($stmt->execute()) {
        echo "<script>alert('Receta registrada correctamente'); 
        window.location='ListaRecetasAdministradores.php';</script>";
        exit;
    } else {
        $error_msg = "Error al ejecutar el procedimiento: " . $stmt->error . "\nJSON enviado: " . $jsonFinal;
        echo "<script>alert(" . json_encode($error_msg) . ");</script>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Receta — ChilMole</title>
    <link rel="stylesheet" href="AgregarRecetaAdministradores.css">
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

    <!-- TOP BAR -->
    <header class="topbar">
        <h2 class="page-title">Registro Recetas</h2>

        <div class="top-actions">
          <div class="profile">
            <div>
              <div class="profile-name"><?= $_SESSION["Nombre"] ?></div>
              <div class="profile-role">Rol <?= $_SESSION["Rol"] ?></div>
            </div>
            <div class="avatar">👩‍🍳</div>
          </div>
        </div>
    </header>

    <!-- FORMULARIO -->
    <form class="form-edit" method="POST">

        <label class="textarea-wrap">
            <span class="label-text">Instrucciones</span>
            <textarea name="Instrucciones" placeholder="Pasos para preparar el platillo" required></textarea>
        </label>

        <div id="ingredientes-list" class="ingredientes-box">
            <h3>Ingredientes</h3>
            <button type="button" class="btn add" onclick="agregarIngrediente()">+ Agregar Ingrediente</button>
        </div>

        <div class="btn-row">
            <button class="btn save" type="submit">Guardar Receta</button>
            <button class="btn cancel" type="button" onclick="history.back()">Cancelar</button>
        </div>

    </form>



    </main>

</div>

<script>
function agregarIngrediente() {
    let contenedor = document.getElementById("ingredientes-list");
    let item = document.createElement("div");
    item.classList.add("ingrediente-row");

    item.innerHTML = `
        <select name="ingredientes[]" required>
            <option value="">Seleccione ingrediente</option>
            <?php
                $q = $conn->query("SELECT idIngrediente, Nombre FROM Ingrediente WHERE Estatus = 'Activo' ORDER BY Nombre");
                while ($row = $q->fetch_assoc()) {
                    echo "<option value='{$row['idIngrediente']}'>{$row['Nombre']}</option>";
                }
            ?>
        </select>
        <input type="number" name="cantidades[]" placeholder="Cantidad requerida" min="0.01" step="0.01" required>
        <button type="button" class="btn remove" onclick="this.parentElement.remove()">X</button>
    `;

    contenedor.appendChild(item);
}
</script>

</body>
</html>
