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

// CONSULTA USUARIOS
$sql = "SELECT * FROM VistaUsuarios ORDER BY NombreCompleto ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - Administrador</title>
    <link rel="stylesheet" href="ListaUsuariosAdministradores.css">
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
                <li class="nav-item"><a href="ListaIngredienteAdministradores.php">Ingredientes</a></li>
                <li class="nav-item"><a href="ListaPedidosAdministradores.php">Pedidos</a></li>
                <li class="nav-item active"><a href="ListaUsuariosAdministradores.php">Usuarios</a></li>
                <li class="nav-item"><a href="ListaVentasAdministradores.php">Ventas</a></li>

                <li class="nav-item"><a href="ListaNotificacionesAdministradores.php">Notificaciones</a></li>
                <li class="nav-item"><a href="Login.php">Salir</a></li>
            </ul>
        </nav>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main>

    <!-- TOP BAR -->
    <header class="topbar">
        <h2 class="page-title">Lista de Usuarios</h2>

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

        <!-- TABLA -->
        <div class="table-wrapper">
            <div class="table-card">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre completo</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Rol</th>
                            <th>Estatus</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($usuario = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $usuario['idPersona'] ?></td>
                                    <td><?= htmlspecialchars($usuario['NombreCompleto']) ?></td>
                                    <td><?= htmlspecialchars($usuario['Usuario']) ?></td>
                                    <td><?= htmlspecialchars($usuario['Email']) ?></td>
                                    <td><?= htmlspecialchars($usuario['Telefono']) ?></td>
                                    <td><?= htmlspecialchars($usuario['Rol']) ?></td>
                                    <td><?= htmlspecialchars($usuario['Estatus']) ?></td>
                                    <td class="actions">
                                        <a href="EditarUsuarioAdministradores.php?id=<?= $usuario['idPersona'] ?>" class="btn edit">Editar</a>
                                        <a href="EliminarUsuarioAdministrador.php?id=<?= $usuario['idPersona'] ?>" 
                                           class="btn delete" 
                                           onclick="return confirm('¿Eliminar usuario?')">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">No hay usuarios registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>


    
    </main>

</div>

</body>
</html>
