<?php
session_start();

if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logeado
$conn->query("SET @id_usuario_actual = " . intval($idPersona));

// ===== LLAMADA AJAX PARA CANCELAR PEDIDO =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'], $_POST['idPedido'])) {
    if ($_POST['accion'] === 'cancelar') {
        $idPedido = intval($_POST['idPedido']);
        $stmt = $conn->prepare("CALL CancelarPedido(?)");
        $stmt->bind_param("i", $idPedido);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'mensaje' => 'Pedido cancelado correctamente']);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Error al cancelar el pedido']);
        }
        $stmt->close();
        exit;
    }
}

// ===== FILTROS =====
$fechaFiltro = $_GET['fecha'] ?? '';
$estatusFiltro = $_GET['estatus'] ?? '';

// Construir query con filtros
$query = "SELECT * FROM VistaPedidos WHERE 1=1";

if (!empty($fechaFiltro)) {
    $fecha = $conn->real_escape_string($fechaFiltro);
    $query .= " AND DATE(Fecha) = '$fecha'";
}

if (!empty($estatusFiltro)) {
    $estatus = $conn->real_escape_string($estatusFiltro);
    $query .= " AND Estatus = '$estatus'";
}

$query .= " ORDER BY Fecha DESC";

// Ejecutar query
$pedidos = [];
$result = $conn->query($query);
if ($result) {
    $pedidos = $result->fetch_all(MYSQLI_ASSOC);
}

// Agrupar los pedidos por idPedido
$pedidosAgrupados = [];
foreach ($pedidos as $p) {
    $id = $p['idPedido'];
    if (!isset($pedidosAgrupados[$id])) {
        $pedidosAgrupados[$id] = [
            'idPedido' => $id,
            'Fecha' => $p['Fecha'],
            'FechaEntrega' => $p['FechaEntrega'],
            'Estatus' => $p['Estatus'],
            'Cliente' => $p['Cliente'],
            'Platillos' => []
        ];
    }
    $pedidosAgrupados[$id]['Platillos'][] = [
        'Nombre' => $p['Platillo'],
        'Cantidad' => $p['Cantidad'],
        'PrecioUnitario' => $p['PrecioUnitario'],
        'Total' => $p['Total']
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ChilMole — Pedidos Administradores</title>
<link rel="stylesheet" href="ListaPedidosAdministradores.css">
</head>
<body>

<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-img"><img src="Imagenes/Lele.png" alt="Logo ChilMole" /></div>
            <div>
                <div class="brand-title">ChilMole</div>
                <div class="brand-sub">Administrador</div>
            </div>
        </div>

        <nav class="nav">
            <ul>
                <li class="nav-item"><a href="InicioAdministradores.php">Inicio</a></li>
                <li class="nav-item"><a href="ListaPlatillosAdministradores.php">Platillos</a></li>
                <li class="nav-item"><a href="ListaRecetasAdministradores.php">Recetas</a></li>
                <li class="nav-item"><a href="ListaIngredienteAdministradores.php">Ingredientes</a></li>
                <li class="nav-item active"><a href="ListaPedidosAdministradores.php">Pedidos</a></li>
                <li class="nav-item"><a href="ListaUsuariosAdministradores.php">Usuarios</a></li>
                <li class="nav-item"><a href="ListaVentasAdministradores.php">Ventas</a></li>
                <li class="nav-item"><a href="ListaNotificacionesAdministradores.php">Notificaciones</a></li>
                <li class="nav-item"><a href="Login.php">Salir</a></li>
            </ul>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main">
        <header class="topbar">
            <h2 class="page-title">Lista de Pedidos</h2>
        </header>

        <!-- FILTROS -->
        <section class="filters">
            <form method="GET">
                <input type="date" name="fecha" value="<?= htmlspecialchars($fechaFiltro) ?>" class="filter-date">
                <select name="estatus" class="filter-select">
                    <option value="">Todos los estatus</option>
                    <option value="Pendiente" <?= $estatusFiltro=='Pendiente' ? 'selected':'' ?>>Pendiente</option>
                    <option value="Entregado" <?= $estatusFiltro=='Entregado' ? 'selected':'' ?>>Entregado</option>
                    <option value="Cancelado" <?= $estatusFiltro=='Cancelado' ? 'selected':'' ?>>Cancelado</option>
                </select>
                <button type="submit" class="filter-btn">Buscar</button>
            </form>
        </section>

        <!-- GRID PEDIDOS -->
        <section class="grid-pedidos">
            <?php foreach($pedidosAgrupados as $pedido): ?>
            <div class="pedido-card" onclick='openModal(<?= json_encode($pedido) ?>)'>
                <div class="pedido-header">
                    <span class="pedido-id">Pedido #<?= $pedido['idPedido'] ?></span>
                    <span class="pedido-status <?= strtolower($pedido['Estatus']) ?>"><?= $pedido['Estatus'] ?></span>
                </div>
                <p class="pedido-fecha">Fecha: <?= $pedido['Fecha'] ?></p>
                <p class="pedido-entrega">Entrega: <?= $pedido['FechaEntrega'] ?></p>
                <p class="pedido-cliente">Cliente: <?= $pedido['Cliente'] ?></p>
            </div>
            <?php endforeach; ?>
        </section>

    
    </main>
</div>

<!-- MODAL PEDIDO -->
<div class="modal" id="modalPedido">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">✖</button>
        <h2>Detalles del Pedido</h2>
        <div id="modalContent"></div>
        <div class="modal-actions">
            <button class="btn-cobrar" onclick="alert('Pedido atendido')">Atender</button>
            <button class="btn-cancelar" id="btnCancelar">Cancelar</button>
        </div>
    </div>
</div>

<script>
let pedidoActualId = null;

function openModal(pedido) {
    pedidoActualId = pedido.idPedido;
    const modal = document.getElementById('modalPedido');
    const content = document.getElementById('modalContent');

    let html = `<p><strong>Pedido:</strong> #${pedido.idPedido}</p>`;
    html += `<p><strong>Cliente:</strong> ${pedido.Cliente}</p>`;
    html += `<p><strong>Fecha:</strong> ${pedido.Fecha}</p>`;
    html += `<p><strong>Entrega:</strong> ${pedido.FechaEntrega}</p>`;
    html += `<p><strong>Estatus:</strong> ${pedido.Estatus}</p>`;
    html += `<h3>Platillos Solicitados:</h3><ul>`;
    pedido.Platillos.forEach(p => {
        html += `<li>${p.Nombre} — ${p.Cantidad} pza(s) — $${p.PrecioUnitario} c/u</li>`;
    });
    html += `</ul>`;
    const total = pedido.Platillos.reduce((sum, p) => sum + parseFloat(p.Total), 0);
    html += `<p><strong>Total:</strong> $${total.toFixed(2)}</p>`;
    content.innerHTML = html;

    document.querySelector('.btn-cobrar').style.display =
    document.querySelector('#btnCancelar').style.display =
        (pedido.Estatus === 'Pendiente') ? 'inline-block' : 'none';

    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('modalPedido').classList.remove('active');
}

// ===== BOTÓN CANCELAR =====
document.getElementById('btnCancelar').addEventListener('click', function(){
    if(!pedidoActualId) return;
    if(!confirm('¿Desea cancelar este pedido?')) return;

    const formData = new FormData();
    formData.append('accion','cancelar');
    formData.append('idPedido', pedidoActualId);

    fetch('', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) location.reload();
            else alert(data.mensaje);
        })
        .catch(err => alert('Error al procesar la solicitud'));
});
</script>

</body>
</html>
