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

// Si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $Nombre = $_POST["Nombre"];
    $UnidadMedida = $_POST["UnidadMedida"];
    $CantidadDisponible = $_POST["CantidadDisponible"];
    $CostoUnitario = $_POST["CostoUnitario"];

    // MANEJO DE LA IMAGEN
    $Imagen = null;

    if (!empty($_FILES["Imagen"]["name"])) {

        $nombreArchivo = time() . "_" . basename($_FILES["Imagen"]["name"]);
        $rutaDestino = "Imagenes/Ingredientes/" . $nombreArchivo;

        // Crear directorio si no existe
        if (!file_exists("Imagenes/Ingredientes/")) {
            mkdir("Imagenes/Ingredientes/", 0777, true);
        }

        if (move_uploaded_file($_FILES["Imagen"]["tmp_name"], $rutaDestino)) {
            $Imagen = $nombreArchivo;
        } else {
            $error = "Error al subir la imagen.";
        }
    }

    // Si no hay imagen, se manda NULL
    if ($Imagen === null) {
        $Imagen = "";
    }

    // Llamar al procedimiento almacenado CORRECTO
    $stmt = $conn->prepare("CALL AgregarIngrediente(?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdss", $Nombre, $UnidadMedida, $CantidadDisponible, $CostoUnitario, $Imagen);

    if ($stmt->execute()) {
        header("Location: ListaIngredienteAdministradores.php?msg=success");
        exit();
    } else {
        $error = "Error SQL: " . $conn->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Ingrediente — ChilMole</title>
    <link rel="stylesheet" href="AgregarIngredienteAdministradores.css">
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
                <li class="nav-item"><a href="ListaRecetasAdministradores.php">Recetas</a></li>
                <li class="nav-item active"><a href="ListaIngredienteAdministradores.php">Ingredientes</a></li>
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
            <h2 class="page-title">Registro Ingredientes</h2>

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

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form class="form-add" method="POST" enctype="multipart/form-data">

            <!-- NOMBRE -->
            <div class="input-wrap">
                <label class="label-text">Nombre</label>
                <input type="text" name="Nombre" placeholder="Ej. Chile Pasilla" required>
            </div>

            <!-- UNIDAD DE MEDIDA -->
            <div class="input-wrap">
                <label class="label-text">Unidad</label>
                <select name="UnidadMedida" required>
                    <option disabled selected>Seleccionar...</option>
                    <option value="g">Gramos (g)</option>
                    <option value="kg">Kilogramos (kg)</option>
                    <option value="ml">Mililitros (ml)</option>
                    <option value="l">Litros (L)</option>
                    <option value="pz">Piezas (pz)</option>
                </select>
            </div>

            <!-- CANTIDAD DISPONIBLE -->
            <div class="input-wrap">
                <label class="label-text">Disponible</label>
                <input type="number" name="CantidadDisponible" placeholder="0.00" min="0" step="0.01" required>
            </div>

            <!-- COSTO UNITARIO -->
            <div class="input-wrap">
                <label class="label-text">Costo Unitario</label>
                <input type="number" name="CostoUnitario" placeholder="0.00" min="0" step="0.01" required>
            </div>

            <!-- IMAGEN -->
            <div class="input-wrap">
                <label class="label-text">Imagen</label>
                <input type="file" name="Imagen" accept="image/*">
            </div>

            <!-- BOTONES -->
            <div class="btn-row">
                <button class="btn save" type="submit">Guardar Ingrediente</button>
                <button class="btn cancel" type="button" onclick="history.back()">Cancelar</button>
            </div>

        </form>
        


    </main>

</div>

</body>
</html>
