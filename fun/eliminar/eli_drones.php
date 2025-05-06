<?php
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit();
}

$conexion = conectar();
$id_usr = $_SESSION['usuario']['id_usr'] ?? null;
$id_rol = 0;

// Validar si es admin
$roles = mysqli_query($conexion, "SELECT id_rol FROM usuarios_roles WHERE id_usr = $id_usr");
while ($rol = mysqli_fetch_assoc($roles)) {
    if ($rol['id_rol'] == 1) {
        $id_rol = 1;
        break;
    }
}

if ($id_rol != 1) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: '⛔ Acceso denegado',
                text: 'Solo los administradores pueden eliminar drones.',
                confirmButtonText: 'Volver',
                confirmButtonColor: '#d33'
            }).then(() => window.location.href = '../../menu/drones.php');
        });
    </script>";
    exit;
}

// Eliminar dron si está fuera de servicio
if (isset($_GET['eliminar'])) {
    $id_dron = intval($_GET['eliminar']);
    $estado = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT estado FROM drones WHERE id_dron = $id_dron"));

    if ($estado && $estado['estado'] == 'fuera de servicio') {
        mysqli_query($conexion, "DELETE FROM drones WHERE id_dron = $id_dron");
        $_SESSION['mensaje'] = "✅ Dron eliminado correctamente.";
        header("Location: eli_drones.php");
        exit;
    } else {
        $_SESSION['mensaje'] = "❌ Solo se pueden eliminar drones fuera de servicio.";
        header("Location: eli_drones.php");
        exit;
    }
}

// Filtro de búsqueda
$busqueda = $_GET['buscar'] ?? '';
$busquedaSQL = mysqli_real_escape_string($conexion, $busqueda);
$filtro = "WHERE d.estado = 'fuera de servicio'";
if (!empty($busquedaSQL)) {
    $filtro .= " AND (d.marca LIKE '%$busquedaSQL%' OR d.modelo LIKE '%$busquedaSQL%' OR d.numero_serie LIKE '%$busquedaSQL%')";
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
  <title>🗑️ Eliminar Drones</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">
<?php include '../../componentes/header.php'; ?>

<main class="container py-5 flex-grow-1">
  <h1 class="titulo-listado text-center mb-4">
    <i class="bi bi-airplane-engines-fill me-2 text-danger"></i>Eliminar Drones
  </h1>

  <form method="get" class="d-flex justify-content-center mb-4">
    <input type="text" name="buscar" class="form-control w-50 me-2" placeholder="🔍 Buscar por marca, modelo o N° serie" value="<?= htmlspecialchars($busqueda) ?>">
    <button class="btn btn-success" type="submit">Buscar</button>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-danger text-center">
        <tr>
          <th>Marca</th>
          <th>Modelo</th>
          <th>N.º Serie</th>
          <th>Tipo</th>
          <th>Parcela</th>
          <th>Tarea</th>
          <th>Estado</th>
          <th>Eliminar</th>
        </tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($drones) == 0): ?>
          <tr><td colspan="8" class="text-center text-muted">No hay drones fuera de servicio</td></tr>
        <?php endif; ?>

        <?php while ($d = mysqli_fetch_assoc($drones)): ?>
          <tr>
            <td><?= htmlspecialchars($d['marca']) ?></td>
            <td><?= htmlspecialchars($d['modelo']) ?></td>
            <td><?= htmlspecialchars($d['numero_serie']) ?></td>
            <td><?= $d['tipo'] ?></td>
            <td><?= $d['ubicacion'] ?? '—' ?></td>
            <td><?= $d['nombre_tarea'] ?? '—' ?></td>
            <td class="text-center"><?= $d['estado'] ?></td>
            <td class="text-center">
              <a href="javascript:confirmarEliminacion(<?= $d['id_dron'] ?>)" class="btn btn-danger btn-sm">
                <i class="bi bi-trash"></i> Eliminar
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <div class="text-center mt-4">
    <a href="../../menu/drones.php" class="btn btn-success rounded-pill px-4">
      <i class="bi bi-arrow-left-circle me-2"></i>Volver al menú de drones
    </a>
  </div>
</main>

<?php include '../../componentes/footer.php'; ?>

<script>
function confirmarEliminacion(id) {
  Swal.fire({
    title: '¿Eliminar dron?',
    text: 'Esta acción es irreversible. ¿Deseas continuar?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = 'eli_drones.php?eliminar=' + id;
    }
  });
}

<?php if (isset($_SESSION['mensaje'])): ?>
window.onload = function () {
  Swal.fire({
    title: 'Aviso',
    text: <?= json_encode($_SESSION['mensaje']) ?>,
    icon: <?= strpos($_SESSION['mensaje'], '✅') !== false ? "'success'" : "'info'" ?>,
    confirmButtonColor: '#218838'
  });
};
<?php unset($_SESSION['mensaje']); endif; ?>
</script>
</body>
</html>
