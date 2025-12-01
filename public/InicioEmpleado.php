<?php
session_start();

if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 2) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado
$conn->query("SET @id_usuario_actual = " . intval($idPersona));

// Consultas a vistas para mostrar en el dashboard
$resultPedidosPendientes = $conn->query("SELECT COUNT(DISTINCT idPedido) AS total FROM VistaPedidos WHERE Estatus = 'Pendiente'");
$pedidosPendientes = $resultPedidosPendientes->fetch_assoc()['total'] ?? 0;

$resultPedidosCompletados = $conn->query("SELECT COUNT(DISTINCT idPedido) AS total FROM VistaPedidos WHERE Estatus = 'Completado' AND Fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$pedidosCompletados = $resultPedidosCompletados->fetch_assoc()['total'] ?? 0;

$resultVentas = $conn->query("SELECT SUM(Total) AS totalVentas FROM VistaVentas WHERE MONTH(Fecha) = MONTH(CURDATE()) AND YEAR(Fecha) = YEAR(CURDATE())");
$ventasGeneradas = $resultVentas->fetch_assoc()['totalVentas'] ?? 0;

$resultNotificaciones = $conn->query("SELECT * FROM VistaNotificaciones ORDER BY FechaCreacion DESC LIMIT 5");
$notificaciones = $resultNotificaciones->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>ChilMole — Inicio Empleado</title>
  <link rel="stylesheet" href="InicioEmpleado.css" />
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
                <li class="nav-item active"><a href="InicioEmpleado.php">Inicio</a></li>
                <li class="nav-item"><a href="ListaPlatillosEmpleados.php">Platillos</a></li>
                <li class="nav-item"><a href="ListaPedidosEmpleados.php">Pedidos</a></li>
                <li class="nav-item"><a href="ListaVentasEmpleados.php">Ventas</a></li>
                <li class="nav-item"><a href="Login.php">Salir</a></li>
            </ul>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main">

      <!-- TOP BAR -->
      <header class="topbar">
        <h2 class="page-title">Bienvenido, <?php echo $_SESSION['Nombre'] ?? 'Empleado'; ?></h2>
        <div class="top-actions">
          <div class="profile">
            <div>
              <div class="profile-name"><?php echo $_SESSION['Nombre'] ?? 'Juan Pérez'; ?></div>
              <div class="profile-role">Empleado</div>
            </div>
            <div class="avatar">👨‍🍳</div>
          </div>
        </div>
      </header>

      <!-- DASHBOARD -->
      <section class="dashboard">

        <!-- TARJETAS PRINCIPALES -->
        <div class="cards">
          <article class="card primary-card">
            <div>
              <h3 class="card-label">Pedidos pendientes</h3>
              <div class="card-value" id="pedidos-pendientes"><?php echo $pedidosPendientes; ?></div>
              <div class="card-note">Total en preparación</div>
            </div>
          </article>

          <article class="card">
            <div>
              <h3 class="card-label">Pedidos completados</h3>
              <div class="card-value" id="pedidos-completados"><?php echo $pedidosCompletados; ?></div>
              <div class="card-note">Últimos 7 días</div>
            </div>
          </article>

          <article class="card">
            <div>
              <h3 class="card-label">Ventas generadas</h3>
              <div class="card-value" id="ventas-generadas">$<?php echo number_format($ventasGeneradas, 2); ?></div>
              <div class="card-note">Este mes</div>
            </div>
          </article>
        </div>

        <!-- ACCESOS RAPIDOS -->
        <div class="quick-access">
          <h4>Accesos rápidos</h4>
          <div class="quick-buttons">
            <a href="ListaPedidosEmpleados.php" class="btn-quick">Lista Pedidos</a>
            <a href="ListaVentasEmpleados.php" class="btn-quick">Mis Ventas</a>
            <a href="ListaPlatillosEmpleados.php" class="btn-quick">Lista Platillos</a>
            <a href="BolsaEmpleados.php" class="btn-quick">Bolsa de Compras</a>
          </div>
        </div>

        <!-- NOTIFICACIONES -->
        <div class="stats-section">
          <section class="card chart-card">
            <div class="card-header">
              <h4>Últimas Notificaciones</h4>
            </div>
            <ul>
              <?php foreach($notificaciones as $n): ?>
                <li><?php echo htmlspecialchars($n['Mensaje']); ?> (<?php echo date("d/m/Y H:i", strtotime($n['FechaCreacion'])); ?>)</li>
              <?php endforeach; ?>
            </ul>
          </section>
        </div>
        


      </section>

    </main>
  </div>
</body>
</html>
