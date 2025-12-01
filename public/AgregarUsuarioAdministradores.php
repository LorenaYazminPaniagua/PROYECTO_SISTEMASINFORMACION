<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Usuario — ChilMole</title>
    <link rel="stylesheet" href="AgregarUsuarioAdministradores.css">
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
        <h2 class="page-title">Registro Usuario</h2>

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

        <form class="form-add" method="POST" enctype="multipart/form-data">

            <!-- NOMBRE -->
            <div class="input-wrap">
                <label class="label-text">Nombre</label>
                <input type="text" name="Nombre" placeholder="Ej. Juan" required>
            </div>

            <!-- APELLIDO PATERNO -->
            <div class="input-wrap">
                <label class="label-text">Apellido Paterno</label>
                <input type="text" name="ApellidoPaterno" placeholder="Ej. Pérez" required>
            </div>

            <!-- APELLIDO MATERNO -->
            <div class="input-wrap">
                <label class="label-text">Apellido Materno</label>
                <input type="text" name="ApellidoMaterno" placeholder="Ej. López">
            </div>

            <!-- TELÉFONO -->
            <div class="input-wrap">
                <label class="label-text">Teléfono</label>
                <input type="tel" name="Telefono" placeholder="Ej. 5551234567" pattern="[0-9]{10}">
            </div>

            <!-- EMAIL -->
            <div class="input-wrap">
                <label class="label-text">Email</label>
                <input type="email" name="Email" placeholder="Ej. usuario@correo.com">
            </div>

            <!-- EDAD -->
            <div class="input-wrap">
                <label class="label-text">Edad</label>
                <input type="number" name="Edad" placeholder="0" min="0" required>
            </div>

            <!-- SEXO -->
            <div class="input-wrap">
                <label class="label-text">Sexo</label>
                <select name="Sexo" required>
                    <option disabled selected>Seleccionar...</option>
                    <option value="Masculino">Masculino</option>
                    <option value="Femenino">Femenino</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>

            <!-- ESTATUS -->
            <div class="input-wrap">
                <label class="label-text">Estatus</label>
                <select name="Estatus" required>
                    <option disabled selected>Seleccionar...</option>
                    <option value="Activo">Activo</option>
                    <option value="Inactivo">Inactivo</option>
                </select>
            </div>

            <!-- USUARIO -->
            <div class="input-wrap">
                <label class="label-text">Usuario</label>
                <input type="text" name="Usuario" placeholder="Nombre de usuario" required>
            </div>

            <!-- CONTRASEÑA -->
            <div class="input-wrap">
                <label class="label-text">Contraseña</label>
                <input type="password" name="Contrasena" placeholder="Contraseña" required>
            </div>

            <!-- ROL -->
            <div class="input-wrap">
                <label class="label-text">Rol</label>
                <select name="idRol" required>
                    <option disabled selected>Seleccionar...</option>
                    <option value="1">Administrador</option>
                    <option value="2">Empleado</option>
                    <option value="3">Cliente</option>
                </select>
            </div>

            <!-- IMAGEN -->
            <div class="input-wrap">
                <label class="label-text">Imagen</label>
                <input type="file" name="Imagen" accept="image/*">
            </div>

            <!-- BOTONES -->
            <div class="btn-row">
                <button class="btn save" type="submit">Guardar Usuario</button>
                <button class="btn cancel" type="button" onclick="history.back()">Cancelar</button>
            </div>

        </form>


    
    </main>

</div>

</body>
</html>
