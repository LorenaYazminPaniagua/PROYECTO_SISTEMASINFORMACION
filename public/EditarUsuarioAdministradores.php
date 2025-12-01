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

if (!isset($_GET['id'])) {
    die("ID de usuario no especificado.");
}

$idPersona = intval($_GET['id']);
$usuario = null;

// OBTENER DATOS DEL USUARIO
$stmt = $conn->prepare("SELECT * FROM Persona WHERE idPersona = ?");
$stmt->bind_param("i", $idPersona);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $usuario = $result->fetch_assoc();
} else {
    die("Usuario no encontrado.");
}

// PROCESAR FORMULARIO
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $Nombre = $_POST['Nombre'];
    $ApellidoPaterno = $_POST['ApellidoPaterno'];
    $ApellidoMaterno = $_POST['ApellidoMaterno'];
    $Telefono = $_POST['Telefono'];
    $Email = $_POST['Email'];
    $Edad = intval($_POST['Edad']);
    $Sexo = $_POST['Sexo'];
    $Estatus = $_POST['Estatus'];
    $UsuarioTxt = $_POST['Usuario'];
    $idRol = intval($_POST['idRol']);

    // Manejo de imagen
    $ImagenNombre = $usuario['Imagen']; // mantener la anterior por defecto
    if (isset($_FILES['Imagen']) && $_FILES['Imagen']['error'] === 0) {
        $ext = pathinfo($_FILES['Imagen']['name'], PATHINFO_EXTENSION);
        $ImagenNombre = "user_{$idPersona}." . $ext;
        $destino = "../uploads/" . $ImagenNombre;
        move_uploaded_file($_FILES['Imagen']['tmp_name'], $destino);
    }

    // Llamar procedimiento almacenado
    $stmtEdit = $conn->prepare("CALL EditarPersona(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtEdit->bind_param(
        "isssssissssi",
        $idPersona,
        $Nombre,
        $ApellidoPaterno,
        $ApellidoMaterno,
        $Telefono,
        $Email,
        $Edad,
        $Sexo,
        $Estatus,
        $UsuarioTxt,
        $ImagenNombre,
        $idRol
    );

    if ($stmtEdit->execute()) {
        echo "<script>alert('Usuario actualizado correctamente'); window.location='ListaUsuariosAdministradores.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error al actualizar usuario: " . $stmtEdit->error . "');</script>";
    }

    $stmtEdit->close();
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario — ChilMole</title>
    <link rel="stylesheet" href="EditarUsuarioAdministradores.css">
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
                <li class="nav-item active"><a href="ListaUsuariosAdministradores.php">Usuarios</a></li>
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
            <h2 class="page-title">Editar Usuario</h2>
            <div class="top-actions">
                <div class="profile">
                    <div>
                        <div class="profile-name">Administrador</div>
                        <div class="profile-role">Superadmin</div>
                    </div>
                    <div class="avatar">👩‍💻</div>
                </div>
            </div>
        </header>

        <!-- FORMULARIO -->
        <form class="form-add" method="POST" enctype="multipart/form-data">

            <input type="hidden" name="idPersona" value="<?php echo $usuario['idPersona']; ?>">

            <div class="input-wrap">
                <label class="label-text">Nombre</label>
                <input type="text" name="Nombre" value="<?php echo $usuario['Nombre']; ?>" required>
            </div>

            <div class="input-wrap">
                <label class="label-text">Apellido Paterno</label>
                <input type="text" name="ApellidoPaterno" value="<?php echo $usuario['ApellidoPaterno']; ?>" required>
            </div>

            <div class="input-wrap">
                <label class="label-text">Apellido Materno</label>
                <input type="text" name="ApellidoMaterno" value="<?php echo $usuario['ApellidoMaterno']; ?>">
            </div>

            <div class="input-wrap">
                <label class="label-text">Teléfono</label>
                <input type="tel" name="Telefono" value="<?php echo $usuario['Telefono']; ?>" pattern="[0-9]{10}">
            </div>

            <div class="input-wrap">
                <label class="label-text">Email</label>
                <input type="email" name="Email" value="<?php echo $usuario['Email']; ?>">
            </div>

            <div class="input-wrap">
                <label class="label-text">Edad</label>
                <input type="number" name="Edad" value="<?php echo $usuario['Edad']; ?>" min="0" required>
            </div>

            <div class="input-wrap">
                <label class="label-text">Sexo</label>
                <select name="Sexo" required>
                    <option disabled>Seleccionar...</option>
                    <option value="Masculino" <?php if($usuario['Sexo']=='Masculino') echo 'selected'; ?>>Masculino</option>
                    <option value="Femenino" <?php if($usuario['Sexo']=='Femenino') echo 'selected'; ?>>Femenino</option>
                    <option value="Otro" <?php if($usuario['Sexo']=='Otro') echo 'selected'; ?>>Otro</option>
                </select>
            </div>

            <div class="input-wrap">
                <label class="label-text">Estatus</label>
                <select name="Estatus" required>
                    <option disabled>Seleccionar...</option>
                    <option value="Activo" <?php if($usuario['Estatus']=='Activo') echo 'selected'; ?>>Activo</option>
                    <option value="Inactivo" <?php if($usuario['Estatus']=='Inactivo') echo 'selected'; ?>>Inactivo</option>
                </select>
            </div>

            <div class="input-wrap">
                <label class="label-text">Usuario</label>
                <input type="text" name="Usuario" value="<?php echo $usuario['Usuario']; ?>" required>
            </div>

            <div class="input-wrap">
                <label class="label-text">Rol</label>
                <select name="idRol" required>
                    <option disabled>Seleccionar...</option>
                    <option value="1" <?php if($usuario['idRol']==1) echo 'selected'; ?>>Administrador</option>
                    <option value="2" <?php if($usuario['idRol']==2) echo 'selected'; ?>>Empleado</option>
                    <option value="3" <?php if($usuario['idRol']==3) echo 'selected'; ?>>Cliente</option>
                </select>
            </div>

            <div class="input-wrap">
                <label class="label-text">Imagen</label>
                <input type="file" name="Imagen" accept="image/*">
                <?php if(!empty($usuario['Imagen'])): ?>
                    <p>Imagen actual: <?php echo $usuario['Imagen']; ?></p>
                <?php endif; ?>
            </div>

            <div class="btn-row">
                <button class="btn save" type="submit">Actualizar Usuario</button>
                <button class="btn cancel" type="button" onclick="history.back()">Cancelar</button>
            </div>

        </form>



    </main>
</div>
</body>
</html>
