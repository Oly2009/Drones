<?php
include '../lib/functiones.php';
session_start();
$conexion = conectar();

if (!isset($_SESSION['usuario'])) {
    echo '⛔ Acceso denegado';
    session_destroy();
    header('Location: ../index.php');
    exit();
}

$idUsuario = $_SESSION['usuario']['id_usr'];

$rolQuery = "SELECT r.nombre_rol FROM usuarios_roles ur JOIN roles r ON ur.id_rol = r.id_rol WHERE ur.id_usr = '$idUsuario'";
$rolResult = mysqli_query($conexion, $rolQuery);
$rol = mysqli_fetch_assoc($rolResult)['nombre_rol'];

$consultaUsuario = "SELECT * FROM usuarios WHERE id_usr = '$idUsuario'";
$resultadoUsuario = mysqli_query($conexion, $consultaUsuario);
$datosUsuario = mysqli_fetch_assoc($resultadoUsuario);

$consultaParcela = "SELECT p.ubicacion FROM parcelas p INNER JOIN parcelas_usuarios pu ON p.id_parcela = pu.id_parcela WHERE pu.id_usr = '$idUsuario'";
$resultadoParcela = mysqli_query($conexion, $consultaParcela);
$datosParcela = mysqli_fetch_assoc($resultadoParcela);

$mensajeJS = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
    $nuevaContrasena = $_POST['nuevaContrasena'];
    $confirmarContrasena = $_POST['confirmarContrasena'];

    if (!empty($nuevaContrasena)) {
        if ($nuevaContrasena !== $confirmarContrasena) {
            $mensajeJS = "error|Las contraseñas no coinciden.";
        } else {
            $hashNueva = base64_encode(hash('sha256', $nuevaContrasena, true));
            $consulta = "UPDATE usuarios SET contrasena='$hashNueva' WHERE id_usr='$idUsuario'";

            if (mysqli_query($conexion, $consulta)) {
                $mensajeJS = "success|Contraseña actualizada correctamente.";
            } else {
                $mensajeJS = "error|Error al actualizar la contraseña.";
            }
        }
    } else {
        $mensajeJS = "error|Debes introducir una nueva contraseña.";
    }
}
?>

<?php include '../componentes/header.php'; ?>
<link rel="stylesheet" href="../../css/style.css">



<main class="cuenta py-5">
  <h1 class="titulo-listado text-center mb-4">
    <i class="bi bi-person-gear me-2" style="color:#9ccc65;"></i>Configuración de cuenta
  </h1>

  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="formulario-cuenta" autocomplete="off">
    <div class="row row-cols-1 row-cols-md-2 g-4">
      <!-- Columna izquierda -->
      <div class="col">
        <label class="form-label">Nombre</label>
        <div class="input-group mb-3">
          <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
          <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($datosUsuario['nombre']); ?>">
        </div>

        <label class="form-label">Apellidos</label>
        <div class="input-group mb-3">
          <span class="input-group-text"><i class="bi bi-person-badge-fill"></i></span>
          <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($datosUsuario['apellidos']); ?>">
        </div>

        <label class="form-label">Correo electrónico</label>
        <div class="input-group mb-3">
          <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
          <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($datosUsuario['email']); ?>">
        </div>

        <label class="form-label">Teléfono</label>
        <div class="input-group mb-3">
          <span class="input-group-text"><i class="bi bi-telephone-fill"></i></span>
          <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($datosUsuario['telefono']); ?>">
        </div>
      </div>

      <!-- Columna derecha -->
      <div class="col">
        <label class="form-label">Parcela asignada</label>
        <div class="input-group mb-3">
          <span class="input-group-text"><i class="bi bi-geo-alt-fill"></i></span>
          <input type="text" class="form-control" readonly value="<?php echo $datosParcela ? htmlspecialchars($datosParcela['ubicacion']) : 'Sin parcela asignada'; ?>">
        </div>

        <label for="nuevaContrasena" class="form-label">Nueva contraseña</label>
        <div class="input-group mb-3">
          <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
          <input type="password" id="nuevaContrasena" name="nuevaContrasena" class="form-control" placeholder="••••••••" required autocomplete="new-password">
          <span class="input-group-text password-toggle" onclick="togglePassword('nuevaContrasena', this)"><i class="bi bi-eye-slash-fill"></i></span>
        </div>

        <label for="confirmarContrasena" class="form-label">Confirmar nueva contraseña</label>
        <div class="input-group mb-3">
          <span class="input-group-text"><i class="bi bi-shield-lock-fill"></i></span>
          <input type="password" id="confirmarContrasena" name="confirmarContrasena" class="form-control" placeholder="••••••••" required autocomplete="new-password">
          <span class="input-group-text password-toggle" onclick="togglePassword('confirmarContrasena', this)"><i class="bi bi-eye-slash-fill"></i></span>
        </div>
      </div>
    </div>

    <div class="text-center mt-4 d-flex justify-content-center gap-3">
        <a href="menu.php" class="btn btn-danger px-4">Volver</a>
      <button type="submit" name="actualizar" value="1" class="btn btn-success px-4">Actualizar contraseña</button>
    </div>
  </form>
</main>

<?php include '../componentes/footer.php'; ?>

<?php if (!empty($mensajeJS)): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const mensaje = <?php echo json_encode($mensajeJS); ?>.split('|');
    Swal.fire({
      icon: mensaje[0],
      title: mensaje[0] === 'success' ? '¡Éxito!' : 'Error',
      text: mensaje[1],
      confirmButtonColor: '#218838',
      showClass: {
        popup: 'animate__animated animate__fadeInDown'
      },
      hideClass: {
        popup: 'animate__animated animate__fadeOutUp'
      }
    });
  });

  function togglePassword(id, el) {
    const input = document.getElementById(id);
    const icon = el.querySelector('i');
    if (input.type === "password") {
      input.type = "text";
      icon.classList.remove("bi-eye-slash-fill");
      icon.classList.add("bi-eye-fill");
    } else {
      input.type = "password";
      icon.classList.remove("bi-eye-fill");
      icon.classList.add("bi-eye-slash-fill");
    }
  }
</script>
<?php endif; ?>
</body>
</html>
