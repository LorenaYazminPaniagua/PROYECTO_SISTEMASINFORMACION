<?php
session_start();

// Validar sesión y rol
if (!isset($_SESSION['idPersona']) || ($_SESSION['Rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

// Conexión
require_once "../includes/conexion.php";

// ID del usuario logueado (quien hace la acción)
$idActual = intval($_SESSION['idPersona']);

// Validar parámetro GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ListaUsuariosAdministradores.php?msg=error_parametro");
    exit();
}

$idAEliminar = intval($_GET['id']);

// Evitar que el administrador se borre a sí mismo
if ($idAEliminar === $idActual) {
    header("Location: ListaUsuariosAdministradores.php?msg=no_autoborrar");
    exit();
}

// Preparar eliminación
$sql = "DELETE FROM Persona WHERE idPersona = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    header("Location: ListaUsuariosAdministradores.php?msg=error_stmt");
    exit();
}

$stmt->bind_param("i", $idAEliminar);

// Ejecutar la eliminación
if ($stmt->execute()) {
    header("Location: ListaUsuariosAdministradores.php?msg=eliminado");
} else {
    header("Location: ListaUsuariosAdministradores.php?msg=error_eliminar");
}

$stmt->close();
$conn->close();
exit();
?>
