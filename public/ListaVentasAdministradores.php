<?php
session_start();

if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// ASIGNAR EL ID DEL USUARIO LOGEADO
$conn->query("SET @id_usuario_actual = " . intval($idPersona));

// ===== OBTENER DATOS DE VENTAS =====
$ventasRaw = [];
$result = $conn->query("SELECT * FROM VistaVentas ORDER BY Fecha DESC");
if ($result) {
    $ventasRaw = $result->fetch_all(MYSQLI_ASSOC);
}

$ventas = [];
$totalVentas = 0;
$totalInvertido = 0;
$gananciaTotal = 0;

foreach ($ventasRaw as $v) {
    $id = $v['idVenta'];
    if (!isset($ventas[$id])) {
        $ventas[$id] = [
            'idVenta' => $id,
            'Fecha' => $v['Fecha'],
            'TipoPago' => $v['TipoPago'],
            'Estatus' => $v['Estatus'],
            'TotalVenta' => 0,
            'TotalInvertido' => 0,
            'Ganancia' => 0,
            'Cajero' => $v['Cajero'] . " (" . $v['CajeroID'] . ")",
            'Items' => []
        ];
    }

    $ventas[$id]['Items'][] = [
        'Platillo' => $v['Platillo'],
        'Cantidad' => (int)$v['Cantidad'],
        'PrecioUnitario' => (float)$v['PrecioUnitario'],
        'Total' => (float)$v['Total']
    ];

    $ventas[$id]['TotalVenta'] += $v['Total'];
    $ventas[$id]['TotalInvertido'] += $v['Cantidad'] * $v['PrecioUnitario'];
    $ventas[$id]['Ganancia'] = $ventas[$id]['TotalVenta'] - $ventas[$id]['TotalInvertido'];
}

// Totales para las tarjetas
foreach ($ventas as $v) {
    $totalVentas += $v['TotalVenta'];
    $totalInvertido += $v['TotalInvertido'];
    $gananciaTotal += $v['Ganancia'];
}

// Convertir a arreglo numérico para JS
$ventasJson = array_values($ventas);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Lista de Ventas — Administrador</title>
<link rel="stylesheet" href="ListaVentasAdministradores.css" />
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
            <li class="nav-item"><a href="ListaUsuariosAdministradores.php">Usuarios</a></li>
            <li class="nav-item active"><a href="ListaVentasAdministradores.php">Ventas</a></li>

            <li class="nav-item"><a href="ListaNotificacionesAdministradores.php">Notificaciones</a></li>
            <li class="nav-item"><a href="Login.php">Salir</a></li>
        </ul>
    </nav>
</aside>

<!-- MAIN -->
<main class="main">

    <!-- TOP BAR -->
    <header class="topbar">
        <h2 class="page-title">Lista de Ventas</h2>
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

    <!-- TARJETAS -->
    <section class="cards">
        <div class="card">
            <h3>Total Ventas</h3>
            <p>$<?php echo number_format($totalVentas,2); ?></p>
        </div>
        <div class="card">
            <h3>Total Invertido</h3>
            <p>$<?php echo number_format($totalInvertido,2); ?></p>
        </div>
        <div class="card">
            <h3>Ganancia</h3>
            <p>$<?php echo number_format($gananciaTotal,2); ?></p>
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
                            <button class="btn view" data-venta='<?php echo json_encode($v, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>Ver detalle</button>
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
        <p><strong>Cajero:</strong> <span id="mv-cajero"></span></p>
        <p><strong>Tipo de pago:</strong> <span id="mv-tipopago"></span></p>
        <p><strong>Estatus:</strong> <span id="mv-estatus"></span></p>
        <p><strong>Total venta:</strong> <span id="mv-totalventa"></span></p>

        <h3 class="section-title">Detalles de la venta</h3>
        <table class="mini-table" id="mv-items">
          <thead>
            <tr><th>Platillo</th><th>Cant.</th><th>Precio</th><th>Total</th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </section>
    </div>
</div>

<script>
function closeModal(){
  document.getElementById("modalVenta").classList.remove("active");
}

// Cerrar modal al hacer click fuera
document.getElementById("modalVenta").addEventListener('click', e => {
  if(e.target.id === 'modalVenta') closeModal();
});

// Asignar evento a todos los botones
document.querySelectorAll('.btn.view').forEach(btn => {
    btn.addEventListener('click', e => {
        const v = JSON.parse(btn.getAttribute('data-venta'));

        document.getElementById('mv-id').innerText=`Venta #${v.idVenta}`;
        document.getElementById('mv-fecha').innerText=v.Fecha;
        document.getElementById('mv-cajero').innerText=v.Cajero;
        document.getElementById('mv-tipopago').innerText=v.TipoPago;
        document.getElementById('mv-estatus').innerText=v.Estatus;
        document.getElementById('mv-totalventa').innerText=`$${Number(v.TotalVenta).toFixed(2)}`;

        const tbody=document.querySelector('#mv-items tbody');
        tbody.innerHTML="";
        v.Items.forEach(it=>{
            const tr=document.createElement('tr');
            tr.innerHTML=`<td>${it.Platillo}</td>
                            <td>${it.Cantidad}</td>
                            <td>$${Number(it.PrecioUnitario).toFixed(2)}</td>
                            <td>$${Number(it.Total).toFixed(2)}</td>`;
            tbody.appendChild(tr);
        });

        document.getElementById("modalVenta").classList.add("active");
    });
});
</script>

</body>
</html>
