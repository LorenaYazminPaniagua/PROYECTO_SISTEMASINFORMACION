<?php
session_start();

if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 2) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado (variable de sesión para procedimientos que la usen)
$conn->query("SET @id_usuario_actual = " . intval($idPersona));

// ===== LLAMADA AJAX PARA CANCELAR / ATENDER PEDIDO =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    header('Content-Type: application/json; charset=utf-8');

    if ($accion === 'cancelar' && isset($_POST['idPedido'])) {
        $idPedido = intval($_POST['idPedido']);

        $stmt = $conn->prepare("CALL CancelarPedido(?)");
        if (!$stmt) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al preparar la consulta: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("i", $idPedido);

        if ($stmt->execute()) {
            while ($conn->more_results() && $conn->next_result()) {}
            $stmt->close();
            echo json_encode(['success' => true, 'mensaje' => 'Pedido cancelado correctamente']);
        } else {
            // intentar obtener el mensaje de error (stmt o conexión)
            $err = $stmt->error ?: $conn->error;
            // limpiar posibles resultsets
            while ($conn->more_results() && $conn->next_result()) {}
            $stmt->close();
            echo json_encode(['success' => false, 'mensaje' => 'Error al cancelar el pedido: ' . $err]);
        }
        exit;
    }

    if ($accion === 'atender' && isset($_POST['idPedido']) && isset($_POST['idPersonaPedido'])) {
        $idPedido = intval($_POST['idPedido']);
        $idPersonaPedido = intval($_POST['idPersonaPedido']);
        $tipoPago = 'Efectivo';

        // Usar el nuevo procedimiento que recibe idPedido
        $stmt = $conn->prepare("CALL RegistrarVentaDesdePedido(?, ?, ?)");
        if (!$stmt) {
            echo json_encode(['success' => false, 'mensaje' => 'Error al preparar la consulta: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("iis", $idPedido, $idPersonaPedido, $tipoPago);

        if ($stmt->execute()) {
            // limpiar todos los resultsets que el procedimiento pueda haber dejado
            while ($conn->more_results() && $conn->next_result()) {}
            $stmt->close();
            echo json_encode(['success' => true, 'mensaje' => 'Pedido atendido y venta registrada correctamente']);
        } else {
            /*
             * Aquí capturamos errores: cuando el procedimiento hace SIGNAL
             * MySQL devuelve el mensaje de error en $conn->error o $stmt->error.
             * Intentamos devolver el mensaje tal cual para mostrarlo en el frontend.
             */
            $errorMsg = $stmt->error ?: $conn->error;

            // En algunos casos mysqli devuelve la información del código de error en la conexión
            // si es necesario, podemos añadir el código numérico también:
            $errno = $conn->errno ?: $stmt->errno;

            // limpiar resultsets si hay (defensivo)
            while ($conn->more_results() && $conn->next_result()) {}

            $stmt->close();

            // Si detectamos el código 1644 (SIGNAL con SQLSTATE personalizado) o simplemente hay mensaje,
            // devolvemos el mensaje tal cual para que el frontend lo muestre.
            if (!empty($errorMsg)) {
                echo json_encode(['success' => false, 'mensaje' => $errorMsg]);
            } else {
                echo json_encode(['success' => false, 'mensaje' => 'Error al atender el pedido. Código: ' . $errno]);
            }
        }
        exit;
    }

    echo json_encode(['success' => false, 'mensaje' => 'Acción inválida o parámetros faltantes']);
    exit;
}

// ===== FIN AJAX =====

// Inicializar filtros
$fechaFiltro = $_GET['fecha'] ?? '';
$estatusFiltro = $_GET['estatus'] ?? '';

// Construir query con filtros
$query = "SELECT * FROM VistaPedidos WHERE 1=1";
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

// Agrupar pedidos por idPedido
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
            'idPersona' => $p['idPersona'] ?? null,
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
<title>ChilMole — Pedidos Empleados</title>
<link rel="stylesheet" href="ListaPedidosEmpleados.css">
</head>
<body>

<div class="app">
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
                <li class="nav-item active"><a href="ListaPedidosEmpleados.php">Pedidos</a></li>
                <li class="nav-item"><a href="ListaVentasEmpleados.php">Ventas</a></li>
                <li class="nav-item"><a href="Login.php">Salir</a></li>
            </ul>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h2 class="page-title">Lista pedidos</h2>
            <div class="top-actions">
                <div class="profile">
                    <div>
                        <div class="profile-name"><?php echo htmlspecialchars($_SESSION['Nombre'] ?? 'Empleado'); ?></div>
                        <div class="profile-role">Empleado</div>
                    </div>
                    <div class="avatar">👨‍🍳</div>
                </div>
            </div>
        </header>

        <section class="filters">
            <form method="get">
                <input type="date" name="fecha" value="<?php echo htmlspecialchars($fechaFiltro); ?>" class="filter-date">
                <select name="estatus" class="filter-select">
                    <option value="">Todos los estatus</option>
                    <option value="Pendiente" <?php if($estatusFiltro=='Pendiente') echo 'selected'; ?>>Pendiente</option>
                    <option value="Entregado" <?php if($estatusFiltro=='Entregado') echo 'selected'; ?>>Entregado</option>
                    <option value="Cancelado" <?php if($estatusFiltro=='Cancelado') echo 'selected'; ?>>Cancelado</option>
                </select>
                <button type="submit" class="filter-btn">Buscar</button>
            </form>
        </section>

        <section class="grid-pedidos">
            <?php foreach($pedidosAgrupados as $pedido): ?>
            <div class="pedido-card" onclick='openModal(<?php echo json_encode($pedido, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'>
                <div class="pedido-header">
                    <span class="pedido-id">Pedido #<?php echo $pedido['idPedido']; ?></span>
                    <span class="pedido-status <?php echo strtolower($pedido['Estatus']); ?>"><?php echo $pedido['Estatus']; ?></span>
                </div>
                <p class="pedido-fecha"><?php echo $pedido['Fecha']; ?></p>
                <p class="pedido-entrega">Entrega: <?php echo $pedido['FechaEntrega']; ?></p>
                <p class="pedido-cliente">Empleado: <?php echo htmlspecialchars($pedido['Cliente']); ?></p>
            </div>
            <?php endforeach; ?>
        </section>
    </main>
</div>

<div class="modal" id="modalPedido">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">✖</button>
        <h2>Detalles del Pedido</h2>
        <div id="modalContent"></div>
        <div class="modal-actions" id="modalButtons">
            <button class="btn-cobrar" id="btnAtender">Atender</button>
            <button class="btn-cancelar" id="btnCancelar">Cancelar</button>
        </div>
    </div>
</div>

<script>
let pedidoActualId = null;
let pedidoActualIdPersona = null;

function openModal(pedido) {
    pedidoActualId = pedido.idPedido;
    pedidoActualIdPersona = pedido.idPersona ?? null;

    const modal = document.getElementById('modalPedido');
    const content = document.getElementById('modalContent');
    const buttons = document.getElementById('modalButtons');

    let html = `<p><strong>Pedido:</strong> #${pedido.idPedido}</p>`;
    html += `<p><strong>Cliente:</strong> ${escapeHtml(pedido.Cliente)}</p>`;
    html += `<p><strong>Fecha:</strong> ${escapeHtml(pedido.Fecha)}</p>`;
    html += `<p><strong>Entrega:</strong> ${escapeHtml(pedido.FechaEntrega)}</p>`;
    html += `<p><strong>Estatus:</strong> ${escapeHtml(pedido.Estatus)}</p>`;
    html += `<h3>Platillos Solicitados:</h3><ul>`;
    pedido.Platillos.forEach(p => {
        html += `<li>${escapeHtml(p.Nombre)} — ${escapeHtml(p.Cantidad)} pza(s) — $${escapeHtml(p.PrecioUnitario)} c/u</li>`;
    });
    html += `</ul>`;
    const total = pedido.Platillos.reduce((sum, p) => sum + parseFloat(p.Total || 0), 0);
    html += `<p><strong>Total:</strong> $${total.toFixed(2)}</p>`;
    content.innerHTML = html;

    buttons.style.display = pedido.Estatus === "Pendiente" ? "flex" : "none";
    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('modalPedido').classList.remove('active');
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text).replace(/&/g, "&amp;")
                       .replace(/</g, "&lt;")
                       .replace(/>/g, "&gt;")
                       .replace(/"/g, "&quot;")
                       .replace(/'/g, "&#039;");
}

document.getElementById('btnCancelar').addEventListener('click', function(){
    if(!pedidoActualId || !confirm('¿Desea cancelar este pedido?')) return;

    const formData = new FormData();
    formData.append('accion','cancelar');
    formData.append('idPedido', pedidoActualId);

    fetch(window.location.pathname, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => { alert(data.mensaje); if(data.success) location.reload(); })
        .catch(err => { console.error(err); alert('Error al procesar la solicitud'); });
});

document.getElementById('btnAtender').addEventListener('click', function(){
    if(!pedidoActualId || !pedidoActualIdPersona || !confirm('¿Marcar este pedido como atendido y registrar venta?')) return;
    
    const formData = new FormData();
    formData.append('accion','atender');
    formData.append('idPedido', pedidoActualId);
    formData.append('idPersonaPedido', pedidoActualIdPersona);

    fetch(window.location.pathname, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => { alert(data.mensaje); if(data.success) location.reload(); })
        .catch(err => { console.error(err); alert('Error al procesar la solicitud'); });
});
</script>
</body>
</html>
