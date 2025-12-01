<?php
ob_start();
session_start();
require_once "../includes/conexion.php";

$error = "";

// Cuando el usuario envía el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {

    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Buscar usuario ignorando may/min
    $sql = "SELECT idPersona, Usuario, Email, Contrasena, Estatus, idRol, Imagen,
                   CONCAT(Nombre, ' ', ApellidoPaterno) AS Nombre
            FROM Persona
            WHERE LOWER(Email) = LOWER(?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {

        $user = $resultado->fetch_assoc();

        if ($user["Estatus"] !== "Activo") {
            $error = "Tu cuenta está desactivada.";
        } else {

            $conBD = $user["Contrasena"];
            $isHashed = preg_match('/^\$2[aby]\$/', $conBD);
            $validPass = false;

            if ($isHashed) {
                $validPass = password_verify($password, $conBD);
            } else if ($password === $conBD) {
                $validPass = true;
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE Persona SET Contrasena = ? WHERE idPersona = ?");
                $upd->bind_param("si", $hash, $user["idPersona"]);
                $upd->execute();
            }

            if ($validPass) {
                $_SESSION["idPersona"] = $user["idPersona"];
                $_SESSION["Usuario"] = $user["Usuario"];
                $_SESSION["Nombre"] = $user["Nombre"];
                $_SESSION["Rol"] = $user["idRol"];
                $_SESSION["Imagen"] = $user["Imagen"];

                switch ($user["idRol"]) {
                    case 1: header("Location: InicioAdministradores.php"); exit();
                    case 2: header("Location: InicioEmpleado.php"); exit();
                    case 3: header("Location: InicioClientes.php"); exit();
                }
            } else {
                $error = "Contraseña incorrecta.";
            }
        }
    } else {
        $error = "El correo no está registrado.";
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login ChilMole</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="Login.css">
</head>
<body>

<main class="page">

  <section class="left">
    <div class="logo">
      <div class="ring"></div>
      <h2>Iniciar Sesión</h2>
    </div>

    <?php if (!empty($error)): ?>
      <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form class="login-card" action="" method="post" autocomplete="off">
      <label>Correo
        <input type="email" name="email" placeholder="abc@xyz.com" required>
      </label>

      <label>Contraseña
        <input type="password" name="password" placeholder="••••••••••" required>
      </label>

      <button class="btn gradient" type="submit" name="login">Entrar</button>

      <hr style="margin: 20px 0; border-color: #ddd;">

      <!-- Botones secundarios -->
      <button class="btn secondary" type="button" onclick="location.href='RegistroUsuarios.php'">Registrarse</button>
      <button class="btn secondary" type="button" onclick="location.href='RecuperarContrasena.php'">Olvidé mi Contraseña</button>
    </form>
  </section>

  <aside class="right">
    <div class="photo-circle"></div>



  </aside>
</main>

</body>
</html>
