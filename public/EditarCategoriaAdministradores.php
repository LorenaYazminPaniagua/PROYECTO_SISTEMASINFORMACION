<?php
session_start();

if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

require_once "../includes/conexion.php";

if (!isset($_GET['id'])) {
    header("Location: ListaCategorias.php");
    exit();
}

$idCategoria = intval($_GET['id']);
$q = $conn->prepare("SELECT Nombre, Descripcion, Imagen FROM Categoria WHERE idCategoria = ?");
$q->bind_param("i", $idCategoria);
$q->execute();
$result = $q->get_result();
$categoria = $result->fetch_assoc();
$q->close();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = $_POST["Nombre"];
    $descripcion = $_POST["Descripcion"];
    
    // Procesar la imagen
    $imagen = $categoria['Imagen']; // mantener la imagen actual si no suben nueva
    if (isset($_FILES['Imagen']) && $_FILES['Imagen']['error'] === UPLOAD_ERR_OK) {
        $archivoTmp = $_FILES['Imagen']['tmp_name'];
        $nombreArchivo = basename($_FILES['Imagen']['name']);
        $rutaDestino = __DIR__ . "/Imagenes/Categoria/" . $nombreArchivo;

        if (move_uploaded_file($archivoTmp, $rutaDestino)) {
            $imagen = $nombreArchivo;
        } else {
            echo "<script>alert('Error al subir la imagen');</script>";
        }
    }

    $stmt = $conn->prepare("CALL EditarCategoria(?, ?, ?, ?)");
    if (!$stmt) die("Error al preparar la consulta: " . $conn->error);

    $stmt->bind_param("isss", $idCategoria, $nombre, $descripcion, $imagen);

    if ($stmt->execute()) {
        echo "<script>alert('Categoría editada correctamente'); window.location='ListaCategoriasAdministradores.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Categoría — ChilMole</title>
<link rel="stylesheet" href="EditarCategoriaAdministradores.css">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-img"><img src="Imagenes/Lele.png" alt="Logo ChilMole" /></div>
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

    <main class="main">
        <header class="topbar">
            <h2 class="page-title">Editar Categoría</h2>
        </header>

        <form class="form-edit" method="POST" enctype="multipart/form-data">
            <label class="input-wrap">
                <span class="label-text">Nombre</span>
                <input type="text" name="Nombre" value="<?= htmlspecialchars($categoria['Nombre']) ?>" required>
            </label>

            <label class="textarea-wrap">
                <span class="label-text">Descripción</span>
                <textarea name="Descripcion" required><?= htmlspecialchars($categoria['Descripcion']) ?></textarea>
            </label>

            <label class="input-wrap">
                <span class="label-text">Imagen</span>
                <input type="file" name="Imagen" accept="image/*">
                <?php if(!empty($categoria['Imagen'])): ?>
                    <img src="Imagenes/Categoria/<?= htmlspecialchars($categoria['Imagen']) ?>" alt="Imagen actual" style="margin-top:10px; width:150px; border-radius:10px;">
                <?php endif; ?>
            </label>

            <div class="btn-row">
                <button class="btn save" type="submit">Actualizar Categoría</button>
                <button class="btn cancel" type="button" onclick="history.back()">Cancelar</button>
            </div>
        </form>


    
    </main>
</div>
</body>
</html>
