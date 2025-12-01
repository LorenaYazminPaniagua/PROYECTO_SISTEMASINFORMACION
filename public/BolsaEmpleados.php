<?php
session_start();

if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 2) {
    header("Location: Login.php");
    exit();
}

$idPersona = intval($_SESSION['idPersona']);

// Conexión
require_once "../includes/conexion.php";

// Para mostrar mensajes en la misma página (errores / avisos / éxito)
$messages = [
    'errors' => [],
    'success' => []
];

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * safe_prepare: intenta preparar una sentencia, si falla limpia resultados pendientes y reintenta.
 * Devuelve el statement preparado o false.
 */
function safe_prepare($conn, $sql) {
    $st = $conn->prepare($sql);
    if ($st !== false) {
        return $st;
    }

    // Intentar limpiar resultados pendientes (caso típico con CALL/PROCEDIMIENTOS almacenados)
    while ($conn->more_results() && $conn->next_result()) {
        $res = $conn->store_result();
        if ($res instanceof mysqli_result) {
            $res->free();
        }
    }

    // Reintentar prepare
    $st = $conn->prepare($sql);
    if ($st !== false) {
        return $st;
    }

    // Registrar error para debugging (no interrumpe la ejecución)
    error_log("safe_prepare failed for SQL: {$sql} | mysqli_error: " . $conn->error);
    return false;
}

// Asegurar que exista carrito
$sql = "SELECT idCarrito FROM Carrito WHERE idPersona = ? LIMIT 1";
$stmt = safe_prepare($conn, $sql);
if ($stmt !== false) {
    $stmt->bind_param("i", $idPersona);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 0) {
            $ins = safe_prepare($conn, "INSERT INTO Carrito (idPersona) VALUES (?)");
            if ($ins !== false) {
                $ins->bind_param("i", $idPersona);
                $ins->execute();
                $ins->close();
                // limpiar resultados si hubiera
                while ($conn->more_results()) $conn->next_result();
            } else {
                $messages['errors'][] = "No se pudo crear el carrito (prepare fallo).";
            }
        }
        if ($res instanceof mysqli_result) $res->free();
    } else {
        $messages['errors'][] = "Error al consultar carrito: " . $stmt->error;
    }
    $stmt->close();
} else {
    $messages['errors'][] = "Error al preparar la consulta para buscar carrito: " . $conn->error;
}

/* ============================
   FUNCIONES AUXILIARES
   ============================ */

function obtener_stock_total_por_platillo($conn, $idPlatillo) {
    $sql = "SELECT COALESCE(SUM(CantidadDisponible),0) AS StockTotal FROM DetallePlatillo WHERE idPlatillo = ?";
    $st = safe_prepare($conn, $sql);
    if ($st === false) {
        return 0;
    }
    $st->bind_param("i", $idPlatillo);
    if (!$st->execute()) {
        error_log("Error ejecutar obtener_stock_total_por_platillo: " . $st->error);
        $st->close();
        return 0;
    }
    $res = $st->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $st->close();
    return floatval($row['StockTotal'] ?? 0);
}

function obtener_cantidad_en_carrito($conn, $idPersona, $idPlatillo) {
    $sql = "SELECT COALESCE(cd.Cantidad,0) AS Cantidad
            FROM CarritoDetalle cd
            JOIN Carrito c ON cd.idCarrito = c.idCarrito
            WHERE c.idPersona = ? AND cd.idPlatillo = ? LIMIT 1";
    $st = safe_prepare($conn, $sql);
    if ($st === false) {
        return 0;
    }
    $st->bind_param("ii", $idPersona, $idPlatillo);
    if (!$st->execute()) {
        error_log("Error ejecutar obtener_cantidad_en_carrito: " . $st->error);
        $st->close();
        return 0;
    }
    $res = $st->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $st->close();
    return floatval($row['Cantidad'] ?? 0);
}

function obtener_carrito_completo($conn, $idPersona) {
    $carrito = [];
    // Usamos CALL con manejo de resultados pendiente
    $sql = "CALL ObtenerCarritoPorPersona(" . intval($idPersona) . ")";
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                while ($row = $result->fetch_assoc()) {
                    $carrito[] = $row;
                }
                $result->free();
            }
        } while ($conn->next_result());
    } else {
        error_log("Error multi_query obtener_carrito_completo: " . $conn->error);
    }
    return $carrito;
}

/* ============================
   PROCESAR ACCIONES (sumar/restar/venta/pedido)
   ============================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    // ====== SUMAR (agregar 1 unidad) ======
    if ($accion === 'sumar' && isset($_POST['idPlatillo'])) {
        $idPlatillo = intval($_POST['idPlatillo']);

        // Antes validábamos stock aquí — ahora permitimos agregar al carrito cualquier cantidad.
        // Simplemente llamamos al SP que incrementa la cantidad en carrito.
        $sql = "CALL SumarCantidadCarrito(?, ?)";
        $st = safe_prepare($conn, $sql);
        if ($st) {
            $st->bind_param("ii", $idPersona, $idPlatillo);
            if (!$st->execute()) {
                $messages['errors'][] = "Error al sumar al carrito: " . $st->error;
            } else {
                $messages['success'][] = "Se agregó 1 unidad al carrito.";
            }
            $st->close();
            while ($conn->more_results()) $conn->next_result();
        } else {
            $messages['errors'][] = "Error al preparar la operación de sumar: " . $conn->error;
        }
    }

    // ====== RESTAR (quitar 1 unidad) ======
    if ($accion === 'restar' && isset($_POST['idPlatillo'])) {
        $idPlatillo = intval($_POST['idPlatillo']);

        $sql = "CALL RestarCantidadCarrito(?, ?)";
        $st = safe_prepare($conn, $sql);
        if ($st) {
            $st->bind_param("ii", $idPersona, $idPlatillo);
            if (!$st->execute()) {
                $messages['errors'][] = "Error al restar del carrito: " . $st->error;
            } else {
                $messages['success'][] = "Se restó 1 unidad del carrito.";
            }
            $st->close();
            while ($conn->more_results()) $conn->next_result();
        } else {
            $messages['errors'][] = "Error al preparar la operación de restar: " . $conn->error;
        }
    }

    // ====== VENTA ======
    if ($accion === 'venta') {
        $tipoPago = 'Efectivo';
        $detalle = [];

        // Obtener carrito actual
        $carritoSP = obtener_carrito_completo($conn, $idPersona);
        foreach ($carritoSP as $row) {
            $detalle[] = [
                "idPlatillo" => intval($row['idPlatillo']),
                "Cantidad" => floatval($row['Cantidad']),
                "PrecioUnitario" => floatval($row['PrecioUnitario'])
            ];
        }

        if (count($detalle) === 0) {
            $messages['errors'][] = "No hay productos en el carrito para realizar la venta.";
        } else {
            // Validación de stock (adicional) — se mantiene: no se puede vender sin stock
            $excede = false;
            foreach ($detalle as $item) {
                $stock = obtener_stock_total_por_platillo($conn, $item['idPlatillo']);
                if ($item['Cantidad'] > $stock) {
                    $messages['errors'][] = "No hay suficiente stock para '{$item['idPlatillo']}' — solicitaste {$item['Cantidad']}, disponible {$stock}.";
                    $excede = true;
                }
            }

            if (!$excede) {
                $jsonDetalle = json_encode($detalle, JSON_UNESCAPED_UNICODE);

                $stmt = safe_prepare($conn, "CALL RegistrarVenta(?, ?, ?)");
                if (!$stmt) {
                    $messages['errors'][] = "Error al preparar RegistrarVenta: " . $conn->error;
                } else {
                    $stmt->bind_param("iss", $idPersona, $tipoPago, $jsonDetalle);
                    if (!$stmt->execute()) {
                        $messages['errors'][] = "No se pudo completar la venta: " . $stmt->error;
                    } else {
                        // Éxito: limpiamos carrito (si tu SP no lo hace)
                        $del = safe_prepare($conn, "DELETE FROM CarritoDetalle WHERE idCarrito = (SELECT idCarrito FROM Carrito WHERE idPersona = ? LIMIT 1)");
                        if ($del) {
                            $del->bind_param("i", $idPersona);
                            $del->execute();
                            $del->close();
                        } else {
                            // No fatal: solo informamos
                            error_log("No se pudo preparar DELETE CarritoDetalle: " . $conn->error);
                        }
                        $messages['success'][] = "Venta realizada correctamente.";
                    }
                    $stmt->close();
                    while ($conn->more_results()) $conn->next_result();
                }
            }
        }
    }

    // ====== PEDIDO ======
    if ($accion === 'pedido') {
        $fechaEntrega = $_POST['fechaEntrega'] ?? null;
        if (!$fechaEntrega) {
            $messages['errors'][] = "Debe seleccionar fecha de entrega.";
        } else {
            $detalle = [];
            $carritoSP = obtener_carrito_completo($conn, $idPersona);
            foreach ($carritoSP as $row) {
                $detalle[] = [
                    "idPlatillo" => intval($row['idPlatillo']),
                    "Cantidad" => floatval($row['Cantidad']),
                    "PrecioUnitario" => floatval($row['PrecioUnitario'])
                ];
            }

            if (count($detalle) === 0) {
                $messages['errors'][] = "No hay productos en el carrito.";
            } else {
                // Eliminamos la validación de stock aquí para permitir crear pedidos aunque excedan el inventario.
                $jsonDetalle = json_encode($detalle, JSON_UNESCAPED_UNICODE);
                $stmt = safe_prepare($conn, "CALL CrearPedido(?, ?, ?)");
                if (!$stmt) {
                    $messages['errors'][] = "Error al preparar CrearPedido: " . $conn->error;
                } else {
                    $stmt->bind_param("iss", $idPersona, $fechaEntrega, $jsonDetalle);
                    if (!$stmt->execute()) {
                        $messages['errors'][] = "Error al crear el pedido: " . $stmt->error;
                    } else {
                        // Limpiar carrito localmente
                        $delq = "DELETE FROM CarritoDetalle WHERE idCarrito = (SELECT idCarrito FROM Carrito WHERE idPersona = ? LIMIT 1)";
                        $del = safe_prepare($conn, $delq);
                        if ($del) {
                            $del->bind_param("i", $idPersona);
                            $del->execute();
                            $del->close();
                        } else {
                            error_log("No se pudo preparar DELETE CarritoDetalle al crear pedido: " . $conn->error);
                        }
                        $messages['success'][] = "Pedido creado correctamente.";
                    }
                    $stmt->close();
                    while ($conn->more_results()) $conn->next_result();
                }
            }
        }
    }
}

/* ============================
   TRAER CARRITO (para mostrar)
   ============================ */
$carrito = obtener_carrito_completo($conn, $idPersona);

$totalPagar = 0;
foreach ($carrito as $item) {
    $totalPagar += floatval($item['Total'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bolsa de Compras — Empleado</title>
<link rel="stylesheet" href="BolsaEmpleados.css">
<style>
/* ===== MODAL ===== */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0; top: 0;
    width: 100%; height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}
.modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 20px;
    border-radius: 10px;
    width: 300px;
    text-align: center;
}
/* Mensajes */
.msg-container { max-width: 960px; margin: 16px auto; padding: 0 16px; }
.msg-error { background: #ffdede; color: #900; border: 1px solid #f5a6a6; padding: 8px 12px; border-radius: 6px; margin-bottom:8px; }
.msg-success { background: #e6ffea; color: #066; border: 1px solid #9fe2b6; padding: 8px 12px; border-radius: 6px; margin-bottom:8px; }
</style>
</head>

<body>

<div class="app">

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="brand">
        <div class="brand-img"><img src="Imagenes/Lele.png" alt="logo"></div>
        <div>
            <div class="brand-title">ChilMole</div>
            <div class="brand-sub">Moles Tradicionales</div>
        </div>
    </div>

    <nav class="nav">
        <ul>
            <li class="nav-item"><a href="InicioEmpleado.php">Inicio</a></li>
            <li class="nav-item active"><a href="ListaPlatillosEmpleados.php">Platillos</a></li>
            <li class="nav-item"><a href="ListaPedidosEmpleados.php">Pedidos</a></li>
            <li class="nav-item"><a href="ListaVentasEmpleados.php">Ventas</a></li>
            <li class="nav-item"><a href="Login.php">Salir</a></li>
        </ul>
    </nav>
</aside>

<!-- MAIN -->
<main class="main">

<header class="topbar">
    <h2 class="page-title">Bolsa de Compras</h2>
    <div class="top-actions">
        <div class="profile">
            <div>
                <div class="profile-name"><?= htmlspecialchars($_SESSION['Nombre'] ?? 'Empleado') ?></div>
                <div class="profile-role">Empleado</div>
            </div>
            <div class="avatar">👨‍🍳</div>
        </div>
    </div>
</header>

<div class="msg-container">
    <?php foreach($messages['errors'] as $err): ?>
        <div class="msg-error"><?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>
    <?php foreach($messages['success'] as $ok): ?>
        <div class="msg-success"><?= htmlspecialchars($ok) ?></div>
    <?php endforeach; ?>
</div>

<section class="table-section">

    <table class="devoluciones-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Total</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php if (count($carrito) === 0): ?>
                <tr><td colspan="5" style="text-align:center">No hay productos en el carrito.</td></tr>
            <?php else: ?>
                <?php foreach($carrito as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['Producto'] ?? ''); ?></td>
                    <td><?= htmlspecialchars($item['Cantidad'] ?? '0'); ?></td>
                    <td><?= number_format(floatval($item['PrecioUnitario'] ?? 0),2); ?></td>
                    <td><?= number_format(floatval($item['Total'] ?? 0),2); ?></td>

                    <td style="display:flex; gap:5px;">
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="accion" value="sumar">
                            <input type="hidden" name="idPlatillo" value="<?= intval($item['idPlatillo'] ?? 0); ?>">
                            <button type="submit" class="btn-detalle">+</button>
                        </form>

                        <form method="POST" style="display:inline">
                            <input type="hidden" name="accion" value="restar">
                            <input type="hidden" name="idPlatillo" value="<?= intval($item['idPlatillo'] ?? 0); ?>">
                            <button type="submit" class="btn-detalle">-</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="total-actions-container">
        <div class="stat-card">
            <strong>Total a Pagar: $<?= number_format($totalPagar,2); ?></strong>
        </div>

        <div class="action-buttons">
            <form method="POST" style="display:inline">
                <input type="hidden" name="accion" value="venta">
                <button type="submit" class="btn-nueva-devolucion">Hacer Venta (Efectivo)</button>
            </form>

            <!-- Botón con type=button para abrir modal -->
            <button type="button" class="btn-nueva-devolucion" id="btnPedido">Hacer Pedido</button>
        </div>
    </div>

</section>

</main>
</div>

<!-- MODAL -->
<div id="modalFecha" class="modal">
    <div class="modal-content">
        <h3>Selecciona la Fecha de Entrega</h3>
        <form method="POST" id="formPedido">
            <input type="date" name="fechaEntrega" required>
            <input type="hidden" name="accion" value="pedido">
            <br><br>
            <button type="submit">Confirmar Pedido</button>
            <button type="button" onclick="cerrarModal()">Cancelar</button>
        </form>
    </div>
</div>

<script>
const btnPedido = document.getElementById('btnPedido');
const modal = document.getElementById('modalFecha');

btnPedido && (btnPedido.onclick = () => {
    modal.style.display = "block";
});

function cerrarModal(){
    modal.style.display = "none";
}

window.onclick = function(e){
    if(e.target == modal){
        cerrarModal();
    }
};
</script>
</body>
</html>
