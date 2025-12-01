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

// Traer ventas del usuario logeado
$ventasRaw = [];
$result = $conn->query("SELECT * FROM VistaVentas WHERE CajeroID = $idPersona ORDER BY Fecha DESC");
if ($result) {
    $ventasRaw = $result->fetch_all(MYSQLI_ASSOC);
}

$ventas = [];
foreach ($ventasRaw as $v) {
    $id = $v['idVenta'];
    if (!isset($ventas[$id])) {
        $ventas[$id] = [
            'idVenta' => $id,
            'Fecha' => $v['Fecha'],
            'TipoPago' => $v['TipoPago'],
            'Estatus' => $v['Estatus'],
            'TotalVenta' => 0,
            'Items' => []
        ];
    }
    $ventas[$id]['Items'][] = [
        'Platillo' => $v['Platillo'],
        'Cantidad' => $v['Cantidad'],
        'PrecioUnitario' => $v['PrecioUnitario'],
        'Total' => $v['Total']
    ];
    $ventas[$id]['TotalVenta'] += $v['Total'];
}

// Traer pedidos levantados por el usuario logeado
$pedidosRaw = [];
$result = $conn->query("SELECT * FROM VistaPedidos WHERE idPersona = $idPersona ORDER BY Fecha DESC");
if ($result) {
    $pedidosRaw = $result->fetch_all(MYSQLI_ASSOC);
}

$pedidos = [];
foreach ($pedidosRaw as $p) {
    $id = $p['idPedido'];
    if (!isset($pedidos[$id])) {
        $pedidos[$id] = [
            'idPedido' => $id,
            'Fecha' => $p['Fecha'],
            'FechaEntrega' => $p['FechaEntrega'],
            'Estatus' => $p['Estatus'],
            'Total' => 0,
            'Items' => []
        ];
    }

    $pedidos[$id]['Items'][] = [
        'Platillo' => $p['Platillo'],
        'Cantidad' => $p['Cantidad'],
        'PrecioUnitario' => $p['PrecioUnitario'],
        'Total' => $p['Total']
    ];

    $pedidos[$id]['Total'] += $p['Total'];
}

// Totales para tarjetas
$totalVentas = array_reduce($ventas, fn($carry,$v)=>$carry+$v['TotalVenta'],0);
$totalPedidos = count($pedidos);
$clientes = [];
foreach($ventas as $v){
    $clientes[$v['idVenta']] = true; // cada venta representa un cliente
}
$totalClientes = count($clientes);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Mis Ventas y Pedidos — Empleado</title>
  <link rel="stylesheet" href="ListaVentasEmpleados.css" />
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
                <li class="nav-item"><a href="InicioEmpleado.php">Inicio</a></li>
                <li class="nav-item"><a href="ListaPlatillosEmpleados.php">Platillos</a></li>
                <li class="nav-item"><a href="ListaPedidosEmpleados.php">Pedidos</a></li>

                <li class="nav-item active"><a href="ListaVentasEmpleados.php">Ventas</a></li>
                <li class="nav-item"><a href="Login.php">Salir</a></li>
            </ul>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main">
      <header class="topbar">
        <h2 class="page-title">Mis ventas</h2>
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

      <!-- TARJETAS -->
      <section class="cards">
        <div class="card">
          <h3>Total Ventas</h3>
          <p>$<?php echo number_format($totalVentas,2); ?></p>
        </div>
        <div class="card">
          <h3>Clientes Atendidos</h3>
          <p><?php echo $totalClientes; ?></p>
        </div>
        <div class="card">
          <h3>Pedidos Levantados</h3>
          <p><?php echo $totalPedidos; ?></p>
        </div>
      </section>

      <!-- TABLA DE VENTAS -->
      <section class="table-area">
        <div class="table-wrapper">
          <table class="styled-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Tipo pago</th>
                <th>Total venta</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach($ventas as $v): ?>
              <tr>
                <td><?php echo $v['idVenta']; ?></td>
                <td><?php echo $v['Fecha']; ?></td>
                <td><?php echo $v['TipoPago']; ?></td>
                <td>$<?php echo number_format($v['TotalVenta'],2); ?></td>
                <td>
                  <button class="btn view" onclick='openModal(<?php echo json_encode($v); ?>)'>Ver detalle</button>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>


    
    </main>
</div>

<!-- MODAL DE VENTA -->
<div id="modalVenta" class="modal">
    <div class="modal-content">
      <header class="modal-header">
        <h2 id="mv-id">Venta #—</h2>
        <div id="mv-fecha" class="muted"></div>
      </header>
      <section class="modal-body">
        <p><strong>Tipo de pago:</strong> <span id="mv-tipopago"></span></p>
        <p><strong>Estatus:</strong> <span id="mv-estatus"></span></p>
        <p><strong>Total venta:</strong> <span id="mv-totalventa"></span></p>

        <h3 class="section-title">Detalles de la venta</h3>
        <table class="mini-table" id="mv-items">
          <thead><tr><th>Platillo</th><th>Cant.</th><th>Precio</th><th>Total</th></tr></thead>
          <tbody></tbody>
        </table>
      </section>
    </div>
</div>

<script>
function openModal(v){
  document.getElementById('mv-id').innerText=`Venta #${v.idVenta}`;
  document.getElementById('mv-fecha').innerText=v.Fecha;
  document.getElementById('mv-tipopago').innerText=v.TipoPago;
  document.getElementById('mv-estatus').innerText=v.Estatus;
  document.getElementById('mv-totalventa').innerText=`$${Number(v.TotalVenta).toFixed(2)}`;

  const tbody=document.querySelector('#mv-items tbody');
  tbody.innerHTML="";
  v.Items.forEach(it=>{
    const tr=document.createElement('tr');
    tr.innerHTML=`<td>${it.Platillo}</td><td>${it.Cantidad}</td><td>$${it.PrecioUnitario}</td><td>$${it.Total}</td>`;
    tbody.appendChild(tr);
  });

  document.getElementById("modalVenta").classList.add("active");
}

function closeModal(){
  document.getElementById("modalVenta").classList.remove("active");
}

document.getElementById("modalVenta").addEventListener('click', e => {
  if(e.target.id === 'modalVenta') closeModal();
});
</script>
