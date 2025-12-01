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

// Traer todas las devoluciones
$devoluciones = [];
$result = $conn->query("SELECT * FROM VistaDevoluciones ORDER BY Fecha DESC");
if ($result) {
    $devoluciones = $result->fetch_all(MYSQLI_ASSOC);
}

// Agrupar devoluciones por idDevolucion
$devolucionesAgrupadas = [];
foreach ($devoluciones as $d) {
    $id = $d['idDevolucion'];
    if (!isset($devolucionesAgrupadas[$id])) {
        $devolucionesAgrupadas[$id] = [
            'idDevolucion' => $id,
            'Fecha' => $d['Fecha'],
            'Cliente' => $d['Cliente'],
            'Platillos' => [],
            'TotalDevuelto' => 0
        ];
    }
    $devolucionesAgrupadas[$id]['Platillos'][] = [
        'Nombre' => $d['Platillo'],
        'CantidadDevuelta' => $d['CantidadDevuelta'],
        'TotalDevuelto' => $d['TotalDevuelto']
    ];
    $devolucionesAgrupadas[$id]['TotalDevuelto'] += $d['TotalDevuelto'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChilMole — Devoluciones Empleados</title>
    <link rel="stylesheet" href="ListaDevolucionesEmpleados.css">
    <style>
        .modal { display:none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); }
        .modal.active { display:block; }
        .modal-content { background:#fff; margin:50px auto; padding:20px; width:90%; max-width:500px; border-radius:10px; }
        .devoluciones-table { width:100%; border-collapse: collapse; }
        .devoluciones-table th, .devoluciones-table td { border:1px solid #ccc; padding:5px; text-align:left; }
        .devoluciones-table th { background:#eee; }
        .btn-detalle { cursor:pointer; }
    </style>
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
                <li class="nav-item"><a href="InicioEmpleado.php">Inicio</a></li>
                <li class="nav-item"><a href="ListaPlatillosEmpleados.php">Platillos</a></li>
                <li class="nav-item"><a href="ListaPedidosEmpleados.php">Pedidos</a></li>
                <li class="nav-item active"><a href="ListaDevolucionesEmpleados.php">Devoluciones</a></li>
                <li class="nav-item"><a href="ListaVentasEmpleados.php">Ventas</a></li>
                <li class="nav-item"><a href="Login.php">Salir</a></li>
            </ul>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main">

        <header class="topbar">
            <h2 class="page-title">Lista de Devoluciones</h2>
            <div class="top-actions">
                <div class="profile">
                    <div>
                        <div class="profile-name"><?php echo $_SESSION['Nombre'] ?? 'Empleado'; ?></div>
                        <div class="profile-role">Empleado</div>
                    </div>
                    <div class="avatar">👨‍🍳</div>
                </div>
            </div>
        </header>

        <section class="stats">
            <div class="stat-card">
                <h3>Total Devoluciones</h3>
                <p id="total-devoluciones"><?php echo count($devolucionesAgrupadas); ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Devuelto</h3>
                <?php
                $totalDevuelto = array_sum(array_column($devolucionesAgrupadas, 'TotalDevuelto'));
                ?>
                <p id="total-devuelto">$<?php echo number_format($totalDevuelto,2); ?></p>
            </div>
            <button class="btn-nueva-devolucion" onclick="window.location.href='AgregarDevolucionEmpleados.php'">
               Nueva Devolución
            </button>
        </section>

        <section class="table-section">
            <table class="devoluciones-table">
                <thead>
                    <tr>
                        <th>ID Devolución</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Total Devuelto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($devolucionesAgrupadas as $devolucion): ?>
                    <tr>
                        <td><?php echo $devolucion['idDevolucion']; ?></td>
                        <td><?php echo $devolucion['Fecha']; ?></td>
                        <td><?php echo $devolucion['Cliente']; ?></td>
                        <td>$<?php echo number_format($devolucion['TotalDevuelto'],2); ?></td>
                        <td>
                            <button class="btn-detalle" onclick='openModal(<?php echo json_encode($devolucion); ?>)'>Ver Detalles</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>



    </main>
</div>

<!-- MODAL DETALLE DEVOLUCIÓN -->
<div class="modal" id="modalDevolucion">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">✖</button>
        <div id="modalContent"></div>
    </div>
</div>

<script>
function openModal(devolucion) {
    const modal = document.getElementById('modalDevolucion');
    const content = document.getElementById('modalContent');

    let html = `<p><strong>Devolución ID:</strong> ${devolucion.idDevolucion}</p>`;
    html += `<p><strong>Cliente:</strong> ${devolucion.Cliente}</p>`;
    html += `<p><strong>Fecha:</strong> ${devolucion.Fecha}</p>`;
    html += `<h3>Productos Devueltos:</h3>`;
    html += `<table style="width:100%;border-collapse: collapse;">`;
    html += `<thead><tr><th>Producto</th><th>Cantidad</th><th>Total Devuelto</th></tr></thead><tbody>`;
    devolucion.Platillos.forEach(p => {
        html += `<tr>
                    <td>${p.Nombre}</td>
                    <td>${p.CantidadDevuelta}</td>
                    <td>$${Number(p.TotalDevuelto).toFixed(2)}</td>
                 </tr>`;
    });
    html += `</tbody></table>`;
    html += `<p class="modal-total"><strong>Total Devuelto:</strong> $${Number(devolucion.TotalDevuelto).toFixed(2)}</p>`;

    content.innerHTML = html;
    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('modalDevolucion').classList.remove('active');
}
</script>

</body>
</html>
