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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario'])) {
    $idUsuario = intval($_POST['usuario']);
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $apellidos = mysqli_real_escape_string($conexion, $_POST['apellidos']);
    $email = mysqli_real_escape_string($conexion, $_POST['email']);
    $telefono = mysqli_real_escape_string($conexion, $_POST['telefono']);
    $rol = intval($_POST['rol']);
    $parcela = intval($_POST['parcela']);

    try {
        mysqli_begin_transaction($conexion);

        mysqli_query($conexion, "UPDATE usuarios SET nombre = '$nombre', apellidos = '$apellidos', email = '$email', telefono = '$telefono' WHERE id_usr = $idUsuario");

        mysqli_query($conexion, "DELETE FROM usuarios_roles WHERE id_usr = $idUsuario");
        mysqli_query($conexion, "INSERT INTO usuarios_roles (id_usr, id_rol) VALUES ($idUsuario, $rol)");

        mysqli_query($conexion, "DELETE FROM parcelas_usuarios WHERE id_usr = $idUsuario");
        mysqli_query($conexion, "INSERT INTO parcelas_usuarios (id_usr, id_parcela) VALUES ($idUsuario, $parcela)");

        mysqli_commit($conexion);
        $mensaje = "✅ Usuario modificado correctamente.";
        $tipo = "exito";
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        $mensaje = "❌ Error al modificar: " . $e->getMessage();
        $tipo = "error";
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
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($u = mysqli_fetch_assoc($usuarios)): ?>
        <tr>
          <form method="post" onsubmit="return validarFormulario(this);">
            <input type="hidden" name="usuario" value="<?= $u['id_usr'] ?>">
            <td><input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($u['nombre']) ?>" required pattern="^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]{2,50}$" title="Solo letras y espacios, entre 2 y 50 caracteres"></td>
            <td><input type="text" name="apellidos" class="form-control" value="<?= htmlspecialchars($u['apellidos']) ?>" required pattern="^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]{2,100}$" title="Solo letras y espacios, entre 2 y 100 caracteres"></td>
            <td><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required pattern="^[^\s@]+@[^\s@]+\.[^\s@]{2,}$" title="Introduce un correo válido (ejemplo@dominio.com)"></td>
            <td><input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($u['telefono']) ?>" required pattern="^\+?\d{9,12}$" title="Debe contener entre 9 y 12 dígitos, puede incluir prefijo (+34)"></td>
            <td>
              <select name="rol" class="form-select" required>
                <?php mysqli_data_seek($roles, 0); while ($r = mysqli_fetch_assoc($roles)): ?>
                  <option value="<?= $r['id_rol'] ?>" <?= $r['id_rol'] == $u['id_rol'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($r['nombre_rol']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </td>
            <td>
              <select name="parcela" class="form-select w-100" required>
                <?php mysqli_data_seek($parcelas, 0); while ($p = mysqli_fetch_assoc($parcelas)): ?>
                  <option value="<?= $p['id_parcela'] ?>" <?= $p['id_parcela'] == $u['id_parcela'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['ubicacion']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </td>
            <td><button class="btn btn-outline-success btn-sm">Actualizar</button></td>
          </form>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <div class="text-center mt-4">
    <a href="../../menu/usuarios.php" class="btn btn-danger rounded-pill px-4">
      <i class="bi bi-arrow-left-circle me-2"></i>Volver al menú de usuarios
    </a>
  </div>
</main>

<?php include '../../componentes/footer.php'; ?>

<script>
function filtrarUsuarios() {
  const filtro = document.getElementById('buscarUsuario').value.toLowerCase();
  document.querySelectorAll('.table tbody tr').forEach(fila => {
    fila.style.display = fila.textContent.toLowerCase().includes(filtro) ? '' : 'none';
  });
}

function validarFormulario(form) {
  const emailInput = form.querySelector('input[name="email"]');
  const emailVal = emailInput.value.trim();
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

  if (!emailRegex.test(emailVal)) {
    Swal.fire({
      icon: 'error',
      title: 'Correo inválido',
      text: 'Por favor, introduce un correo electrónico válido con formato usuario@dominio.com.',
      confirmButtonColor: '#d33'
    });
    return false;
  }

  const telInput = form.querySelector('input[name="telefono"]');
  const telVal = telInput.value.trim();
  const telRegex = /^\+?\d{9,12}$/;

  if (!telRegex.test(telVal)) {
    Swal.fire({
      icon: 'error',
      title: 'Teléfono inválido',
      text: 'Introduce un número válido con entre 9 y 12 dígitos. Puede incluir prefijo como +34.',
      confirmButtonColor: '#d33'
    });
    return false;
  }

  return true;
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