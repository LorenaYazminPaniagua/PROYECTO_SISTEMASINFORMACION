<?php
session_start();

// Verificar si el usuario está logueado y es cliente
if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 3) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado
$conn->query("SET @id_usuario_actual = " . intval($idPersona));

// CREAR CARRITO SI NO EXISTE
$sql = "SELECT idCarrito FROM Carrito WHERE idPersona = $idPersona LIMIT 1";
$result = $conn->query($sql);
if ($result->num_rows === 0) {
    $conn->query("INSERT INTO Carrito (idPersona) VALUES ($idPersona)");
}
$result->free();

// PROCESAR ACCIONES (sumar/restar/pedido)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    // SUMAR
    if ($accion === 'sumar' && isset($_POST['idPlatillo'])) {
        $idPlatillo = intval($_POST['idPlatillo']);
        $sql = "CALL SumarCantidadCarrito($idPersona, $idPlatillo)";
        $conn->multi_query($sql);
        while ($conn->more_results()) $conn->next_result();
    }

    // RESTAR
    if ($accion === 'restar' && isset($_POST['idPlatillo'])) {
        $idPlatillo = intval($_POST['idPlatillo']);
        $sql = "CALL RestarCantidadCarrito($idPersona, $idPlatillo)";
        $conn->multi_query($sql);
        while ($conn->more_results()) $conn->next_result();
    }

    // PEDIDO
    if ($accion === 'pedido') {
        $fechaEntrega = $_POST['fechaEntrega'] ?? null;
        if (!$fechaEntrega) {
            die("Debe seleccionar fecha de entrega.");
        }

        $detalle = [];
        $sql = "CALL ObtenerCarritoPorPersona($idPersona)";
        if ($conn->multi_query($sql)) {
            do {
                if ($result = $conn->store_result()) {
                    while ($row = $result->fetch_assoc()) {
                        $detalle[] = [
                            "idPlatillo" => intval($row['idPlatillo']),
                            "Cantidad" => intval($row['Cantidad']),
                            "PrecioUnitario" => floatval($row['PrecioUnitario'])
                        ];
                    }
                    $result->free();
                }
            } while ($conn->next_result());
        }

        if (count($detalle) === 0) {
            die("No hay productos en el carrito.");
        }

        $jsonDetalle = json_encode($detalle);
        $stmt = $conn->prepare("CALL CrearPedido(?, ?, ?)");
        $stmt->bind_param("iss", $idPersona, $fechaEntrega, $jsonDetalle);
        $stmt->execute();
        $stmt->close();

        $conn->query("DELETE FROM CarritoDetalle WHERE idCarrito = (SELECT idCarrito FROM Carrito WHERE idPersona = $idPersona LIMIT 1)");

        $_SESSION['pedido_exitoso'] = true;
        header("Location: BolsaClientes.php");
        exit;
    }
}

// TRAER CARRITO
$carrito = [];
$sql = "CALL ObtenerCarritoPorPersona($idPersona)";
if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            while ($row = $result->fetch_assoc()) {
                $carrito[] = $row;
            }
            $result->free();
        }
    } while ($conn->next_result());
}

$totalPagar = 0;
foreach ($carrito as $item) {
    $totalPagar += floatval($item['Total']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mi Bolsa — Cliente</title>
<link rel="stylesheet" href="BolsaClientes.css">
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
    border-radius: 18px;
    width: 300px;
    text-align: center;
}
</style>
</head>
<body>
<div class="app">

  <main class="main">

    <!-- TOPBAR -->
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

    <h2 style="color:var(--accent); margin-top:20px;">Mi Bolsa de Compras</h2>

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
              <?php foreach($carrito as $item): ?>
              <tr>
                  <td><?= htmlspecialchars($item['Producto']); ?></td>
                  <td><?= $item['Cantidad']; ?></td>
                  <td>$<?= number_format($item['PrecioUnitario'],2); ?></td>
                  <td>$<?= number_format($item['Total'],2); ?></td>
                  <td style="display:flex; gap:5px;">
                      <form method="POST">
                          <input type="hidden" name="accion" value="sumar">
                          <input type="hidden" name="idPlatillo" value="<?= $item['idPlatillo']; ?>">
                          <button type="submit" class="btn-detalle">+</button>
                      </form>

                      <form method="POST">
                          <input type="hidden" name="accion" value="restar">
                          <input type="hidden" name="idPlatillo" value="<?= $item['idPlatillo']; ?>">
                          <button type="submit" class="btn-detalle">-</button>
                      </form>
                  </td>
              </tr>
              <?php endforeach; ?>
          </tbody>
      </table>

      <div class="total-actions-container">
          <div class="stat-card">
              <strong>Total a Pagar: $<?= number_format($totalPagar,2); ?></strong>
          </div>

          <div class="action-buttons">
              <!-- SOLO PEDIDO -->
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

btnPedido.onclick = () => {
    modal.style.display = "block";
};

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
