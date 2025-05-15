<?php
session_start();
include '../../lib/functiones.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit();
}

$conexion = conectar();
$idAdmin = $_SESSION['usuario']['id_usr'];

$esAdmin = false;
$rolCheck = mysqli_query($conexion, "SELECT id_rol FROM usuarios_roles WHERE id_usr = $idAdmin");
while ($rol = mysqli_fetch_assoc($rolCheck)) {
    if ($rol['id_rol'] == 1) {
        $esAdmin = true;
        break;
    }
}
if (!$esAdmin) {
    echo "<h2 class='mensaje-error'>⛔ Acceso restringido</h2><p class='mensaje-error'>Solo administradores pueden acceder.</p>";
    exit();
}

$mensaje = "";
$tipo = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuarios'])) {
    foreach ($_POST['usuarios'] as $usuarioData) {
        $idUsuario = intval($usuarioData['id']);
        $nombre = mysqli_real_escape_string($conexion, $usuarioData['nombre']);
        $apellidos = mysqli_real_escape_string($conexion, $usuarioData['apellidos']);
        $email = mysqli_real_escape_string($conexion, $usuarioData['email']);
        $telefono = mysqli_real_escape_string($conexion, $usuarioData['telefono']);
        $rol = intval($usuarioData['rol']);
        $parcela = intval($usuarioData['parcela']);

        try {
            mysqli_begin_transaction($conexion);

            mysqli_query($conexion, "UPDATE usuarios SET nombre = '$nombre', apellidos = '$apellidos', email = '$email', telefono = '$telefono' WHERE id_usr = $idUsuario");
            mysqli_query($conexion, "DELETE FROM usuarios_roles WHERE id_usr = $idUsuario");
            mysqli_query($conexion, "INSERT INTO usuarios_roles (id_usr, id_rol) VALUES ($idUsuario, $rol)");
            mysqli_query($conexion, "DELETE FROM parcelas_usuarios WHERE id_usr = $idUsuario");
            mysqli_query($conexion, "INSERT INTO parcelas_usuarios (id_usr, id_parcela) VALUES ($idUsuario, $parcela)");

            mysqli_commit($conexion);
            $mensaje = "✅ Cambios guardados correctamente.";
            $tipo = "exito";
        } catch (Exception $e) {
            mysqli_rollback($conexion);
            $mensaje = "❌ Error al modificar: " . $e->getMessage();
            $tipo = "error";
        }
    }
}

$usuarios = mysqli_query($conexion, "
    SELECT u.id_usr, u.nombre, u.apellidos, u.email, u.telefono,
        (SELECT ur.id_rol FROM usuarios_roles ur WHERE ur.id_usr = u.id_usr LIMIT 1) AS id_rol,
        (SELECT pu.id_parcela FROM parcelas_usuarios pu WHERE pu.id_usr = u.id_usr LIMIT 1) AS id_parcela
    FROM usuarios u
    WHERE u.id_usr NOT IN (SELECT id_usr FROM usuarios_roles WHERE id_rol = 1)
");

$roles = mysqli_query($conexion, "SELECT * FROM roles WHERE id_rol != 1");
$parcelas = mysqli_query($conexion, "SELECT * FROM parcelas");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Modificar Usuarios</title>
  <link rel="stylesheet" href="../../css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">
<?php include '../../componentes/header.php'; ?>
<main class="container flex-grow-1 py-5">
  <h1 class="titulo-listado text-center mb-4">
    <i class="bi bi-person-gear me-2" style="color:#6f42c1;"></i>Modificar Usuarios
  </h1>

  <form method="get" class="d-flex justify-content-center mb-4">
    <input type="text" id="buscarUsuario" class="form-control w-50 me-2" placeholder="🔍 Buscar por nombre, correo o teléfono">
    <button class="btn btn-success" type="button" onclick="filtrarUsuarios()">Buscar</button>
  </form>

  <form method="post">
    <div class="table-responsive">
      <table class="table">
        <thead class="table-success text-center">
          <tr>
            <th>Nombre</th>
            <th>Apellidos</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Rol</th>
            <th>Parcela</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($u = mysqli_fetch_assoc($usuarios)): ?>
          <tr>
            <td><input type="text" name="usuarios[<?= $u['id_usr'] ?>][nombre]" class="form-control" value="<?= htmlspecialchars($u['nombre']) ?>" required></td>
            <td><input type="text" name="usuarios[<?= $u['id_usr'] ?>][apellidos]" class="form-control" value="<?= htmlspecialchars($u['apellidos']) ?>" required></td>
            <td><input type="email" name="usuarios[<?= $u['id_usr'] ?>][email]" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required></td>
            <td><input type="text" name="usuarios[<?= $u['id_usr'] ?>][telefono]" class="form-control" value="<?= htmlspecialchars($u['telefono']) ?>" required></td>
            <td>
              <select name="usuarios[<?= $u['id_usr'] ?>][rol]" class="form-select">
                <?php mysqli_data_seek($roles, 0); while ($r = mysqli_fetch_assoc($roles)): ?>
                  <option value="<?= $r['id_rol'] ?>" <?= $r['id_rol'] == $u['id_rol'] ? 'selected' : '' ?>><?= $r['nombre_rol'] ?></option>
                <?php endwhile; ?>
              </select>
            </td>
            <td>
              <select name="usuarios[<?= $u['id_usr'] ?>][parcela]" class="form-select">
                <option value="">Ninguna</option>
                <?php mysqli_data_seek($parcelas, 0); while ($p = mysqli_fetch_assoc($parcelas)): ?>
                  <option value="<?= $p['id_parcela'] ?>" <?= $p['id_parcela'] == $u['id_parcela'] ? 'selected' : '' ?>><?= $p['ubicacion'] ?></option>
                <?php endwhile; ?>
              </select>
            </td>
            <input type="hidden" name="usuarios[<?= $u['id_usr'] ?>][id]" value="<?= $u['id_usr'] ?>">
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

   <div class="text-center mt-4">
  <div class="d-flex flex-column flex-sm-row justify-content-center align-items-stretch gap-3">
    <button type="submit" class="btn btn-success w-100 w-sm-auto px-4">
      <i class="bi bi-floppy me-2"></i>Guardar cambios
    </button>
    <a href="../../menu/usuarios.php" class="btn btn-danger w-100 w-sm-auto px-4">
      <i class="bi bi-arrow-left-circle me-2"></i>Volver al menú de usuarios
    </a>
  </div>
</div>

  </form>
</main>

<?php include '../../componentes/footer.php'; ?>

<script>
function filtrarUsuarios() {
  const filtro = document.getElementById('buscarUsuario').value.toLowerCase();
  document.querySelectorAll('.table tbody tr').forEach(fila => {
    fila.style.display = fila.textContent.toLowerCase().includes(filtro) ? '' : 'none';
  });
}

<?php if (!empty($mensaje)): ?>
window.onload = function () {
  Swal.fire({
    title: <?= json_encode($tipo === 'exito' ? '✅ Éxito' : '❌ Error') ?>,
    text: <?= json_encode($mensaje) ?>,
    icon: <?= json_encode($tipo === 'exito' ? 'success' : 'error') ?>,
    confirmButtonColor: '#218838'
  });
};
<?php endif; ?>
</script>
</body>
</html>
