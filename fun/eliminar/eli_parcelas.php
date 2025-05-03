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

        // Obtener el nombre del fichero antes de eliminar
        $consulta = mysqli_query($conexion, "SELECT fichero FROM parcelas WHERE id_parcela = $id");
        $fila = mysqli_fetch_assoc($consulta);
        $fichero = $fila['fichero'] ?? '';
        $ruta = realpath(__DIR__ . "/../agregar/parcelas/" . $fichero);

        // Eliminar registros relacionados
        mysqli_query($conexion, "DELETE FROM parcelas_usuarios WHERE id_parcela = $id");
        mysqli_query($conexion, "DELETE FROM ruta WHERE id_parcela = $id");
        mysqli_query($conexion, "DELETE FROM trabajos_tareas WHERE id_trabajo IN (SELECT id_trabajo FROM trabajos WHERE id_parcela = $id)");
        mysqli_query($conexion, "DELETE FROM trabajos WHERE id_parcela = $id");
        mysqli_query($conexion, "UPDATE drones SET id_parcela = NULL WHERE id_parcela = $id");
        mysqli_query($conexion, "DELETE FROM parcelas WHERE id_parcela = $id");

        // Eliminar archivo GeoJSON si existe
        if ($ruta && file_exists($ruta)) {
            unlink($ruta);
        }
    }
    $_SESSION['mensaje'] = "Parcelas y archivos eliminados correctamente.";
    header("Location: eli_parcelas.php");
    exit();
}

$parcelas = mysqli_query($conexion, "SELECT * FROM parcelas ORDER BY id_parcela ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>🗑️ Eliminar Parcelas - AgroSky</title>
  <link rel="stylesheet" href="../../css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">
<?php include '../../componentes/header.php'; ?>
<main class="container py-5 flex-grow-1">
  <h1 class="titulo-listado text-center mb-4">
    <i class="bi bi-geo-alt-x me-2 text-danger"></i>Eliminar Parcelas
  </h1>

  <form method="get" class="d-flex justify-content-center mb-4">
    <input type="text" id="buscarParcela" class="form-control w-50 me-2" placeholder="🔍 Buscar por ubicación, cultivo o estado">
    <button class="btn btn-success" type="button" onclick="filtrarParcelas()">Buscar</button>
  </form>

  <form method="post" onsubmit="return confirmarEliminacion(event)">
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-success text-center">
          <tr>
            <th>Nombre</th>
            <th>Ubicación</th>
            <th>Tipo cultivo</th>
            <th>Área (m²)</th>
            <th>Latitud</th>
            <th>Longitud</th>
            <th>Estado</th>
            <th>Fecha registro</th>
            <th>Observaciones</th>
            <th>Eliminar</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($p = mysqli_fetch_assoc($parcelas)): ?>
          <tr>
            <td><?= htmlspecialchars($p['nombre'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['ubicacion']) ?></td>
            <td><?= htmlspecialchars($p['tipo_cultivo'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['area_m2'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['latitud']) ?></td>
            <td><?= htmlspecialchars($p['longitud']) ?></td>
            <td><?= htmlspecialchars($p['estado']) ?></td>
            <td><?= htmlspecialchars($p['fecha_registro']) ?></td>
            <td><?= htmlspecialchars($p['observaciones'] ?? '') ?></td>
            <td class="text-center">
              <input type="checkbox" name="borrar[]" value="<?= $p['id_parcela'] ?>">
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <div class="text-center mt-4">
  <div class="d-flex flex-column flex-sm-row justify-content-center align-items-stretch gap-3">
    <button type="submit" class="btn btn-danger w-100 w-sm-auto px-4">
      <i class="bi bi-trash me-2"></i>Eliminar seleccionadas
    </button>
    <a href="../../menu/parcelas.php" class="btn btn-success w-100 w-sm-auto px-4">
      <i class="bi bi-arrow-left-circle me-2"></i>Volver al menú de parcelas
    </a>
  </div>
</div>

  </form>
</main>
<?php include '../../componentes/footer.php'; ?>
<script>
function filtrarParcelas() {
  const filtro = document.getElementById('buscarParcela').value.toLowerCase();
  document.querySelectorAll('.table tbody tr').forEach(fila => {
    fila.style.display = fila.textContent.toLowerCase().includes(filtro) ? '' : 'none';
  });
}

function confirmarEliminacion(e) {
  e.preventDefault();
  const checkboxes = document.querySelectorAll('input[name="borrar[]"]:checked');
  if (checkboxes.length === 0) {
    Swal.fire({
      icon: 'warning',
      title: '⚠️ Aviso',
      text: 'No has seleccionado ninguna parcela.',
      confirmButtonColor: '#d33'
    });
    return false;
  }
  Swal.fire({
    title: '¿Estás seguro?',
    text: 'Las parcelas seleccionadas serán eliminadas y sus archivos también. Esta acción no se puede deshacer.',
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
