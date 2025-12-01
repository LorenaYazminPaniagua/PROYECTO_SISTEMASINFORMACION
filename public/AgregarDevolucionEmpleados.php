<?php
session_start();

// Validar que sea empleado (Rol = 2)
if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 2) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Activar manejo de errores de MySQLi EXCEPCIONES
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// =======================
// PROCESAR FORMULARIO
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        $Motivo           = $_POST['Motivo'] ?? null;
        $idVenta         = $_POST['idVenta'] ?? null;
        $idDetalleVenta  = $_POST['idDetalleVenta'] ?? null;
        $CantidadDevuelta = $_POST['CantidadDevuelta'] ?? null;
        $TotalDevuelto    = $_POST['TotalDevuelto'] ?? null;

        // Validaciones básicas
        if (!$Motivo || !$idVenta || !$idDetalleVenta || !$CantidadDevuelta || !$TotalDevuelto) {
            throw new Exception("Faltan campos obligatorios.");
        }

        // Crear JSON para el procedimiento
        $detalle = [
            [
                "idVenta" => intval($idVenta),
                "idDetalleVenta" => intval($idDetalleVenta),
                "CantidadDevuelta" => intval($CantidadDevuelta),
                "TotalDevuelto" => floatval($TotalDevuelto)
            ]
        ];

        $detalleJSON = json_encode($detalle, JSON_UNESCAPED_UNICODE);

        // Preparar llamada al procedimiento
        $stmt = $conn->prepare("CALL RegistrarDevolucion(?, ?, ?)");
        $stmt->bind_param("iss", $idPersona, $Motivo, $detalleJSON);

        // Ejecutar
        $stmt->execute();

        // Limpiar posibles resultados previos del procedure
        while ($stmt->more_results() && $stmt->next_result()) {;}

        echo "<script>
                alert('✔ Devolución registrada correctamente');
                window.location.href='ListaDevolucionesEmpleados.php';
            </script>";
        exit();

    } catch (mysqli_sql_exception $sql_e) {

        // Captura errores SQL del procedimiento
        $msg = addslashes($sql_e->getMessage());

        echo "<script>
                alert('❌ Error SQL: $msg');
            </script>";

    } catch (Exception $e) {

        // Errores generales del PHP
        $msg = addslashes($e->getMessage());

        echo "<script>
                alert('⚠ Error: $msg');
            </script>";
    }
}

// =======================
// CONSULTAS PARA SELECTS
// =======================

try {
    $ventas = $conn->query("SELECT idVenta FROM Venta");

    $detalles = $conn->query("
        SELECT dv.idDetalleVenta, dv.idVenta, p.Nombre
        FROM DetalleVenta dv
        JOIN Platillo p ON p.idPlatillo = dv.idPlatillo
    ");

} catch (mysqli_sql_exception $e) {
    die("Error cargando selects: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Devolución — ChilMole</title>
    <link rel="stylesheet" href="AgregarDevolucionEmpleados.css">
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
                <div class="brand-sub">Empleado</div>
            </div>
        </div>

        <nav class="nav">
            <ul>
                <li class="nav-item"><a href="InicioEmpleado.php">Inicio</a></li>
                <li class="nav-item"><a href="ListaPlatillosEmpleados.php">Platillos</a></li>
                <li class="nav-item"><a href="ListaPedidosEmpleados.php">Pedidos</a></li>
                <li class="nav-item"><a href="ListaVentasEmpleados.php">Ventas</a></li>
                <li class="nav-item"><a href="Login.php">Salir</a></li>
            </ul>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main">

        <!-- TOP BAR -->
        <header class="topbar">
            <h2 class="page-title">Hacer devolución</h2>
            <div class="top-actions">
                <div class="profile">
                    <div>
                        <div class="profile-name">
                            <?= $_SESSION['Nombre'] ?? 'Empleado' ?>
                        </div>
                        <div class="profile-role">Empleado</div>
                    </div>
                    <div class="avatar">👨‍🍳</div>
                </div>
            </div>
        </header>

        <!-- FORMULARIO -->
        <form class="form-add" method="POST">

            <!-- VENTA -->
            <div class="input-wrap">
                <label class="label-text">Venta</label>
                <select name="idVenta" required>
                    <option disabled selected>Seleccionar venta...</option>

                    <?php while($v = $ventas->fetch_assoc()): ?>
                        <option value="<?=$v['idVenta']?>"><?=$v['idVenta']?></option>
                    <?php endwhile; ?>

                </select>
            </div>

            <!-- MOTIVO -->
            <div class="input-wrap">
                <label class="label-text">Motivo</label>
                <input type="text" name="Motivo" placeholder="Ej. Producto dañado" required>
            </div>

            <!-- DETALLES -->
            <h3>Detalles de la devolución</h3>

            <div class="input-wrap">
                <label class="label-text">Producto</label>
                <select name="idDetalleVenta" required>
                    <option disabled selected>Seleccionar producto...</option>

                    <?php while($d = $detalles->fetch_assoc()): ?>
                        <option value="<?=$d['idDetalleVenta']?>">
                            Venta #<?=$d['idVenta']?> — <?=$d['Nombre']?>
                        </option>
                    <?php endwhile; ?>

                </select>
            </div>

            <div class="input-wrap">
                <label class="label-text">Cantidad devuelta</label>
                <input type="number" name="CantidadDevuelta" min="1" required>
            </div>

            <div class="input-wrap">
                <label class="label-text">Total devuelto</label>
                <input type="number" name="TotalDevuelto" min="0" step="0.01" required>
            </div>

            <!-- BOTONES -->
            <div class="btn-row">
                <button class="btn save" type="submit">Registrar Devolución</button>
                <button class="btn cancel" type="button" onclick="history.back()">Cancelar</button>
            </div>

        </form>

    </main>

</div>

</body>
</html>
