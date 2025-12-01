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

// VALIDAR ID
if (!isset($_GET['id'])) {
    die("Error: No se recibió el ID del ingrediente.");
}

$idIngrediente = intval($_GET['id']);

// OBTENER DATOS DEL INGREDIENTE
$sql = $conn->prepare("SELECT * FROM Ingrediente WHERE idIngrediente = ?");
$sql->bind_param("i", $idIngrediente);
$sql->execute();
$result = $sql->get_result();

if ($result->num_rows === 0) {
    die("Error: Ingrediente no encontrado.");
}

$ingrediente = $result->fetch_assoc();

// SI ENVIARON FORMULARIO → ACTUALIZAR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $Nombre = $_POST['Nombre'];
    $UnidadMedida = $_POST['UnidadMedida'];
    $CantidadDisponible = $_POST['CantidadDisponible'];
    $CostoUnitario = $_POST['CostoUnitario'];
    $Estatus = $_POST['Estatus'];

    // ===============================
    // MANEJO DE IMAGEN (NUEVO)
    // ===============================
    $Imagen = $ingrediente['Imagen']; // Imagen actual

    if (!empty($_FILES['Imagen']['name'])) {

        $nombreArchivo = time() . "_" . basename($_FILES["Imagen"]["name"]);
        $rutaDestino = "Imagenes/Ingredientes/" . $nombreArchivo;

        // Crear carpeta si no existe
        if (!file_exists("Imagenes/Ingredientes/")) {
            mkdir("Imagenes/Ingredientes/", 0777, true);
        }

        if (move_uploaded_file($_FILES["Imagen"]["tmp_name"], $rutaDestino)) {
            $Imagen = $nombreArchivo;
        } else {
            echo "<script>alert('Error al subir la imagen');</script>";
        }
    }

    // LLAMAR PROCEDIMIENTO ALMACENADO
    $stmt = $conn->prepare("CALL EditarIngrediente(?,?,?,?,?,?,?)");

    $stmt->bind_param(
        "issddss",
        $idIngrediente,
        $Nombre,
        $UnidadMedida,
        $CantidadDisponible,
        $CostoUnitario,
        $Estatus,
        $Imagen
    );

    if ($stmt->execute()) {
        echo "<script>alert('Ingrediente actualizado correctamente'); window.location='ListaIngredienteAdministradores.php';</script>";
        exit;
    } else {
        echo "Error SQL: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Ingrediente — ChilMole</title>
    <link rel="stylesheet" href="EditarIngredienteAdministradores.css">
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

    <header class="topbar">
        <h2 class="page-title">Modificar Ingrediente</h2>

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

        <form class="form-edit" method="POST" enctype="multipart/form-data">

            <!-- NOMBRE -->
            <div class="input-wrap">
                <label class="label-text">Nombre</label>
                <input type="text" name="Nombre" value="<?= $ingrediente['Nombre'] ?>" required>
            </div>

            <!-- UNIDAD DE MEDIDA -->
            <div class="input-wrap">
                <label class="label-text">Unidad</label>
                <select name="UnidadMedida" required>
                    <option value="g"  <?= $ingrediente['UnidadMedida'] == "g" ? "selected" : "" ?>>Gramos (g)</option>
                    <option value="kg" <?= $ingrediente['UnidadMedida'] == "kg" ? "selected" : "" ?>>Kilogramos (kg)</option>
                    <option value="ml" <?= $Ingrediente['UnidadMedida'] == "ml" ? "selected" : "" ?>>Mililitros (ml)</option>
                    <option value="l"  <?= $ingrediente['UnidadMedida'] == "l" ? "selected" : "" ?>>Litros (L)</option>
                    <option value="pz" <?= $ingrediente['UnidadMedida'] == "pz" ? "selected" : "" ?>>Piezas (pz)</option>
                </select>
            </div>

            <!-- CANTIDAD DISPONIBLE -->
            <div class="input-wrap">
                <label class="label-text">Disponible</label>
                <input type="number" name="CantidadDisponible" value="<?= $ingrediente['CantidadDisponible'] ?>" min="0" step="0.01" required>
            </div>

            <!-- COSTO UNITARIO -->
            <div class="input-wrap">
                <label class="label-text">Costo Unitario</label>
                <input type="number" name="CostoUnitario" value="<?= $ingrediente['CostoUnitario'] ?>" min="0" step="0.01" required>
            </div>

            <!-- ESTATUS -->
            <div class="input-wrap">
                <label class="label-text">Estatus</label>
                <select name="Estatus" required>
                    <option value="Activo"   <?= $ingrediente['Estatus'] == "Activo" ? "selected" : "" ?>>Activo</option>
                    <option value="Inactivo" <?= $ingrediente['Estatus'] == "Inactivo" ? "selected" : "" ?>>Inactivo</option>
                </select>
            </div>

            <!-- IMAGEN (NUEVO) -->
            <div class="input-wrap">
                <label class="label-text">Imagen</label>
                <input type="file" name="Imagen" accept="image/*">
                <br>
                <small>Imagen actual:</small><br>
                <img src="Imagenes/Ingredientes/<?= $ingrediente['Imagen'] ?>" width="120" style="margin-top:5px; border-radius:10px;">
            </div>

            <!-- BOTONES -->
            <div class="btn-row">
                <button class="btn save" type="submit">Guardar Cambios</button>
                <button class="btn cancel" type="button" onclick="history.back()">Cancelar</button>
            </div>

        </form>


    
    </main>

</div>

</body>
</html>
