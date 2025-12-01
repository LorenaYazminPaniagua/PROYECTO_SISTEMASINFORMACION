<?php
session_start();

if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];
require_once "../includes/conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = $_POST["Nombre"];
    $descripcion = $_POST["Descripcion"];
    $imagen = $_POST["Imagen"];

    $stmt = $conn->prepare("CALL AgregarCategoria(?, ?, ?)");
    if (!$stmt) die("Error al preparar la consulta: " . $conn->error);

    $stmt->bind_param("sss", $nombre, $descripcion, $imagen);

    if ($stmt->execute()) {
        echo "<script>alert('Categoría agregada correctamente'); window.location='ListaCategorias.php';</script>";
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
<title>Agregar Categoría — ChilMole</title>
<link rel="stylesheet" href="AgregarCategoriaAdministradores.css">
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
            <h2 class="page-title">Agregar Categoría</h2>
        </header>

        <form class="form-edit" method="POST">
            <label class="input-wrap">
                <span class="label-text">Nombre</span>
                <input type="text" name="Nombre" placeholder="Nombre de la categoría" required>
            </label>

            <label class="textarea-wrap">
                <span class="label-text">Descripción</span>
                <textarea name="Descripcion" placeholder="Descripción de la categoría" required></textarea>
            </label>

            <label class="input-wrap">
                <span class="label-text">Imagen</span>
                <input type="text" name="Imagen" placeholder="URL de la imagen" required>
            </label>

            <div class="btn-row">
                <button class="btn save" type="submit">Guardar Categoría</button>
                <button class="btn cancel" type="button" onclick="history.back()">Cancelar</button>
            </div>
        </form>

    </main>
</div>
</body>
</html>
