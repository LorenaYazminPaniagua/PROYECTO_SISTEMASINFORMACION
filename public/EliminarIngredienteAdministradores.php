<?php
session_start();

if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

require_once "../includes/conexion.php";

// Establecer variable usada por triggers (si los tuvieras)
$conn->query("SET @id_usuario_actual = " . intval($idPersona));

// =========================
// VALIDAR ID RECIBIDO
// =========================
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ListaIngredienteAdministradores.php?err=ID inválido");
    exit();
}

$idIngrediente = intval($_GET['id']);


// =========================
// VERIFICAR SI EXISTE
// =========================
$sql = "SELECT Nombre FROM Ingrediente WHERE idIngrediente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idIngrediente);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: ListaIngredienteAdministradores.php?err=Ingrediente no encontrado");
    exit();
}

$row = $result->fetch_assoc();
$nombreIngrediente = $row['Nombre'];

$stmt->close();


// =========================
// RESTRICCIÓN: ¿Está usado en una receta?
// =========================
$sql = "SELECT COUNT(*) AS total FROM DetalleReceta WHERE idIngrediente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idIngrediente);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($res['total'] > 0) {
    header("Location: ListaIngredienteAdministradores.php?err=No se puede borrar, está usado en recetas");
    exit();
}


// =========================
// GUARDAR AUDITORÍA (ANTES DE BORRAR)
// =========================
$sqlAud = "
INSERT INTO AuditoriaPersona 
(Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona)
VALUES 
('DELETE', 'Ingrediente', ?, NULL, ?)
";
$stmt = $conn->prepare($sqlAud);
$datoAnterior = "Ingrediente: $nombreIngrediente (ID: $idIngrediente)";
$stmt->bind_param("si", $datoAnterior, $idPersona);
$stmt->execute();
$stmt->close();


// =========================
// ELIMINAR INGREDIENTE
// =========================
$sql = "DELETE FROM Ingrediente WHERE idIngrediente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idIngrediente);

if ($stmt->execute()) {
    header("Location: ListaIngredienteAdministradores.php?msg=Ingrediente eliminado");
} else {
    header("Location: ListaIngredienteAdministradores.php?err=Error al eliminar");
}

$stmt->close();
$conn->close();
?>
