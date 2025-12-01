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

// CARGAR CATEGORÍAS PARA EL SELECT
$categorias = $conn->query("SELECT idCategoria, Nombre FROM Categoria");

// CARGAR RECETAS DESDE LA VISTA
$recetas = $conn->query("SELECT idReceta, Instrucciones FROM VistaRecetas GROUP BY idReceta");

// VERIFICAR ENVÍO DEL FORMULARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $Nombre = $_POST['Nombre'];
    $Descripcion = $_POST['Descripcion'];
    $PrecioVenta = $_POST['PrecioVenta'];
    $idCategoria = $_POST['idCategoria'];

    // Nuevos campos
    $idReceta = $_POST['idReceta']; // ← NUEVO
    $Cantidad = $_POST['Cantidad'];
    $CantidadDisponible = $_POST['CantidadDisponible'];

    // ---- MANEJO DE IMAGEN ----
    $nombreImagen = null;

    if (!empty($_FILES['Imagen']['name'])) {

        $archivo = $_FILES['Imagen'];
        $ext = pathinfo($archivo['name'], PATHINFO_EXTENSION);

        // Validar extensión
        $ext_permitida = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array(strtolower($ext), $ext_permitida)) {
            die("❌ Formato de imagen no válido");
        }

        // Generar nombre único
        $nombreImagen = "platillo_" . time() . "." . $ext;

        $rutaDestino = "../public/Imagenes/Platillos/" . $nombreImagen;

        // Mover archivo
        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            die("❌ Error al guardar la imagen.");
        }
    }

    // ---- LLAMADA AL PROCEDIMIENTO (8 parámetros) ----
    $stmt = $conn->prepare("CALL AgregarPlatillo(?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsiddd", 
        $Nombre, 
        $Descripcion, 
        $PrecioVenta, 
        $nombreImagen, 
        $idCategoria,
        $idReceta,
        $Cantidad,
        $CantidadDisponible
    );

    if ($stmt->execute()) {
        echo "<script>
                alert('Platillo agregado correctamente');
                window.location = 'ListaPlatillosAdministradores.php';
              </script>";
        exit;
    } else {
        echo "<script>alert('Error al agregar el platillo: ".$conn->error."');</script>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Platillo — ChilMole</title>
    <link rel="stylesheet" href="AgregarPlatilloAdministrador.css">
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
        <h2 class="page-title">Registro Platillos</h2>

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

        <!-- FORMULARIO -->
        <form class="form-edit" method="POST" enctype="multipart/form-data">
            
            <!-- Nombre -->
            <label class="input-wrap">
                <span class="label-text">Nombre</span>
                <input type="text" name="Nombre" placeholder="Nombre del platillo" required>
            </label>

            <!-- Descripción -->
            <label class="textarea-wrap">
                <span class="label-text">Descripción</span>
                <textarea name="Descripcion" placeholder="Descripción del platillo"></textarea>
            </label>

            <!-- Precio -->
            <label class="input-wrap">
                <span class="label-text">Precio</span>
                <input type="number" name="PrecioVenta" placeholder="Precio de venta" min="0" step="0.01" required>
            </label>

            <!-- Categoría -->
            <label class="input-wrap select-wrap">
                <span class="label-text">Categoría</span>
                <select name="idCategoria" required>
                    <option value="">Seleccione una categoría</option>
                    <?php while ($cat = $categorias->fetch_assoc()) { ?>
                        <option value="<?= $cat['idCategoria'] ?>">
                            <?= $cat['Nombre'] ?>
                        </option>
                    <?php } ?>
                </select>
            </label>

            <!-- 🔥 NUEVO: SELECT DE RECETA -->
            <label class="input-wrap select-wrap">
                <span class="label-text">Receta</span>
                <select name="idReceta" required>
                    <option value="">Seleccione una receta</option>

                    <?php while ($rec = $recetas->fetch_assoc()) { ?>
                        <option value="<?= $rec['idReceta'] ?>">
                            Receta #<?= $rec['idReceta'] ?> — <?= substr($rec['Instrucciones'], 0, 40) ?>...
                        </option>
                    <?php } ?>

                </select>
            </label>

            <!-- Cantidad -->
            <label class="input-wrap">
                <span class="label-text">Cantidad Inicial</span>
                <input type="number" name="Cantidad" min="0" step="0.01" placeholder="Ej. 10" required>
            </label>

            <!-- Cantidad Disponible -->
            <label class="input-wrap">
                <span class="label-text">Cantidad Disponible</span>
                <input type="number" name="CantidadDisponible" min="0" step="0.01" placeholder="Ej. 10" required>
            </label>

            <!-- Imagen -->
            <label class="input-wrap">
                <span class="label-text">Imagen</span>
                <input type="file" name="Imagen" accept="image/*">
            </label>

            <!-- Botones -->
            <div class="btn-row">
                <button class="btn save" type="submit">Agregar Platillo</button>
                <button class="btn cancel" type="button" onclick="history.back()">Cancelar</button>
            </div>

        </form>


    </main>

</div>

</body>
</html>
