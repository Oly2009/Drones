<?php
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    echo "<p>⛔ Acceso denegado</p>";
    exit;
}

$con = conectar();

// Consulta completa con fechas nuevas
$trabajos_q = "SELECT 
    t.fecha_asignacion,
    t.fecha_ejecucion,
    t.hora,
    t.estado_general,
    p.ubicacion AS parcela,
    d.marca AS dron,
    d.modelo,
    d.numero_serie,
    GROUP_CONCAT(ta.nombre_tarea SEPARATOR ', ') AS tareas,
    u.nombre AS nombre_usuario,
    u.apellidos AS apellidos_usuario
 FROM trabajos t
 JOIN parcelas p ON t.id_parcela = p.id_parcela
 JOIN drones d ON t.id_dron = d.id_dron
 LEFT JOIN trabajos_tareas tt ON t.id_trabajo = tt.id_trabajo
 LEFT JOIN tareas ta ON ta.id_tarea = tt.id_tarea
 LEFT JOIN usuarios u ON t.id_usr = u.id_usr
 GROUP BY t.id_trabajo
 ORDER BY t.fecha_asignacion DESC, t.hora DESC";

$trabajos = mysqli_query($con, $trabajos_q);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>📋 Listado de Trabajos - AgroSky</title>
  <link rel="stylesheet" href="../../css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="d-flex flex-column min-vh-100">
<?php include '../../componentes/header.php'; ?>

<main class="flex-grow-1 container py-5">
  <section>
    <h1 class="titulo-listado">
      <i class="bi bi-journal-text me-2" style="color: #6f42c1;"></i>Listado de Trabajos
    </h1>

    <form class="busqueda-form d-flex" onsubmit="event.preventDefault(); filtrarTabla();">
      <input type="text" id="filtro" class="form-control me-2" placeholder="🔍 Buscar por cualquier campo...">
      <button type="submit" class="btn btn-success">Buscar</button>
    </form>

    <div class="table-responsive">
      <table id="tablaTrabajos" class="table align-middle">
        <thead>
          <tr>
            <th>Asignado</th>
            <th>Ejecutado</th>
            <th>Hora</th>
            <th>Parcela</th>
            <th>Marca/Modelo</th>
            <th>Nº Serie</th>
            <th>Tarea(s)</th>
            <th>Usuario</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($trabajo = mysqli_fetch_assoc($trabajos)) { ?>
          <tr>
            <td><?= htmlspecialchars($trabajo['fecha_asignacion']) ?></td>
            <td><?= $trabajo['fecha_ejecucion'] ?? '<em>—</em>' ?></td>
            <td><?= $trabajo['hora'] ?? '<em>—</em>' ?></td>
            <td><?= htmlspecialchars($trabajo['parcela']) ?></td>
            <td><?= htmlspecialchars($trabajo['dron'] . ' ' . $trabajo['modelo']) ?></td>
            <td><?= htmlspecialchars($trabajo['numero_serie']) ?></td>
            <td><?= htmlspecialchars($trabajo['tareas']) ?></td>
            <td><?= $trabajo['nombre_usuario'] ? htmlspecialchars($trabajo['nombre_usuario'] . ' ' . $trabajo['apellidos_usuario']) : 'Desconocido' ?></td>
            <td><span class="estado-<?= strtolower(str_replace(' ', '-', $trabajo['estado_general'])) ?>"><?= ucfirst($trabajo['estado_general']) ?></span></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

    <div id="mensajeNoResultados" class="alert alert-warning text-center mt-4" style="display: none;">
      ❌ No se encontraron resultados.
    </div>

    <div class="text-center mt-4">
      <a href="../../menu/trabajos.php" class="btn btn-danger rounded-pill px-4">
        <i class="bi bi-arrow-left-circle me-2"></i>Volver al menú de trabajos
      </a>
    </div>
  </section>
</main>

<?php include '../../componentes/footer.php'; ?>

<script>
function filtrarTabla() {
  const input = document.getElementById("filtro").value.toLowerCase();
  const filas = document.querySelectorAll("#tablaTrabajos tbody tr");
  let coincidencias = 0;

  filas.forEach(fila => {
    let textoFila = '';
    fila.querySelectorAll('td').forEach(td => {
      textoFila += td.innerText.toLowerCase() + ' ';
    });

    const coincide = textoFila.includes(input);
    fila.style.display = coincide ? "" : "none";
    if (coincide) coincidencias++;
  });

  const mensaje = document.getElementById("mensajeNoResultados");
  mensaje.style.display = coincidencias === 0 ? "block" : "none";
}
</script>
</body>
</html>
