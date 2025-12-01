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

// CARGAR DATOS DEL DASHBOARD
$sqlVentasHoy = "
    SELECT IFNULL(SUM(Total),0) AS TotalHoy
    FROM VistaVentas
    WHERE DATE(Fecha) = CURDATE();
";
$ventasHoy = $conn->query($sqlVentasHoy)->fetch_assoc()['TotalHoy'];

$sqlVentasAyer = "
    SELECT IFNULL(SUM(Total),0) AS TotalAyer
    FROM VistaVentas
    WHERE DATE(Fecha) = CURDATE() - INTERVAL 1 DAY;
";
$ventasAyer = $conn->query($sqlVentasAyer)->fetch_assoc()['TotalAyer'];

$delta = ($ventasAyer > 0)
    ? round((($ventasHoy - $ventasAyer) / $ventasAyer) * 100, 2)
    : 0;

$sqlPedidosPend = "
    SELECT COUNT(*) AS Pendientes
    FROM VistaPedidos
    WHERE Estatus = 'Pendiente' OR Estatus = 'Preparación';
";
$pedidosPendientes = $conn->query($sqlPedidosPend)->fetch_assoc()['Pendientes'];

$sqlDevoluciones = "
    SELECT COUNT(*) AS DevHoy
    FROM VistaDevoluciones
    WHERE DATE(Fecha) = CURDATE();
";
$devolucionesHoy = $conn->query($sqlDevoluciones)->fetch_assoc()['DevHoy'];

$sqlTopPlatillos = "
    SELECT Platillo, SUM(Cantidad) AS Total
    FROM VistaVentas
    WHERE Fecha >= CURDATE() - INTERVAL 7 DAY
    GROUP BY Platillo
    ORDER BY Total DESC
    LIMIT 5;
";
$topPlatillos = $conn->query($sqlTopPlatillos);

$sqlNotif = "
    SELECT *
    FROM VistaNotificaciones
    ORDER BY FechaCreacion DESC
    LIMIT 5;
";
$notificaciones = $conn->query($sqlNotif);

$sqlStockBajo = "
    SELECT *
    FROM VistaIngredientes
    WHERE CantidadDisponible < 10
    ORDER BY CantidadDisponible ASC;
";
$stockBajo = $conn->query($sqlStockBajo);

$sqlActividad = "
    (SELECT Fecha AS Fecha, CONCAT('Venta realizada: ', Platillo) AS Evento
     FROM VistaVentas ORDER BY Fecha DESC LIMIT 5)
    UNION
    (SELECT Fecha AS Fecha, CONCAT('Pedido generado: ', Platillo) AS Evento
     FROM VistaPedidos ORDER BY Fecha DESC LIMIT 5)
    UNION
    (SELECT Fecha AS Fecha, CONCAT('Devolución: ', Platillo) AS Evento
     FROM VistaDevoluciones ORDER BY Fecha DESC LIMIT 5)
    ORDER BY Fecha DESC
    LIMIT 10;
";
$actividad = $conn->query($sqlActividad);

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>ChilMole — Panel Administrador</title>
  <link rel="stylesheet" href="InicioAdministradores.css" />
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
                <li class="nav-item active"><a href="InicioAdministradores.php">Inicio</a></li>
                <li class="nav-item"><a href="ListaPlatillosAdministradores.php">Platillos</a></li>
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

    <!-- MAIN CONTENT -->
    <main class="main">

      <!-- TOP BAR -->
      <header class="topbar">
        <input id="search"
          class="search-input"
          type="search"
          placeholder="Buscar platillos, pedidos o usuarios..." />

        <div class="top-actions">
          <div class="profile">
            <div>
              <!-- Corrección aquí también (Nombre y Rol) -->
              <div class="profile-name"><?= $_SESSION['Nombre'] ?></div>
              <div class="profile-role">Administrador</div>
            </div>
            
            <div class="avatar">
                <?php if (!empty($_SESSION['Imagen'])): ?>
                    <img src="Imagenes/<?= $_SESSION['Imagen'] ?>" alt="Usuario">
                <?php else: ?>
                    👤
                <?php endif; ?>
            </div>
          </div>
        </div>
      </header>

      <!-- DASHBOARD -->
      <section class="dashboard">

        <!-- CARDS -->
        <div class="cards">
          <article class="card primary-card">
            <div>
              <h3 class="card-label">Ventas</h3>
              <div class="card-value" id="ventas-hoy">$<?= number_format($ventasHoy, 2) ?></div>
              <div class="card-note">
                Respecto ayer <span class="delta" id="ventas-delta"><?= $delta ?>%</span>
              </div>
            </div>
          </article>

          <article class="card">
            <div>
              <h3 class="card-label">Pedidos pendientes</h3>
              <div class="card-value" id="pedidos-pendientes"><?= $pedidosPendientes ?></div>
              <div class="card-note">En preparación</div>
            </div>
          </article>

          <article class="card">
            <div>
              <h1 class="card-label">Mole de mi tierra te desea un gran dia</h1>
            </div>
          </article>
        </div>

        <!-- MID AREA -->
        <div class="mid-area">

          <!-- CHART -->
          <section class="card chart-card">
            <div class="card-header">
              <h4>Platillos más vendidos</h4>
              <small class="muted">Últimos 7 días</small>
            </div>

            <div class="chart" id="bar-chart">
              <ul>
              <?php while($p = $topPlatillos->fetch_assoc()): ?>
                <li><?= $p['Platillo'] ?> — <?= $p['Total'] ?> vendidos</li>
              <?php endwhile; ?>
              </ul>
            </div>
          </section>

          <!-- RIGHT PANEL -->
          <aside class="right-col">

            <div class="card">
              <div class="card-header">
                <h4>Notificaciones</h4>
              </div>
              <ul id="notifications-list" class="list">
                <?php while($n = $notificaciones->fetch_assoc()): ?>
                  <li><?= $n['Mensaje'] ?></li>
                <?php endwhile; ?>
              </ul>
            </div>

            <div class="card">
              <div class="card-header">
                <h4>Ingredientes bajos</h4>
              </div>
              <ul id="low-stock-list" class="list">
                <?php while($i = $stockBajo->fetch_assoc()): ?>
                  <li><?= $i['Nombre'] ?> — <?= $i['CantidadDisponible'] ?> <?= $i['UnidadMedida'] ?></li>
                <?php endwhile; ?>
              </ul>
            </div>
          </aside>
        </div>

        <!-- BOTTOM ROW -->
        <div class="bottom-row">
          <div class="card activity">
            <div class="card-header">
              <h4>Actividad reciente</h4>
            </div>
            <ul id="activity-list" class="list compact">
              <?php while($a = $actividad->fetch_assoc()): ?>
                <li><?= $a['Fecha'] ?> — <?= $a['Evento'] ?></li>
              <?php endwhile; ?>
            </ul>
          </div>
        </div>
      </section>



    </main>
  </div>

</body>
</html>
