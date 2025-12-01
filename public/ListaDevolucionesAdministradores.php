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

// FILTROS
$estatusFiltro = isset($_GET['estatus']) ? $_GET['estatus'] : '';
$fechaFiltro = isset($_GET['fecha']) ? $_GET['fecha'] : '';

$where = [];
if($estatusFiltro != ''){
    $where[] = "d.Estatus = '". $conn->real_escape_string($estatusFiltro) ."'";
}
if($fechaFiltro != ''){
    $where[] = "DATE(d.Fecha) = '". $conn->real_escape_string($fechaFiltro) ."'";
}
$whereSql = '';
if(count($where) > 0){
    $whereSql = "WHERE ". implode(" AND ", $where);
}

// OBTENER DEVOLUCIONES
$sql = "
SELECT *
FROM VistaDevoluciones d
INNER JOIN Devolucion dd ON d.idDevolucion = dd.idDevolucion
".$whereSql."
ORDER BY d.Fecha DESC
";
$result = $conn->query($sql);

$devoluciones = [];
if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $id = $row['idDevolucion'];
        if(!isset($devoluciones[$id])){
            $devoluciones[$id] = [
                'id' => $id,
                'fecha' => $row['Fecha'],
                'motivo' => $row['Motivo'],
                'cliente' => $row['Cliente'],
                'estatus' => $row['Estatus'] ?? 'Pendiente',
                'articulos' => [],
                'total' => 0
            ];
        }
        $devoluciones[$id]['articulos'][] = [
            'platillo' => $row['Platillo'],
            'cantidad' => $row['CantidadDevuelta'],
            'total' => $row['TotalDevuelto']
        ];
        $devoluciones[$id]['total'] += $row['TotalDevuelto'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChilMole — Devoluciones</title>
    <link rel="stylesheet" href="ListaDevolucionesAdministradores.css">
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
                <li class="nav-item"><a href="InicioAdministradores.php">Inicio</a></li>
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

    <!-- MAIN -->
    <main class="main">

        <!-- TOP BAR -->
        <header class="topbar">
            <h2 class="page-title">Lista de Devoluciones</h2>
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

        <!-- FILTROS -->
        <section class="filters">
            <form method="GET">
                <input type="date" name="fecha" value="<?php echo htmlspecialchars($fechaFiltro); ?>" class="filter-date">
                <select name="estatus" class="filter-select">
                    <option value="">Todos los estatus</option>
                    <option value="Pendiente" <?php if($estatusFiltro=='Pendiente') echo 'selected'; ?>>Pendiente</option>
                    <option value="Aprobada" <?php if($estatusFiltro=='Aprobada') echo 'selected'; ?>>Aprobada</option>
                    <option value="Rechazada" <?php if($estatusFiltro=='Rechazada') echo 'selected'; ?>>Rechazada</option>
                </select>
                <button type="submit" class="filter-btn">Buscar</button>
            </form>
        </section>

        <!-- GRID DE DEVOLUCIONES -->
        <section class="grid-devoluciones">
        <?php if(count($devoluciones) > 0): ?>
            <?php foreach($devoluciones as $d): ?>
            <div class="devol-card" data-id="<?php echo $d['id']; ?>" data-articulos='<?php echo json_encode($d['articulos']); ?>' data-cliente="<?php echo $d['cliente']; ?>" data-motivo="<?php echo $d['motivo']; ?>" data-fecha="<?php echo $d['fecha']; ?>" data-total="<?php echo $d['total']; ?>" data-estatus="<?php echo $d['estatus']; ?>">
                <div class="devol-header">
                    <span class="devol-id">Devolución #<?php echo $d['id']; ?></span>
                    <span class="devol-status <?php echo strtolower($d['estatus']); ?>"><?php echo $d['estatus']; ?></span>
                </div>
                <p class="devol-fecha">Fecha: <?php echo $d['fecha']; ?></p>
                <p class="devol-motivo"><strong>Motivo:</strong> <?php echo $d['motivo']; ?></p>
                <p class="devol-cliente"><strong>Cliente:</strong> <?php echo $d['cliente']; ?></p>
                <div class="devol-actions">
                    <button class="btn-detalle" onclick="openModal(this)">Ver Detalles</button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay devoluciones que coincidan con los filtros.</p>
        <?php endif; ?>
        </section>



    </main>
</div>

<!-- MODAL DETALLE DEVOLUCIÓN -->
<div class="modal" id="modalDevolucion">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">✖</button>
        <h2>Detalles de la Devolución</h2>
        <p class="modal-info"><strong>Devolución:</strong> <span id="modal-id"></span></p>
        <p class="modal-info"><strong>Cliente:</strong> <span id="modal-cliente"></span></p>
        <p class="modal-info"><strong>Motivo:</strong> <span id="modal-motivo"></span></p>
        <p class="modal-info"><strong>Fecha:</strong> <span id="modal-fecha"></span></p>
        <h3 class="modal-subtitle">Artículos Devueltos:</h3>
        <ul class="platillos-lista" id="modal-articulos"></ul>
        <p class="modal-total">Total devuelto: <strong>$<span id="modal-total"></span></strong></p>
    </div>
</div>

<script>
function openModal(btn){
    const card = btn.closest('.devol-card');
    document.getElementById('modal-id').innerText = card.dataset.id;
    document.getElementById('modal-cliente').innerText = card.dataset.cliente;
    document.getElementById('modal-motivo').innerText = card.dataset.motivo;
    document.getElementById('modal-fecha').innerText = card.dataset.fecha;
    document.getElementById('modal-total').innerText = parseFloat(card.dataset.total).toFixed(2);

    const articulos = JSON.parse(card.dataset.articulos);
    const ul = document.getElementById('modal-articulos');
    ul.innerHTML = '';
    articulos.forEach(a => {
        const li = document.createElement('li');
        li.textContent = `${a.platillo} — ${a.cantidad} pieza(s) — $${parseFloat(a.total).toFixed(2)}`;
        ul.appendChild(li);
    });

    document.getElementById('modalDevolucion').classList.add('active');
}

function closeModal(){
    document.getElementById('modalDevolucion').classList.remove('active');
}
</script>

</body>
</html>
