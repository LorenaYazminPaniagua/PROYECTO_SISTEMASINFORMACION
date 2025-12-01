<?php
session_start();

if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar id del usuario logueado (para triggers, si existen)
$conn->query("SET @id_usuario_actual = " . intval($idPersona));

// ===============================
// VALIDAR ID DEL PLATILLO
// ===============================
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ListaPlatillosAdministradores.php?err=ID inválido");
    exit();
}

$idPlatillo = intval($_GET['id']);


// ===============================
// 1. Verificar que exista
// ===============================
$sql = "SELECT Platillo, Imagen FROM Platillo WHERE idPlatillo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idPlatillo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ListaPlatillosAdministradores.php?err=Platillo no encontrado");
    exit();
}

$data = $result->fetch_assoc();
$nombrePlatillo = $data["Platillo"];
$imagenPlatillo = $data["Imagen"];

$stmt->close();


// ===============================
// 2. Verificar si tiene detalle (ingredientes) asociados
// ===============================
$sql = "SELECT COUNT(*) AS total FROM DetallePlatillo WHERE idPlatillo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idPlatillo);
$stmt->execute();
$detalle = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($detalle["total"] > 0) {
    header("Location: ListaPlatillosAdministradores.php?err=No se puede borrar, el platillo tiene ingredientes registrados");
    exit();
}


// ===============================
// 3. Guardar auditoría ANTES de borrar
// ===============================
$sqlAud = "
INSERT INTO AuditoriaPersona
(Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona)
VALUES
('DELETE', 'Platillo', ?, NULL, ?)
";

$stmt = $conn->prepare($sqlAud);
$datoAnterior = "Platillo: $nombrePlatillo (ID: $idPlatillo)";
$stmt->bind_param("si", $datoAnterior, $idPersona);
$stmt->execute();
$stmt->close();


// ===============================
// 4. Borrar PLATILLO
// ===============================
$sql = "DELETE FROM Platillo WHERE idPlatillo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idPlatillo);

if ($stmt->execute()) {

    // BORRAR IMAGEN si existía
    if (!empty($imagenPlatillo) && file_exists("Imagenes/Platillos/" . $imagenPlatillo)) {
        unlink("Imagenes/Platillos/" . $imagenPlatillo);
    }

    header("Location: ListaPlatillosAdministradores.php?msg=Platillo eliminado");
} else {
    header("Location: ListaPlatillosAdministradores.php?err=Error al eliminar el platillo");
}

$stmt->close();
$conn->close();
exit();

?>
