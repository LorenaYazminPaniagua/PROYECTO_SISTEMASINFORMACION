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

// OBTENER NOTIFICACIONES
$sql = "SELECT * FROM VistaNotificaciones ORDER BY FechaCreacion DESC";
$result = $conn->query($sql);

$notificaciones = [];
if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        // Determinar el item según el tipo
        $item = $row['Ingrediente'] ?? $row['Platillo'] ?? 'N/A';
        // Determinar estado: asumimos que si hay 'FechaLeida' es leída, sino no leída (puedes adaptar si tienes columna específica)
        $estado = isset($row['Leida']) && $row['Leida'] ? 'Leída' : 'No leída';
        $notificaciones[] = [
            'id' => $row['idNotificacion'],
            'tipo' => $row['Tipo'],
            'item' => $item,
            'cantidad' => $row['CantidadActual'],
            'mensaje' => $row['Mensaje'],
            'fecha' => $row['FechaCreacion']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones — ChilMole</title>
    <link rel="stylesheet" href="ListaNotificacionesAdministradores.css">
</head>
<body>

<div class="app">

    <!-- SIDEBAR -->
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
                <li class="nav-item"><a href="ListaPlatillosAdministradores.php">Platillos</a></li>
                <li class="nav-item"><a href="ListaRecetasAdministradores.php">Recetas</a></li>
                <li class="nav-item"><a href="ListaIngredienteAdministradores.php">Ingredientes</a></li>
                <li class="nav-item"><a href="ListaPedidosAdministradores.php">Pedidos</a></li>
                <li class="nav-item"><a href="ListaUsuariosAdministradores.php">Usuarios</a></li>
                <li class="nav-item"><a href="ListaVentasAdministradores.php">Ventas</a></li>
                <li class="nav-item active"><a href="ListaNotificacionesAdministradores.php">Notificaciones</a></li>
                <li class="nav-item"><a href="Login.php">Salir</a></li>
            </ul>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main">

        <!-- TOP BAR -->
        <header class="topbar">
            <h2 class="page-title">Notificaciones</h2>
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

        <!-- TABLA DE NOTIFICACIONES -->
        <div class="card">
            <table class="notif-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Nombre</th>
                        <th>Cantidad Actual</th>
                        <th>Mensaje</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($notificaciones) > 0): ?>
                        <?php foreach($notificaciones as $n): ?>
                            <tr class="<?php echo $n['estado']=='No leída' ? 'unread' : ''; ?>">
                                <td><?php echo $n['id']; ?></td>
                                <td><?php echo htmlspecialchars($n['tipo']); ?></td>
                                <td><?php echo htmlspecialchars($n['item']); ?></td>
                                <td><?php echo htmlspecialchars($n['cantidad']); ?></td>
                                <td><?php echo htmlspecialchars($n['mensaje']); ?></td>
                                <td><?php echo $n['fecha']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No hay notificaciones.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


    
    </main>
</div>

</body>
</html>
