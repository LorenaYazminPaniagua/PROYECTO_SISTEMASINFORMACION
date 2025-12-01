<?php
session_start();
require_once "../includes/conexion.php";

$error = "";
$success = "";

// Cuando envían el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"]);
    $apellidoP = trim($_POST["apellidoP"]);
    $apellidoM = trim($_POST["apellidoM"]);
    $telefono = trim($_POST["telefono"]);
    $email = trim($_POST["email"]);
    $edad = intval($_POST["edad"]);
    $sexo = $_POST["sexo"];
    $usuario = trim($_POST["usuario"]);
    $contrasena = password_hash($_POST["contrasena"], PASSWORD_DEFAULT); // hash seguro
    $imagen = "default.png"; // ruta por defecto
    $idRol = 3; // cliente por defecto

    // Preparar llamada al procedimiento
    $stmt = $conn->prepare("CALL RegistrarPersona(?,?,?,?,?,?,?,?,?,?,?)");
    if(!$stmt){
        $error = "Error al preparar la consulta: " . $conn->error;
    } else {

        $stmt->bind_param(
            "sssssissssi",
            $nombre,
            $apellidoP,
            $apellidoM,
            $telefono,
            $email,
            $edad,
            $sexo,
            $usuario,
            $contrasena,
            $imagen,
            $idRol
        );

        if ($stmt->execute()) {
            $success = "Registro exitoso, ya puedes iniciar sesión.";
        } else {
            $error = "Error al registrar usuario: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro — ChilMole</title>
  <link rel="stylesheet" href="RegistroUsuarios.css">
</head>
<body>
<main class="page">

  <!-- COLUMNA IZQUIERDA: FORMULARIO -->
  <section class="left">
    <div class="logo">
      <div class="ring"></div>
      <h2>Registro de Usuario</h2>
    </div>

    <!-- Mensajes de error o éxito -->
    <?php if($error): ?>
      <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    <?php if($success): ?>
      <div class="alert success"><?= $success ?></div>
    <?php endif; ?>

    <form class="login-card" action="" method="post" autocomplete="off">
      <label>Nombre
        <input type="text" name="nombre" required>
      </label>
      <label>Apellido Paterno
        <input type="text" name="apellidoP" required>
      </label>
      <label>Apellido Materno
        <input type="text" name="apellidoM">
      </label>
      <label>Teléfono
        <input type="text" name="telefono">
      </label>
      <label>Email
        <input type="email" name="email" required>
      </label>
      <label>Edad
        <input type="number" name="edad" required>
      </label>
      <label>Sexo
        <select name="sexo" required>
          <option value="Masculino">Masculino</option>
          <option value="Femenino">Femenino</option>
          <option value="Otro">Otro</option>
        </select>
      </label>
      <label>Usuario
        <input type="text" name="usuario" required>
      </label>
      <label>Contraseña
        <input type="password" name="contrasena" required>
      </label>

      <button class="btn gradient" type="submit">Registrar</button>
    </form>

    <!-- Botón Iniciar Sesión -->
    <button onclick="window.location='Login.php'" class="btn secondary">Iniciar Sesión</button>
  </section>

  <!-- COLUMNA DERECHA: IMAGEN -->
  <aside class="right">
    <div class="photo-circle"></div>

  </aside>

</main>
</body>
</html>
