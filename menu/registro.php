<?php 
session_start();
include '../lib/functiones.php';
include '../componentes/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registro de Usuarios - AgroSky</title>
  <link rel="stylesheet" href="../css/style.css">
 
</head>
<body class="registro-body">

<?php
$registroExitoso = false;
$mensaje = '';
$tipoMensaje = '';
$conexion = conectar();

if (isset($_POST['enviarReg'])) {
    $nombre = trim($_POST['usu']);
    $apellidos = trim($_POST['apellidos']);
    $correo = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['passwordMatchInput']);

    if (empty($nombre) || empty($apellidos) || empty($correo) || empty($telefono) || empty($password) || empty($confirm)) {
        $mensaje = "Todos los campos son obligatorios.";
        $tipoMensaje = 'error';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El correo electrónico no tiene un formato válido.";
        $tipoMensaje = 'warning';
    } elseif (!preg_match('/^[0-9]{9}$/', $telefono)) {
        $mensaje = "El número de teléfono debe tener exactamente 9 dígitos.";
        $tipoMensaje = 'warning';
    } elseif ($password !== $confirm) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipoMensaje = 'error';
    } else {
        $check = mysqli_query($conexion, "SELECT * FROM usuarios WHERE email = '$correo'");
        if (mysqli_num_rows($check) > 0) {
            $mensaje = "Ya existe un usuario con ese correo.";
            $tipoMensaje = 'warning';
        } else {
            $passwordHashed = base64_encode(hash('sha256', $password, true));
            $insert = "INSERT INTO usuarios (nombre, apellidos, contrasena, telefono, email) VALUES ('$nombre', '$apellidos', '$passwordHashed', '$telefono', '$correo')";
            if (mysqli_query($conexion, $insert)) {
                $id_usr = mysqli_insert_id($conexion);
                $resRol = mysqli_query($conexion, "SELECT id_rol FROM roles WHERE nombre_rol = 'piloto'");
                if ($rowRol = mysqli_fetch_assoc($resRol)) {
                    $id_rol = $rowRol['id_rol'];
                    mysqli_query($conexion, "INSERT INTO usuarios_roles (id_usr, id_rol) VALUES ($id_usr, $id_rol)");
                }
                $mensaje = "¡Usuario registrado correctamente!";
                $tipoMensaje = 'success';
            } else {
                $mensaje = "Error al registrar el usuario.";
                $tipoMensaje = 'error';
            }
        }
    }
}
?>

<main class="registro-main">
  <div class="formulario-registro">
    <h2 class="text-center mb-4">🛫 Registro de Usuarios <br><span class="text-success">- AgroSky -</span></h2>
    <form action="registro.php" method="post" autocomplete="off">
      <div class="mb-3">
        <label class="form-label">👤 Nombre de usuario</label>
        <input type="text" name="usu" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">👥 Apellidos</label>
        <input type="text" name="apellidos" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">📧 Correo electrónico</label>
        <input type="email" name="correo" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">📱 Número de teléfono</label>
        <input type="tel" name="telefono" class="form-control" required pattern="[0-9]{9}" maxlength="9">
      </div>
      <div class="mb-3">
        <label class="form-label">🔒 Contraseña</label>
        <input type="password" name="password" class="form-control" required autocomplete="new-password">
      </div>
      <div class="mb-4">
        <label class="form-label">🔁 Repite la contraseña</label>
        <input type="password" name="passwordMatchInput" class="form-control" required autocomplete="new-password">
      </div>
      <div class="d-flex justify-content-center gap-3">
        <input type="submit" name="enviarReg" value="Registrar" class="btn btn-success px-4">
        <a href="usuarios.php" class="btn btn-danger px-4">Volver</a>
      </div>
    </form>
  </div>
</main>

<?php if (!empty($mensaje)): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
      icon: '<?= $tipoMensaje ?>',
      title: '<?= $tipoMensaje === "success" ? "¡Éxito!" : "Atención" ?>',
      text: '<?= $mensaje ?>',
      confirmButtonColor: '#218838'
    });
  });
</script>
<?php endif; ?>

<?php include '../componentes/footer.php'; ?>
</body>
</html>
