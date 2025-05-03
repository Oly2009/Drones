<?php
include '../../lib/functiones.php';
session_start();
$conexion = conectar();

$id_usr = $_SESSION['usuario']['id_usr'] ?? null;
$roles = mysqli_query($conexion, "SELECT id_rol FROM usuarios_roles WHERE id_usr = $id_usr");
$id_rol = null;
while ($rol = mysqli_fetch_assoc($roles)) {
  if ($rol['id_rol'] == 1) {
    $id_rol = 1;
    break;
  } elseif ($rol['id_rol'] == 2) {
    $id_rol = 2;
  }
}

if ($id_rol != 1) {
  echo "<script>
    Swal.fire({ icon: 'error', title: '⛔ Acceso denegado', text: 'Solo administradores pueden eliminar drones' }).then(() => location.href='../../menu/drones.php');
  </script>";
  exit;
}

// Eliminar drone si está fuera de servicio
if (isset($_GET['eliminar'])) {
  $id_dron = intval($_GET['eliminar']);
  $consulta = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT estado FROM drones WHERE id_dron = $id_dron"));

  if ($consulta && $consulta['estado'] === 'fuera de servicio') {
    if (mysqli_query($conexion, "DELETE FROM drones WHERE id_dron = $id_dron")) {
      echo "<script>
        Swal.fire({ icon: 'success', title: '✅ Dron eliminado correctamente' }).then(() => location.href='eli_drones.php');
      </script>";
    } else {
      echo "<script>
        Swal.fire({ icon: 'error', title: '❌ Error al eliminar dron' });
      </script>";
    }
  } else {
    echo "<script>
      Swal.fire({ icon: 'info', title: 'ℹ️ Solo se pueden eliminar drones fuera de servicio' });
    </script>";
  }
}

$busqueda = $_GET['buscar'] ?? '';
$busqueda = mysqli_real_escape_string($conexion, $busqueda);
$filtro = "WHERE d.estado = 'fuera de servicio'";
if (!empty($busqueda)) {
  $filtro .= " AND (d.marca LIKE '%$busqueda%' OR d.modelo LIKE '%$busqueda%' OR d.numero_serie LIKE '%$busqueda%')";
}

$drones = mysqli_query($conexion, "
  SELECT d.*, p.ubicacion, t.nombre_tarea 
  FROM drones d 
  LEFT JOIN parcelas p ON d.id_parcela = p.id_parcela 
  LEFT JOIN tareas t ON d.id_tarea = t.id_tarea
  $filtro
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Eliminar Drones</title>
  <link rel="stylesheet" href="../../css/style.css">
</head>
<body class="d-flex flex-column min-vh-100">
  <div class="d-flex flex-column min-vh-100">
    <?php include '../../componentes/header.php'; ?>

    <main class="container flex-grow-1 d-flex flex-column">
      <h2 class="titulo-listado text-center mb-4">🗑️ Eliminar Drones</h2>

      <form method="get" class="d-flex justify-content-center mb-4">
        <input type="text" name="buscar" class="form-control w-50 me-2" placeholder="🔍 Buscar por marca, modelo o N° serie" value="<?= htmlspecialchars($busqueda) ?>">
        <button class="btn btn-success" type="submit">Buscar</button>
      </form>

      <div class="table-responsive">
        <table class="table table-bordered">
          <thead class="table-danger text-center">
            <tr>
              <th>Marca</th><th>Modelo</th><th>N.º Serie</th><th>Tipo</th><th>Parcela</th><th>Tarea</th><th>Estado</th><th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($drones) == 0): ?>
              <tr>
                <td colspan="8" class="text-center text-muted">No hay drones fuera de servicio que coincidan con la búsqueda</td>
              </tr>
            <?php endif; ?>

            <?php while ($dron = mysqli_fetch_assoc($drones)): ?>
              <tr>
                <td><?= htmlspecialchars($dron['marca']) ?></td>
                <td><?= htmlspecialchars($dron['modelo']) ?></td>
                <td><?= htmlspecialchars($dron['numero_serie']) ?></td>
                <td><?= $dron['tipo'] ?></td>
                <td><?= $dron['ubicacion'] ?></td>
                <td><?= $dron['nombre_tarea'] ?></td>
                <td><?= $dron['estado'] ?></td>
                <td class="text-center">
                  <a href="?eliminar=<?= $dron['id_dron'] ?>" class="btn btn-danger btn-sm">Eliminar</a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <div class="text-center mt-4">
        <a href="../../menu/drones.php" class="btn btn-danger rounded-pill px-4">
          <i class="bi bi-arrow-left-circle me-2"></i>Volver al menú de drones
        </a>
      </div>
    </main>

    <?php include '../../componentes/footer.php'; ?>
  </div>
</body>
</html>