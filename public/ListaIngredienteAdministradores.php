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

// CONSULTA A LA VISTA VistaIngredientes
$sql = "SELECT * FROM VistaIngredientes";
$resultado = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChilMole — Ingredientes</title>
    <link rel="stylesheet" href="ListaIngredienteAdministradores.css">
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

    <!-- MAIN CONTENT -->
    <main class="main">

        <header class="topbar">
            <h2 class="page-title">Lista de Ingredientes</h2>
        </header>

        <section class="filters">
            <a href="AgregarIngredienteAdministradores.php" class="btn-add filter-btn">
                Agregar Ingrediente
            </a>
        </section>

        <!-- GRID -->
        <div class="ingredientes-grid">

        <?php
        if ($resultado && $resultado->num_rows > 0) {
            while ($row = $resultado->fetch_assoc()) {

                $colorStock = ($row["CantidadDisponible"] < 100) ? "stock-low" : "";

                // ============================
                //   USAR SÓLO IMAGEN DE BD
                // ============================

                $imagenBD = $row["Imagen"];

                if (!empty($imagenBD)) {
                    $rutaImagen = "Imagenes/Ingredientes/" . $imagenBD;
                } else {
                    $rutaImagen = "Imagenes/Ingredientes/sin-imagen.png";
                }

                // Verificar si el archivo existe
                if (!file_exists("Imagenes/Ingredientes/" . $imagenBD)) {
                    $rutaImagen = "Imagenes/Ingredientes/sin-imagen.png";
                }

                echo '
                <div class="ingrediente-card">
                    <img src="' . $rutaImagen . '" class="ingrediente-img">

                    <div class="ingrediente-info">
                        <h2>' . $row["Nombre"] . '</h2>
                        <p><strong>Unidad:</strong> ' . $row["UnidadMedida"] . '</p>
                        <p class="stock ' . $colorStock . '"><strong>Stock:</strong> ' . $row["CantidadDisponible"] . '</p>
                        <p><strong>Costo:</strong> $' . number_format($row["CostoUnitario"], 2) . '</p>
                        <p><strong>Estatus:</strong> ' . $row["Estatus"] . '</p>
                    </div>

                    <div class="ingrediente-actions">
                        <a href="EditarIngredienteAdministradores.php?id=' . $row["idIngrediente"] . '" class="btn edit">Editar</a>
                        
                    </div>
                </div>';
            }
        } else {
            echo "<p>No hay ingredientes registrados.</p>";
        }

        $conn->close();
        ?>

        </div>
        


    </main>

</div>

</body>
</html>
