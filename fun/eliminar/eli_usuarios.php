<?php
include '../../lib/functiones.php';
session_start();

// Validar sesión
if (!isset($_SESSION['usuario'])) {
  header("Location: ../../index.php");
  exit();
}

// Validar rol administrador
$conexion = conectar();
$id_usr = $_SESSION['usuario']['id_usr'];
$rol_q = mysqli_query($conexion, "SELECT id_rol FROM usuarios_roles WHERE id_usr = $id_usr");
$is_admin = false;
while ($r = mysqli_fetch_assoc($rol_q)) {
  if ($r['id_rol'] == 1) $is_admin = true;
}
if (!$is_admin) {
  echo "<script>
    Swal.fire({ icon: 'error', title: '⛔ Acceso denegado', text: 'Solo administradores pueden eliminar usuarios.' })
      .then(() => location.href='../../menu/usuarios.php');
  </script>";
  exit;
}

// Eliminar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrar'])) {
  $ids = $_POST['borrar'];
  foreach ($ids as $id) {
    $id = intval($id);
    // Eliminar relaciones
    mysqli_query($conexion, "DELETE FROM usuarios_roles WHERE id_usr = $id");
    mysqli_query($conexion, "DELETE FROM parcelas_usuarios WHERE id_usr = $id");
    // Drones asignados
    mysqli_query($conexion, "UPDATE drones SET id_usr = NULL WHERE id_usr = $id");
    // Trabajos asignados
    mysqli_query($conexion, "DELETE FROM trabajos_tareas WHERE id_trabajo IN (SELECT id_trabajo FROM trabajos WHERE id_usr = $id)");
    mysqli_query($conexion, "DELETE FROM trabajos WHERE id_usr = $id");
    // Eliminar usuario
    mysqli_query($conexion, "DELETE FROM usuarios WHERE id_usr = $id");
  }
  $_SESSION['mensaje'] = "Usuarios eliminados correctamente.";
  header("Location: eli_usuarios.php");
  exit;
}

// Obtener usuarios no admin
$usuarios = mysqli_query($conexion, "
  SELECT u.id_usr, u.nombre, u.apellidos, u.email, u.telefono,
         (SELECT GROUP_CONCAT(r.nombre_rol SEPARATOR ', ') FROM usuarios_roles ur JOIN roles r ON ur.id_rol = r.id_rol WHERE ur.id_usr = u.id_usr AND ur.id_rol != 1) AS rol,
         (SELECT GROUP_CONCAT(p.ubicacion SEPARATOR ', ') FROM parcelas_usuarios pu JOIN parcelas p ON pu.id_parcela = p.id_parcela WHERE pu.id_usr = u.id_usr) AS parcelas
  FROM usuarios u
  WHERE u.id_usr NOT IN (SELECT id_usr FROM usuarios_roles WHERE id_rol = 1)
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>🗑️ Eliminar Usuarios - AgroSky</title>
  <link rel="stylesheet" href="../../css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">
<?php include '../../componentes/header.php'; ?>
<main class="container flex-grow-1 py-5">
  <h1 class="titulo-listado text-center mb-4">
    <i class="bi bi-person-x me-2 text-danger"></i>Eliminar Usuarios
  </h1>

  <form method="get" class="d-flex justify-content-center mb-4">
    <input type="text" id="buscarUsuario" class="form-control w-50 me-2" placeholder="🔍 Buscar por nombre, email o teléfono">
    <button class="btn btn-success" type="button" onclick="filtrarUsuarios()">Buscar</button>
  </form>

  <form method="post" onsubmit="return confirmarEliminacion(event)">
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-danger text-center">
          <tr>
            <th>Nombre</th>
            <th>Apellidos</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Rol</th>
            <th>Parcelas</th>
            <th>Eliminar</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($u = mysqli_fetch_assoc($usuarios)): ?>
          <tr>
            <td><?= htmlspecialchars($u['nombre']) ?></td>
            <td><?= htmlspecialchars($u['apellidos']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['telefono']) ?></td>
            <td><?= htmlspecialchars($u['rol'] ?? 'Sin rol') ?></td>
            <td><?= htmlspecialchars($u['parcelas'] ?? 'Ninguna') ?></td>
            <td class="text-center">
              <input type="checkbox" name="borrar[]" value="<?= $u['id_usr'] ?>">
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <div class="text-center mt-4">
      <div class="d-flex flex-column flex-sm-row justify-content-center align-items-stretch gap-3">
        <button type="submit" class="btn btn-danger w-100 w-sm-auto px-4">
          <i class="bi bi-trash me-2"></i>Eliminar seleccionados
        </button>
        <a href="../../menu/usuarios.php" class="btn btn-success w-100 w-sm-auto px-4">
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

function confirmarEliminacion(e) {
  e.preventDefault();
  const seleccionados = document.querySelectorAll('input[name="borrar[]"]:checked');
  if (seleccionados.length === 0) {
    Swal.fire({
      icon: 'warning',
      title: '⚠️ Aviso',
      text: 'No has seleccionado ningún usuario.',
      confirmButtonColor: '#d33'
    });
    return false;
  }
  Swal.fire({
    title: '¿Estás seguro?',
    text: 'Los usuarios seleccionados serán eliminados. Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      e.target.submit();
    }
  });
  return false;
}

<?php if (isset($_SESSION['mensaje'])): ?>
window.onload = function () {
  Swal.fire({
    title: '✅ Éxito',
    text: <?= json_encode($_SESSION['mensaje']) ?>,
    icon: 'success',
    confirmButtonColor: '#218838'
  });
};
<?php unset($_SESSION['mensaje']); endif; ?>
</script>
</body>
</html>
