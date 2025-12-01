<?php
session_start();

if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar usuario actual para triggers
$conn->query("SET @id_usuario_actual = " . intval($idPersona));


// ====================================
// VALIDAR ID
// ====================================
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ListaRecetasAdministradores.php?err=ID inválido");
    exit();
}

$idReceta = intval($_GET['id']);


// ====================================
// 1. Verificar que la receta exista
// ====================================
$sql = "SELECT Instrucciones FROM Receta WHERE idReceta = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idReceta);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ListaRecetasAdministradores.php?err=Receta no encontrada");
    exit();
}

$receta = $result->fetch_assoc();
$stmt->close();


// ====================================
// 2. Verificar si la receta tiene ingredientes en DetalleReceta
// ====================================
$sql = "SELECT COUNT(*) AS total FROM DetalleReceta WHERE idReceta = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idReceta);
$stmt->execute();
$detalle = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($detalle["total"] > 0) {
    header("Location: ListaRecetasAdministradores.php?err=No se puede eliminar: la receta tiene ingredientes registrados");
    exit();
}


// ====================================
// 3. Guardar auditoría ANTES de borrar
// ====================================
$sqlAud = "
INSERT INTO AuditoriaPersona
(Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona)
VALUES
('DELETE', 'Receta', ?, NULL, ?)
";

$stmt = $conn->prepare($sqlAud);
$datoAnterior = "Receta ID $idReceta — Instrucciones: " . $receta["Instrucciones"];
$stmt->bind_param("si", $datoAnterior, $idPersona);
$stmt->execute();
$stmt->close();


// ====================================
// 4. Eliminar receta
// ====================================
$sql = "DELETE FROM Receta WHERE idReceta = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idReceta);

if ($stmt->execute()) {
    header("Location: ListaRecetasAdministradores.php?msg=Receta eliminada");
} else {
    header("Location: ListaRecetasAdministradores.php?err=Error al eliminar la receta");
}

$stmt->close();
$conn->close();
exit();
?>
