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

// OBTENER ID DEL PLATILLO
if (!isset($_GET["id"])) {
    die("Error: No se especificó un ID de platillo.");
}

$idPlatillo = intval($_GET["id"]);

// CARGAR DATOS DEL PLATILLO EXISTENTE
$sqlPlatillo = $conn->prepare("SELECT * FROM Platillo WHERE idPlatillo = ?");
$sqlPlatillo->bind_param("i", $idPlatillo);
$sqlPlatillo->execute();
$dataPlatillo = $sqlPlatillo->get_result()->fetch_assoc();

if (!$dataPlatillo) {
    die("Error: El platillo no existe.");
}

// CARGAR DETALLES
$sqlDetalle = $conn->prepare("SELECT * FROM DetallePlatillo WHERE idPlatillo = ?");
$sqlDetalle->bind_param("i", $idPlatillo);
$sqlDetalle->execute();
$dataDetalle = $sqlDetalle->get_result()->fetch_assoc();

// CARGAR CATEGORÍAS
$categorias = $conn->query("SELECT idCategoria, Nombre FROM Categoria");

// CARGAR RECETAS (desde la vista solicitada)
$recetas = $conn->query("
    SELECT idReceta, Instrucciones 
    FROM VistaRecetas 
    GROUP BY idReceta
");

// PROCESAR FORMULARIO
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = $_POST["Nombre"];
    $descripcion = $_POST["Descripcion"];
    $precio = $_POST["PrecioVenta"];
    $idCategoria = $_POST["idCategoria"];
    $idReceta = $_POST["idReceta"];  // NUEVO
    $cantidad = $_POST["Cantidad"];
    $cantidadDisponible = $_POST["CantidadDisponible"];
    $fechaPrep = $_POST["FechaPreparacion"];

    // MANEJO DE IMAGEN
    $imagenFinal = $dataPlatillo["Imagen"];

    if (!empty($_FILES["Imagen"]["name"])) {

        $nombreImg = time() . "_" . basename($_FILES["Imagen"]["name"]);
        $ruta = "Imagenes/Platillos/" . $nombreImg;

        move_uploaded_file($_FILES["Imagen"]["tmp_name"], $ruta);

        $imagenFinal = $nombreImg;
    }

    // LLAMAR PROCEDIMIENTO EditarPlatillo (7 parámetros)
    $stmt = $conn->prepare("CALL EditarPlatillo(?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "issdsii",
        $idPlatillo,
        $nombre,
        $descripcion,
        $precio,
        $imagenFinal,
        $idCategoria,
        $idReceta
    );

    try {
        $stmt->execute();
    } catch (Exception $e) {
        die("Error al actualizar: " . $e->getMessage());
    }

    // ACTUALIZAR DETALLEPLATILLO
    if ($dataDetalle) {
        $sqlUpd = $conn->prepare("
            UPDATE DetallePlatillo
            SET Cantidad = ?, CantidadDisponible = ?, FechaPreparacion = ?
            WHERE idPlatillo = ?
        ");
        $sqlUpd->bind_param("ddsi", $cantidad, $cantidadDisponible, $fechaPrep, $idPlatillo);
        $sqlUpd->execute();
    }

    echo "<script>
            alert('Platillo actualizado correctamente');
            window.location='ListaPlatillosAdministradores.php';
          </script>";
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Platillo — ChilMole</title>
    <link rel="stylesheet" href="EditarPlatilloAdministrador.css">
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

    <header class="topbar">
        <h2 class="page-title">Modificar Platillo</h2>
        <div class="top-actions">
          <div class="profile">
            <div><div class="profile-name">Administrador</div><div class="profile-role">Superadmin</div></div>
            <div class="avatar">👩‍🍳</div>
          </div>
        </div>
    </header>

        <form class="form-edit" method="POST" enctype="multipart/form-data">

            <!-- Nombre -->
            <label class="input-wrap">
                <input type="text" name="Nombre" value="<?= $dataPlatillo['Nombre'] ?>" required>
            </label>

            <!-- Descripción -->
            <label class="textarea-wrap">
                <textarea name="Descripcion"><?= $dataPlatillo['Descripcion'] ?></textarea>
            </label>

            <!-- Precio -->
            <label class="input-wrap">
                <input type="number" name="PrecioVenta" min="0" step="0.01"
                       value="<?= $dataPlatillo['PrecioVenta'] ?>" required>
            </label>

            <!-- Categoría -->
            <label class="input-wrap select-wrap">
                <select name="idCategoria" required>
                    <option value="">Seleccione una categoría</option>
                    <?php while ($cat = $categorias->fetch_assoc()): ?>
                        <option value="<?= $cat['idCategoria'] ?>"
                            <?= $cat['idCategoria'] == $dataPlatillo['idCategoria'] ? 'selected' : '' ?>>
                            <?= $cat['Nombre'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </label>

            <!-- Receta -->
            <label class="input-wrap select-wrap">
                <select name="idReceta" required>
                    <option value="">Seleccione una receta</option>
                    <?php while ($rec = $recetas->fetch_assoc()): ?>
                        <option value="<?= $rec['idReceta'] ?>"
                            <?= $rec['idReceta'] == $dataPlatillo['idReceta'] ? 'selected' : '' ?>>
                            Receta #<?= $rec['idReceta'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </label>

            <!-- Imagen actual -->
            <p>Imagen actual:</p>
            <img src="ImagenesPlatillos/<?= $dataPlatillo['Imagen'] ?>" width="150">

            <!-- Nueva imagen -->
            <label class="input-wrap">
                <input type="file" name="Imagen" accept="image/*">
            </label>

            <!-- Cantidades -->
            <label class="input-wrap">
                <input type="number" name="Cantidad" min="0" step="0.01" 
                       value="<?= $dataDetalle['Cantidad'] ?>" required>
            </label>

            <label class="input-wrap">
                <input type="number" name="CantidadDisponible" min="0" step="0.01" 
                       value="<?= $dataDetalle['CantidadDisponible'] ?>" required>
            </label>

            <label class="input-wrap">
                <input type="datetime-local" name="FechaPreparacion"
                       value="<?= date('Y-m-d\TH:i', strtotime($dataDetalle['FechaPreparacion'])) ?>">
            </label>

            <div class="btn-row">
                <button class="btn save" type="submit">Guardar Cambios</button>
                <button class="btn cancel" type="button" onclick="history.back()">Cancelar</button>
            </div>

        </form>


    
    </main>

</div>

</body>
</html>
