<?php
// =================== CONFIG ===================
session_start();
include '../../lib/functiones.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit();
}

$conexion = conectar();
$email = $_SESSION['usuario']['email'];
$id_usr_sesion = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT id_usr FROM usuarios WHERE email = '$email'"))['id_usr'];

// ============ FORMULARIO POST ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_parcela = intval($_POST['id_parcela']);
    $id_dron = intval($_POST['id_dron']);
    $id_piloto = intval($_POST['id_piloto']);
    $fecha_trabajo = mysqli_real_escape_string($conexion, $_POST['fecha_trabajo']);

    $dron_info = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT id_tarea FROM drones WHERE id_dron = $id_dron"));
    $id_tarea = $dron_info['id_tarea'] ?? null;

    if ($id_tarea) {
        $insert_trabajo = "INSERT INTO trabajos (fecha_asignacion, id_dron, id_parcela, id_usr, estado_general) VALUES ('$fecha_trabajo', $id_dron, $id_parcela, $id_piloto, 'pendiente')";
        if (mysqli_query($conexion, $insert_trabajo)) {
            $id_trabajo = mysqli_insert_id($conexion);
            $insert_trabajo_tarea = "INSERT INTO trabajos_tareas (id_trabajo, id_tarea) VALUES ($id_trabajo, $id_tarea)";
            if (mysqli_query($conexion, $insert_trabajo_tarea)) {
                $_SESSION['mensaje_exito'] = "✅ Trabajo asignado correctamente.";
                header("Location: agr_trabajos.php");
                exit();
            } else {
                $_SESSION['mensaje_error'] = "❌ Error al asignar la tarea al trabajo: " . mysqli_error($conexion);
            }
        } else {
            $_SESSION['mensaje_error'] = "❌ Error al asignar el trabajo: " . mysqli_error($conexion);
        }
    } else {
        $_SESSION['mensaje_error'] = "❌ No se pudo obtener la tarea del dron.";
    }
    header("Location: agr_trabajos.php");
    exit();
}

// =========== PETICIONES AJAX ============
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'cargar_recursos' && isset($_GET['id_parcela'])) {
        $id_parcela = intval($_GET['id_parcela']);
        try {
            $query_drones = "SELECT d.id_dron, d.marca, d.modelo, d.estado
                             FROM drones d
                             WHERE d.id_parcela = $id_parcela
                               AND d.estado = 'disponible'";
            $drones = mysqli_query($conexion, $query_drones);
            if (!$drones) throw new Exception("Error al cargar los drones: " . mysqli_error($conexion));
            $drones_data = mysqli_fetch_all($drones, MYSQLI_ASSOC);

            $query_pilotos = "SELECT u.id_usr, u.nombre, u.apellidos
                             FROM usuarios u
                             INNER JOIN usuarios_roles ur ON u.id_usr = ur.id_usr
                             INNER JOIN parcelas_usuarios pu ON u.id_usr = pu.id_usr
                             WHERE ur.id_rol = 2 AND pu.id_parcela = $id_parcela";
            $pilotos = mysqli_query($conexion, $query_pilotos);
            if (!$pilotos) throw new Exception("Error al cargar los pilotos: " . mysqli_error($conexion));
            $pilotos_data = mysqli_fetch_all($pilotos, MYSQLI_ASSOC);

            header('Content-Type: application/json');
            echo json_encode(['drones' => $drones_data, 'pilotos' => $pilotos_data, 'success' => true]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    if ($_GET['action'] === 'cargar_tarea_dron' && isset($_GET['id_dron'])) {
        $id_dron = intval($_GET['id_dron']);
        try {
            $query = "SELECT t.nombre_tarea FROM drones d
                      LEFT JOIN tareas t ON d.id_tarea = t.id_tarea
                      WHERE d.id_dron = $id_dron";
            $result = mysqli_query($conexion, $query);
            if (!$result) throw new Exception("Error al cargar la tarea del dron: " . mysqli_error($conexion));
            $row = mysqli_fetch_assoc($result);
            header('Content-Type: application/json');
            echo json_encode(['tarea' => $row['nombre_tarea'] ?? 'Sin tarea asignada', 'success' => true]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }
}

$parcelas = mysqli_fetch_all(mysqli_query($conexion, "SELECT * FROM parcelas WHERE EXISTS (SELECT 1 FROM ruta WHERE ruta.id_parcela = parcelas.id_parcela)"), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>➕ Asignar Trabajo - AgroSky</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="registro-body" style="background-image: url('../../img/dron_fondo.png');">
<?php include '../../componentes/header.php'; ?>
<main class="registro-main">
  <div class="formulario-registro">
    <h2><i class="bi bi-clipboard2-check-fill me-2 text-success"></i>Asignar Trabajo<br><span style="color: #2e7d32;">- AgroSky -</span></h2>

    <form method="post">
      <label>📍 Parcela</label>
      <select name="id_parcela" id="id_parcela" required onchange="cargarRecursos(this.value)">
        <option value="">-- Selecciona una parcela --</option>
        <?php foreach ($parcelas as $p): ?>
          <option value="<?= $p['id_parcela'] ?>"><?= htmlspecialchars($p['nombre'] ?? $p['ubicacion']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>🚁 Dron</label>
      <select name="id_dron" id="id_dron" required disabled onchange="mostrarTareaDron(this.value)">
        <option value="">-- Selecciona una parcela primero --</option>
      </select>

      <label>⚙️ Tarea</label>
      <input type="text" id="tarea_dron" readonly placeholder="Se mostrará automáticamente">

      <label>👨‍✈️ Piloto</label>
      <select name="id_piloto" id="id_piloto" required disabled>
        <option value="">-- Selecciona una parcela primero --</option>
      </select>

      <label>📅 Fecha</label>
      <input type="date" name="fecha_trabajo" id="fecha_trabajo" required>

      <div class="botones">
        <button type="submit" class="btn btn-primario">Asignar</button>
        <a href="../../menu/trabajos.php" class="btn btn-secundario">Volver</a>
      </div>
    </form>
  </div>
</main>
<?php include '../../componentes/footer.php'; ?>
<script>
const currentFile = window.location.pathname.split('/').pop();
function cargarRecursos(idParcela) {
    if (!idParcela) return resetearCampos();
    const dronSelect = document.getElementById('id_dron');
    const pilotoSelect = document.getElementById('id_piloto');
    const tareaInput = document.getElementById('tarea_dron');
    dronSelect.innerHTML = '<option value="">Cargando drones...</option>';
    pilotoSelect.innerHTML = '<option value="">Cargando pilotos...</option>';
    dronSelect.disabled = true;
    pilotoSelect.disabled = true;
    tareaInput.value = '';
    const timestamp = new Date().getTime();
    fetch(`${currentFile}?action=cargar_recursos&id_parcela=${idParcela}&_=${timestamp}`)
        .then(res => res.ok ? res.json() : Promise.reject(res.statusText))
        .then(data => {
            if (!data.success) throw new Error(data.error);
            dronSelect.innerHTML = '<option value="">-- Elige un dron --</option>';
            data.drones.forEach(d => {
                dronSelect.innerHTML += `<option value="${d.id_dron}">${d.marca} ${d.modelo}</option>`;
            });
            dronSelect.disabled = !data.drones.length;
            pilotoSelect.innerHTML = '<option value="">-- Elige un piloto --</option>';
            data.pilotos.forEach(p => {
                pilotoSelect.innerHTML += `<option value="${p.id_usr}">${p.nombre} ${p.apellidos}</option>`;
            });
            pilotoSelect.disabled = !data.pilotos.length;
        })
        .catch(error => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los recursos.', footer: error.message });
            resetearCampos();
        });
}
function mostrarTareaDron(idDron) {
    const tareaInput = document.getElementById('tarea_dron');
    if (!idDron) return tareaInput.value = '';
    tareaInput.value = 'Cargando...';
    const timestamp = new Date().getTime();
    fetch(`${currentFile}?action=cargar_tarea_dron&id_dron=${idDron}&_=${timestamp}`)
        .then(res => res.ok ? res.json() : Promise.reject(res.statusText))
        .then(data => tareaInput.value = data.success ? data.tarea : 'Error al cargar tarea')
        .catch(error => {
            tareaInput.value = 'Error al cargar tarea';
            Swal.fire({ icon: 'warning', title: 'Error al cargar tarea', text: error.message, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        });
}
function resetearCampos() {
    document.getElementById('id_dron').innerHTML = '<option value="">-- Selecciona una parcela primero --</option>';
    document.getElementById('id_dron').disabled = true;
    document.getElementById('id_piloto').innerHTML = '<option value="">-- Selecciona una parcela primero --</option>';
    document.getElementById('id_piloto').disabled = true;
    document.getElementById('tarea_dron').value = '';
}
document.addEventListener('DOMContentLoaded', () => {
    const fechaInput = document.getElementById('fecha_trabajo');
    const today = new Date().toISOString().split('T')[0];
    fechaInput.value = today;
    fechaInput.min = today;
});
</script>
<?php if (isset($_SESSION['mensaje_exito'])): ?>
<script>
Swal.fire({ icon: 'success', title: '¡Éxito!', text: <?= json_encode($_SESSION['mensaje_exito']) ?>, confirmButtonColor: '#28a745' });
</script>
<?php unset($_SESSION['mensaje_exito']); endif; ?>
<?php if (isset($_SESSION['mensaje_error'])): ?>
<script>
Swal.fire({ icon: 'error', title: 'Error', text: <?= json_encode($_SESSION['mensaje_error']) ?>, confirmButtonColor: '#dc3545' });
</script>
<?php unset($_SESSION['mensaje_error']); endif; ?>
</body>
</html>
