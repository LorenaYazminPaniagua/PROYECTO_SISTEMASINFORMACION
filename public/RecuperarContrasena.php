<?php
session_start();
require_once "../includes/conexion.php";

$error = "";
$success = "";

// Cuando envían el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $nuevaContrasena = password_hash($_POST["contrasena"], PASSWORD_DEFAULT);

    // Verificar si el usuario existe
    $stmt = $conn->prepare("SELECT idPersona FROM Persona WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        $idPersona = $user["idPersona"];

        // Llamar al procedimiento para actualizar contraseña
        $stmt2 = $conn->prepare("CALL EditarContrasenaPersona(?, ?)");
        $stmt2->bind_param("is", $idPersona, $nuevaContrasena);
        if($stmt2->execute()){
            $success = "Contraseña actualizada correctamente, ahora puedes iniciar sesión.";
        } else {
            $error = "Error al actualizar contraseña: ".$conn->error;
        }

    } else {
        $error = "El correo no está registrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar Contraseña — ChilMole</title>
  <link rel="stylesheet" href="RecuperarContrasena.css">
</head>
<body>
<main class="page">

<section class="left">
  <div class="logo"><div class="ring"></div><h2>Recuperar Contraseña</h2></div>

  <?php if($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
  <?php if($success): ?><div class="success"><?= $success ?></div><?php endif; ?>

  <form class="login-card" action="" method="post" autocomplete="off">
    <label>Email <input type="email" name="email" required></label>
    <label>Nueva Contraseña <input type="password" name="contrasena" required></label>
    <button class="btn gradient" type="submit">Actualizar Contraseña</button>
  </form>

  <p class="signup">¿Recuerdas tu contraseña? <a href="Login.php">Inicia sesión</a></p>
</section>

<aside class="right">
  <div class="photo-circle"></div>


    
</aside>
</main>
</body>
</html>
