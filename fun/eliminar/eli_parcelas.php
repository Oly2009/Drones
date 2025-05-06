<?php
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit();
}

$conexion = conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrar'])) {
    $ids = $_POST['borrar'];
    foreach ($ids as $id) {
        $id = intval($id);
        mysqli_query($conexion, "DELETE FROM trabajos_tareas WHERE id_trabajo = $id");
        mysqli_query($conexion, "DELETE FROM trabajos WHERE id_trabajo = $id");
    }
    $_SESSION['mensaje'] = "Trabajos eliminados correctamente.";
    header("Location: eli_trabajos.php");
    exit();
}

$trabajos = mysqli_query($conexion, "
    SELECT t.id_trabajo, t.fecha_asignacion AS fecha, p.ubicacion, 
           CONCAT(d.marca, ' ', d.modelo) AS dron, 
           GROUP_CONCAT(tar.nombre_tarea SEPARATOR ', ') AS tareas
    FROM trabajos t
    JOIN parcelas p ON t.id_parcela = p.id_parcela
    JOIN drones d ON t.id_dron = d.id_dron
    LEFT JOIN trabajos_tareas tt ON tt.id_trabajo = t.id_trabajo
    LEFT JOIN tareas tar ON tar.id_tarea = tt.id_tarea
    GROUP BY t.id_trabajo
    ORDER BY t.fecha DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>🗑️ Eliminar Trabajos - AgroSky</title>
  <link rel="stylesheet" href="../../css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">
<?php include '../../componentes/header.php'; ?>
<main class="container py-5 flex-grow-1">
  <h1 class="titulo-listado text-center mb-4">
    <i class="bi bi-journal-x me-2 text-danger"></i>Eliminar Trabajos
  </h1>

  <form method="get" class="d-flex justify-content-center mb-4">
    <input type="text" id="buscarTrabajo" class="form-control w-50 me-2" placeholder="🔍 Buscar por parcela o dron...">
    <button class="btn btn-success" type="button" onclick="filtrarTrabajos()">Buscar</button>
  </form>

  <form method="post" onsubmit="return confirmarEliminacion(event)">
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle" id="tablaTrabajos">
        <thead class="table-success text-center">
          <tr>
            <th>Fecha</th>
            <th>Parcela</th>
            <th>Dron</th>
            <th>Tarea(s)</th>
            <th>Eliminar</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($t = mysqli_fetch_assoc($trabajos)): ?>
          <tr>
            <td><?= htmlspecialchars($t['fecha']) ?></td>
            <td><?= htmlspecialchars($t['ubicacion']) ?></td>
            <td><?= htmlspecialchars($t['dron']) ?></td>
            <td><?= htmlspecialchars($t['tareas']) ?></td>
            <td class="text-center">
              <input type="checkbox" name="borrar[]" value="<?= $t['id_trabajo'] ?>">
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
        <a href="../../menu/trabajos.php" class="btn btn-success w-100 w-sm-auto px-4">
          <i class="bi bi-arrow-left-circle me-2"></i>Volver al menú de trabajos
        </a>
      </div>
    </div>
  </form>
</main>
<?php include '../../componentes/footer.php'; ?>
<script>
function filtrarTrabajos() {
  const filtro = document.getElementById('buscarTrabajo').value.toLowerCase();
  document.querySelectorAll('#tablaTrabajos tbody tr').forEach(fila => {
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
      text: 'No has seleccionado ningún trabajo.',
      confirmButtonColor: '#d33'
    });
    return false;
  }
  Swal.fire({
    title: '¿Eliminar trabajos seleccionados?',
    text: 'Esta acción eliminará los registros de trabajos y sus tareas asociadas.',
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
