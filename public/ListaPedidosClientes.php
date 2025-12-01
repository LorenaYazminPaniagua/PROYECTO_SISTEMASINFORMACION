<?php
session_start();

// Verificar si el usuario es cliente
if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 3) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado
$conn->query("SET @id_usuario_actual = " . intval($idPersona));

// Inicializar filtros
$fechaFiltro = $_GET['fecha'] ?? '';
$estatusFiltro = $_GET['estatus'] ?? '';

// Construir query con filtros SOLO PARA EL USUARIO LOGUEADO
$query = "SELECT * FROM VistaPedidos WHERE idPersona = $idPersona";
if (!empty($fechaFiltro)) {
    $query .= " AND Fecha = '" . $conn->real_escape_string($fechaFiltro) . "'";
}
if (!empty($estatusFiltro)) {
    $query .= " AND Estatus = '" . $conn->real_escape_string($estatusFiltro) . "'";
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

// ===== AJAX para cancelar pedido =====
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ChilMole — Mis Pedidos</title>
<link rel="stylesheet" href="ListaPedidosClientes.css">
</head>
<body>

<div class="app">

    <!-- TOPBAR CLIENTE -->
    <header class="topbar">
        <div class="top-buttons">
          <a href="InicioClientes.php" class="btn-top">Inicio</a>
          <a href="ListaPedidosClientes.php" class="btn-top">Mis Pedidos</a>
          <a href="BolsaClientes.php" class="btn-top">Mi Bolsa</a>
          <a href="Login.php" class="btn-top">Salir</a>
        </div>
        <div class="profile">
            <div>
                <div class="profile-name"><?= htmlspecialchars($_SESSION['Nombre'] ?? 'Cliente'); ?></div>
                <div class="profile-role">Cliente</div>
            </div>
            <div class="avatar">🧑‍🍳</div>
        </div>
    </header>

    <!-- FILTROS -->
    <section class="filters">
        <form method="get">
            <input type="date" name="fecha" value="<?= htmlspecialchars($fechaFiltro); ?>" class="filter-date">
            <select name="estatus" class="filter-select">
                <option value="">Todos los estatus</option>
                <option value="Pendiente" <?= $estatusFiltro=='Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                <option value="Entregado" <?= $estatusFiltro=='Entregado' ? 'selected' : ''; ?>>Entregado</option>
                <option value="Cancelado" <?= $estatusFiltro=='Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
            </select>
            <button type="submit" class="filter-btn">Buscar</button>
        </form>
    </section>

    <!-- GRID PEDIDOS -->
    <section class="grid-pedidos">
        <?php foreach($pedidosAgrupados as $pedido): ?>
        <div class="pedido-card" onclick='openModal(<?= json_encode($pedido); ?>)'>
            <div class="pedido-header">
                <span class="pedido-id">Pedido #<?= $pedido['idPedido']; ?></span>
                <span class="pedido-status <?= strtolower($pedido['Estatus']); ?>"><?= $pedido['Estatus']; ?></span>
            </div>
            <p class="pedido-fecha"><?= $pedido['Fecha']; ?></p>
            <p class="pedido-entrega">Entrega: <?= $pedido['FechaEntrega']; ?></p>
        </div>
        <?php endforeach; ?>
    </section>
</div>

<!-- MODAL PEDIDO -->
<div class="modal" id="modalPedido">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">✖</button>
        <h2>Detalles del Pedido</h2>
        <div id="modalContent"></div>

        <div class="modal-actions" id="modalButtons">
            <button class="btn-cancelar" id="btnCancelar">Cancelar Pedido</button>
        </div>
    </div>
</div>

<script>
let pedidoActualId = null;

function openModal(pedido) {
    pedidoActualId = pedido.idPedido;
    const modal = document.getElementById('modalPedido');
    const content = document.getElementById('modalContent');
    const buttons = document.getElementById('modalButtons');

    let html = `<p><strong>Pedido:</strong> #${pedido.idPedido}</p>`;
    html += `<p><strong>Fecha:</strong> ${pedido.Fecha}</p>`;
    html += `<p><strong>Entrega:</strong> ${pedido.FechaEntrega}</p>`;
    html += `<h3>Platillos Solicitados:</h3><ul>`;
    pedido.Platillos.forEach(p => {
        html += `<li>${p.Nombre} — ${p.Cantidad} pza(s) — $${p.PrecioUnitario} c/u</li>`;
    });
    html += `</ul>`;
    const total = pedido.Platillos.reduce((sum, p) => sum + parseFloat(p.Total), 0);
    html += `<p><strong>Total:</strong> $${total.toFixed(2)}</p>`;
    content.innerHTML = html;

    // Solo mostrar botón cancelar si pendiente
    buttons.style.display = (pedido.Estatus === "Pendiente") ? "flex" : "none";

    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('modalPedido').classList.remove('active');
}

// Cancelar pedido
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
