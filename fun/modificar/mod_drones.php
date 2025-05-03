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

$busqueda = $_GET['buscar'] ?? '';
$busqueda = mysqli_real_escape_string($conexion, $busqueda);
$filtro = '';
if (!empty($busqueda)) {
  $filtro = "WHERE d.marca LIKE '%$busqueda%' OR d.modelo LIKE '%$busqueda%' OR d.numero_serie LIKE '%$busqueda%'";
}

$drones = mysqli_query($conexion, "
  SELECT d.*, p.ubicacion, t.nombre_tarea 
  FROM drones d 
  LEFT JOIN parcelas p ON d.id_parcela = p.id_parcela 
  LEFT JOIN tareas t ON d.id_tarea = t.id_tarea
  $filtro
");

if (isset($_POST['modificar'])) {
  $id = $_POST['id_dron'];
  $estado = $_POST['estado'];
  $marca = mysqli_real_escape_string($conexion, $_POST['marca']);
  $modelo = mysqli_real_escape_string($conexion, $_POST['modelo']);
  $serie = mysqli_real_escape_string($conexion, $_POST['numero_serie']);
  $tipo = $_POST['tipo'];

  $update = "UPDATE drones 
             SET marca='$marca', modelo='$modelo', numero_serie='$serie', tipo='$tipo', estado='$estado' 
             WHERE id_dron=$id";

  if (mysqli_query($conexion, $update)) {
    echo "<script>
      Swal.fire({ icon: 'success', title: '✅ Dron actualizado' }).then(() => location.href='mod_drones.php');
    </script>";
  } else {
    echo "<script>
      Swal.fire({ icon: 'error', title: '❌ Error al actualizar' });
    </script>";
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Modificar Drones</title>
  <link rel="stylesheet" href="../../css/style.css">
</head>
<body class="d-flex flex-column min-vh-100">
  <div class="d-flex flex-column min-vh-100">
    <?php include '../../componentes/header.php'; ?>

    <main class="container flex-grow-1 d-flex flex-column">
      <h2 class="titulo-listado text-center mb-4">🔧 Modificar Drones</h2>

      <form method="get" class="d-flex justify-content-center mb-4">
        <input type="text" name="buscar" class="form-control w-50 me-2" placeholder="🔍 Buscar por marca, modelo o N° serie" value="<?= htmlspecialchars($busqueda) ?>">
        <button class="btn btn-success" type="submit">Buscar</button>
      </form>

      <div class="table-responsive">
        <table class="table table-bordered">
          <thead class="table-success text-center">
            <tr>
              <th>Marca</th><th>Modelo</th><th>N.º Serie</th><th>Tipo</th><th>Parcela</th><th>Tarea</th><th>Estado</th><th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($drones) == 0): ?>
              <tr>
                <td colspan="8" class="text-center text-muted">No hay drones registrados que coincidan con la búsqueda</td>
              </tr>
            <?php endif; ?>

            <?php while ($dron = mysqli_fetch_assoc($drones)): ?>
              <tr>
                <form method="post">
                  <td><input type="text" name="marca" value="<?= htmlspecialchars($dron['marca']) ?>" required class="form-control"></td>
                  <td><input type="text" name="modelo" value="<?= htmlspecialchars($dron['modelo']) ?>" required class="form-control"></td>
                  <td><input type="text" name="numero_serie" value="<?= htmlspecialchars($dron['numero_serie']) ?>" required class="form-control"></td>
                  <td><input type="text" name="tipo" value="<?= htmlspecialchars($dron['tipo'] ?? 'Sin tipo') ?>" class="form-control"></td>
                  <td><?= $dron['ubicacion'] ?: 'Sin parcela' ?></td>
                  <td><?= $dron['nombre_tarea'] ?: 'Sin tarea' ?></td>
                  <td>
                    <select name="estado" class="form-select" required>
                      <option value="disponible" <?= $dron['estado'] === 'disponible' ? 'selected' : '' ?>>disponible</option>
                      <option value="en reparación" <?= $dron['estado'] === 'en reparación' ? 'selected' : '' ?>>en reparación</option>
                      <option value="en uso" <?= $dron['estado'] === 'en uso' ? 'selected' : '' ?>>en uso</option>
                      <option value="fuera de servicio" <?= $dron['estado'] === 'fuera de servicio' ? 'selected' : '' ?>>fuera de servicio</option>
                    </select>
                  </td>
                  <td class="text-center">
                    <input type="hidden" name="id_dron" value="<?= $dron['id_dron'] ?>">
                    <button type="submit" name="modificar" class="btn btn-success btn-sm">Modificar</button>
                  </td>
                </form>
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
