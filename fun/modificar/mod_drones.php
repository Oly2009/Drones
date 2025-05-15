<?php
include '../../lib/functiones.php';
session_start();
$conexion = conectar();

// Búsqueda
$busqueda = $_GET['buscar'] ?? '';
$busqueda = mysqli_real_escape_string($conexion, $busqueda);
$filtro = '';
if (!empty($busqueda)) {
    $filtro = "WHERE d.marca LIKE '%$busqueda%' OR d.modelo LIKE '%$busqueda%' OR d.numero_serie LIKE '%$busqueda%' OR p.ubicacion LIKE '%$busqueda%'";
}

$drones_query = mysqli_query($conexion, "
    SELECT d.*, p.ubicacion, p.id_parcela AS parcela_id, t.nombre_tarea
    FROM drones d
    LEFT JOIN parcelas p ON d.id_parcela = p.id_parcela
    LEFT JOIN tareas t ON d.id_tarea = t.id_tarea
    $filtro
");
$drones_data = mysqli_fetch_all($drones_query, MYSQLI_ASSOC);

$parcelas_query = mysqli_query($conexion, "SELECT id_parcela, ubicacion FROM parcelas");
$parcelas = mysqli_fetch_all($parcelas_query, MYSQLI_ASSOC);

if (isset($_POST['accion']) && $_POST['accion'] === 'modificar_dron_ajax') {
    $id = $_POST['id_dron'];
    $estado = $_POST['estado'];
    $marca = mysqli_real_escape_string($conexion, $_POST['marca']);
    $modelo = mysqli_real_escape_string($conexion, $_POST['modelo']);
    $serie = mysqli_real_escape_string($conexion, $_POST['numero_serie']);
    $tipo = $_POST['tipo'];
    $id_parcela = $_POST['id_parcela'] === '' ? 'NULL' : $_POST['id_parcela'];

    $update = "UPDATE drones
               SET marca='$marca', modelo='$modelo', numero_serie='$serie', tipo='$tipo', estado='$estado', id_parcela=$id_parcela
               WHERE id_dron=$id";

    if (mysqli_query($conexion, $update)) {
        echo json_encode([
            'success' => true,
            'message' => 'Dron actualizado correctamente',
            'data' => [
                'id_dron' => $id,
                'marca' => htmlspecialchars($marca),
                'modelo' => htmlspecialchars($modelo),
                'numero_serie' => htmlspecialchars($serie),
                'tipo' => htmlspecialchars($tipo),
                'estado' => $estado,
                'id_parcela' => $id_parcela,
                'ubicacion' => $id_parcela === 'NULL' ? 'Sin parcela' : (mysqli_fetch_assoc(mysqli_query($conexion, "SELECT ubicacion FROM parcelas WHERE id_parcela = $id_parcela"))['ubicacion'] ?? 'Sin parcela'),
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el dron: ' . mysqli_error($conexion)]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Modificar Drones</title>
  <link rel="stylesheet" href="../../css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const forms = document.querySelectorAll('tbody tr form');
      forms.forEach(form => {
        form.addEventListener('submit', async (event) => {
          event.preventDefault();
          const formData = new FormData(form);
          formData.append('accion', 'modificar_dron_ajax');

          try {
            const response = await fetch('', {
              method: 'POST',
              body: formData
            });

            if (response.ok) {
              const data = await response.json();
              if (data.success) {
                Swal.fire({
                  icon: 'success',
                  title: '✅ Dron actualizado',
                  text: data.message,
                  timer: 1500,
                  showConfirmButton: false
                }).then(() => location.reload());
              } else {
                Swal.fire({ icon: 'error', title: '❌ Error', text: data.message });
              }
            } else {
              Swal.fire({ icon: 'error', title: '❌ Error de red', text: 'Hubo un problema al comunicarse con el servidor.' });
            }
          } catch (error) {
            console.error('Error:', error);
            Swal.fire({ icon: 'error', title: '❌ Error inesperado', text: 'Ocurrió un error al procesar la solicitud.' });
          }
        });
      });
    });
  </script>
</head>
<body class="d-flex flex-column min-vh-100">
  <div class="d-flex flex-column min-vh-100">
    <?php include '../../componentes/header.php'; ?>

    <main class="container flex-grow-1 d-flex flex-column">
      <h2 class="titulo-listado text-center mb-4">🔧 Modificar Drones</h2>

      <form method="get" class="d-flex justify-content-center mb-4">
        <input type="text" name="buscar" class="form-control w-50 me-2" placeholder="🔍 Buscar por marca, modelo, N° serie o parcela" value="<?= htmlspecialchars($busqueda) ?>">
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
            <?php if (empty($drones_data)): ?>
              <tr><td colspan="8" class="text-center text-muted">No hay drones registrados que coincidan con la búsqueda</td></tr>
            <?php endif; ?>

            <?php foreach ($drones_data as $dron): ?>
              <tr>
                <form method="post">
                  <td><input type="text" name="marca" value="<?= htmlspecialchars($dron['marca']) ?>" required class="form-control"></td>
                  <td><input type="text" name="modelo" value="<?= htmlspecialchars($dron['modelo']) ?>" required class="form-control"></td>
                  <td><input type="text" name="numero_serie" value="<?= htmlspecialchars($dron['numero_serie']) ?>" required class="form-control"></td>
                  <td>
                    <select name="tipo" class="form-select" required>
                      <?php
                      $tipos = ['eléctrico', 'gasolina', 'híbrido', 'otro'];
                      foreach ($tipos as $tipo_opcion):
                      ?>
                        <option value="<?= $tipo_opcion ?>" <?= $dron['tipo'] === $tipo_opcion ? 'selected' : '' ?>>
                          <?= ucfirst($tipo_opcion) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td>
                    <select name="id_parcela" class="form-select">
                      <option value="">Sin parcela</option>
                      <?php foreach ($parcelas as $parcela): ?>
                        <option value="<?= $parcela['id_parcela'] ?>" <?= $dron['parcela_id'] == $parcela['id_parcela'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars($parcela['ubicacion']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td><?= $dron['nombre_tarea'] ?: 'Sin tarea' ?></td>
                  <td>
                    <?php if ($dron['estado'] === 'en uso'): ?>
                      <select class="form-select" disabled>
                        <option selected>En uso</option>
                      </select>
                      <input type="hidden" name="estado" value="en uso">
                      <div class="text-muted small mt-1">Estado actual: <strong>en uso</strong></div>
                    <?php else: ?>
                      <select name="estado" class="form-select" required>
                        <?php
                        $estados = ['disponible', 'en reparación', 'fuera de servicio'];
                        foreach ($estados as $estado_opcion):
                        ?>
                          <option value="<?= $estado_opcion ?>" <?= $dron['estado'] === $estado_opcion ? 'selected' : '' ?>>
                            <?= ucfirst($estado_opcion) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <input type="hidden" name="id_dron" value="<?= $dron['id_dron'] ?>">
                    <button type="submit" class="btn btn-success btn-sm">Modificar</button>
                  </td>
                </form>
              </tr>
            <?php endforeach; ?>
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
