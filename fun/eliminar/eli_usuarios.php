<?php
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit();
}

function eliminarUsuario($id_usuario) {
    $conexion = conectar();
    mysqli_begin_transaction($conexion);

    try {
        mysqli_query($conexion, "DELETE FROM usuarios_roles WHERE id_usr = $id_usuario");
        mysqli_query($conexion, "DELETE FROM parcelas_usuarios WHERE id_usr = $id_usuario");

        $drones = [];
        $consultaDrones = mysqli_query($conexion, "SELECT id_dron FROM drones WHERE id_usr = $id_usuario");
        while ($row = mysqli_fetch_assoc($consultaDrones)) {
            $drones[] = $row['id_dron'];
        }

        if (!empty($drones)) {
            $idsDrones = implode(",", $drones);
            mysqli_query($conexion, "DELETE tt FROM trabajos_tareas tt INNER JOIN trabajos t ON tt.id_trabajo = t.id_trabajo WHERE t.id_dron IN ($idsDrones)");
            mysqli_query($conexion, "DELETE FROM trabajos WHERE id_dron IN ($idsDrones)");
        }

        mysqli_query($conexion, "UPDATE drones SET id_usr = NULL WHERE id_usr = $id_usuario");
        mysqli_query($conexion, "DELETE FROM usuarios WHERE id_usr = $id_usuario");

        mysqli_commit($conexion);
        return ['success' => true, 'mensaje' => '✅ Usuario eliminado correctamente. Drones desvinculados.'];
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        return ['success' => false, 'mensaje' => '❌ Error al eliminar usuario: ' . $e->getMessage()];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_usuario'])) {
    $resultado = eliminarUsuario(intval($_POST['id_usuario']));
    $_SESSION['mensaje'] = $resultado['mensaje'];
    $_SESSION['mensaje_tipo'] = $resultado['success'] ? 'exito' : 'error';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

$conexion = conectar();
$usuarios = mysqli_query($conexion, "
    SELECT u.id_usr, u.nombre, u.apellidos, u.email, u.telefono,
        (SELECT GROUP_CONCAT(r.nombre_rol SEPARATOR ', ') FROM usuarios_roles ur JOIN roles r ON ur.id_rol = r.id_rol WHERE ur.id_usr = u.id_usr AND ur.id_rol != 1) AS rol,
        (SELECT GROUP_CONCAT(p.ubicacion SEPARATOR ', ') FROM parcelas_usuarios pu JOIN parcelas p ON pu.id_parcela = p.id_parcela WHERE pu.id_usr = u.id_usr) AS parcelas
    FROM usuarios u
    WHERE u.id_usr NOT IN (SELECT id_usr FROM usuarios_roles WHERE id_rol = 1)
");
?>


<head>

  <title>👤 Eliminar Usuarios - AgroSky</title>
 
  <link rel="stylesheet" href="../../css/style.css">

</head>
<body class="d-flex flex-column min-vh-100">
<?php include '../../componentes/header.php'; ?>

<main class="container py-5 flex-grow-1">
  <h1 class="titulo-listado text-center mb-4">
  <i class="bi bi-person-x me-2" style="color:#d33;"></i>Eliminar Usuarios
</h1>



  <form class="busqueda-form d-flex flex-column flex-md-row gap-2 mb-4 justify-content-center" onsubmit="event.preventDefault();">
    <input type="text" class="form-control" placeholder="Buscar por nombre o correo" id="buscarUsuario">
    <button class="btn btn-success" type="submit">Buscar</button>
  </form>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Apellidos</th>
          <th>Email</th>
          <th>Teléfono</th>
          <th>Rol</th>
          <th>Parcelas</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($u = mysqli_fetch_assoc($usuarios)): ?>
        <tr>
          <td><?= htmlspecialchars($u['nombre']) ?></td>
          <td><?= htmlspecialchars($u['apellidos']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= htmlspecialchars($u['telefono']) ?></td>
          <td><?= htmlspecialchars($u['rol'] ?? 'Sin asignar') ?></td>
          <td><?= htmlspecialchars($u['parcelas'] ?? 'Ninguna') ?></td>
          <td>
            <form method="post" class="form-eliminar">
              <input type="hidden" name="id_usuario" value="<?= $u['id_usr'] ?>">
              <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmarEliminacion(this)">Eliminar</button>
            </form>
          </td>
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
let formEliminar = null;

function confirmarEliminacion(btn) {
  formEliminar = btn.closest('form');
  Swal.fire({
    title: '¿Estás seguro?',
    text: 'Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed && formEliminar) {
      formEliminar.submit();
    }
  });
}

document.getElementById('buscarUsuario').addEventListener('input', function () {
  const filtro = this.value.toLowerCase();
  document.querySelectorAll('.table tbody tr').forEach(fila => {
    fila.style.display = fila.textContent.toLowerCase().includes(filtro) ? '' : 'none';
  });
});

<?php if (isset($_SESSION['mensaje'])): ?>
window.onload = function () {
  Swal.fire({
    title: <?= json_encode($_SESSION['mensaje_tipo'] === 'exito' ? '✅ Éxito' : '❌ Error') ?>,
    text: <?= json_encode($_SESSION['mensaje']) ?>,
    icon: <?= json_encode($_SESSION['mensaje_tipo'] === 'exito' ? 'success' : 'error') ?>,
    confirmButtonColor: '#218838'
  });
};
<?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); endif; ?>
</script>

