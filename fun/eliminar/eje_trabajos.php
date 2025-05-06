<?php
include '../../lib/functiones.php';
session_start();

// Establecer la zona horaria para España
date_default_timezone_set('Europe/Madrid');

if (!isset($_SESSION['usuario'])) {
    echo "<p>⛔ Acceso denegado</p>";
    exit;
}

$con = conectar();
$id_sesion = $_SESSION['usuario']['id_usr'];
$esAdmin = mysqli_fetch_assoc(mysqli_query($con, "SELECT 1 FROM usuarios_roles WHERE id_usr = $id_sesion AND id_rol = 1"));

// FINALIZAR trabajo
if (isset($_GET['finalizar'])) {
    $id = intval($_GET['finalizar']);
    $fecha_finalizacion = date('Y-m-d H:i:s'); // Obtener la fecha y hora de finalización

    // Actualizar el estado general y la fecha de ejecución
    $update_trabajo_q = "UPDATE trabajos SET estado_general = 'finalizado', fecha_ejecucion = '$fecha_finalizacion' WHERE id_trabajo = $id";
    if (!mysqli_query($con, $update_trabajo_q)) {
        error_log("Error al finalizar el trabajo " . $id . ": " . mysqli_error($con));
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Error al actualizar el trabajo.']);
        exit;
    }

    // Actualizar el estado del dron a disponible
    $update_dron_q = "UPDATE drones SET estado = 'disponible' WHERE id_dron = (SELECT id_dron FROM trabajos WHERE id_trabajo = $id)";
    if (!mysqli_query($con, $update_dron_q)) {
        error_log("Error al actualizar el estado del dron para el trabajo " . $id . ": " . mysqli_error($con));
        // No es crítico, pero se puede loggear
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'fecha_ejecucion' => $fecha_finalizacion]);
    exit;
}

// EJECUTAR trabajo con mapa y animación
if (isset($_GET['ejecutar'])) {
    $id_trabajo = intval($_GET['ejecutar']);
    $trabajo_info_q = mysqli_query($con, "SELECT t.*, p.ubicacion, p.latitud, p.longitud, p.fichero, d.marca FROM trabajos t JOIN parcelas p ON t.id_parcela = p.id_parcela JOIN drones d ON t.id_dron = d.id_dron WHERE t.id_trabajo = $id_trabajo");
    $trabajo = mysqli_fetch_assoc($trabajo_info_q);

    if (!$trabajo) {
        echo "<p class='text-danger'>Error: No se encontró el trabajo con ID $id_trabajo.</p>";
        exit;
    }

    $coords_q = mysqli_query($con, "SELECT latitud, longitud FROM ruta WHERE id_parcela = {$trabajo['id_parcela']}");
    $ruta = [];
    while ($p = mysqli_fetch_assoc($coords_q)) $ruta[] = [$p['latitud'], $p['longitud']];
    mysqli_free_result($coords_q);

    $geojson = '../../fun/agregar/parcelas/' . $trabajo['fichero'];
    $geojsonData = file_exists($geojson) ? file_get_contents($geojson) : 'null';

    // Obtener las tareas asignadas a este trabajo
    $tareas_q = mysqli_query($con, "SELECT ta.nombre_tarea FROM trabajos_tareas tt JOIN tareas ta ON tt.id_tarea = ta.id_tarea WHERE tt.id_trabajo = $id_trabajo");
    $tareas = [];
    while ($tarea = mysqli_fetch_assoc($tareas_q)) {
        $tareas[] = htmlspecialchars($tarea['nombre_tarea']);
    }
    $lista_tareas = implode(', ', $tareas);
    mysqli_free_result($tareas_q);

    // Obtener la fecha y HORA actual para guardar como hora de inicio de ejecución
    $fecha_inicio_ejecucion = date('Y-m-d');
    $hora_inicio_ejecucion = date('H:i:s');

    // actualizar estado, fecha_ejecucion y hora
    $update_ejecucion_q = "UPDATE trabajos SET estado_general = 'en curso', fecha_ejecucion = '$fecha_inicio_ejecucion', hora = '$hora_inicio_ejecucion' WHERE id_trabajo = $id_trabajo";
    if (!mysqli_query($con, $update_ejecucion_q)) {
        echo "<p class='text-danger'>Error al iniciar el trabajo: " . mysqli_error($con) . "</p>";
        exit;
    }
    $update_dron_uso_q = "UPDATE drones SET estado = 'en uso' WHERE id_dron = {$trabajo['id_dron']}";
    if (!mysqli_query($con, $update_dron_uso_q)) {
        // No es crítico, se puede loggear
        error_log("Error al actualizar el estado del dron a 'en uso' para el trabajo " . $id_trabajo . ": " . mysqli_error($con));
    }

    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Ejecutar trabajo</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
        <link rel="stylesheet" href="../../css/style.css">
    </head>
    <body class="d-flex flex-column min-vh-100">
        <?php include '../../componentes/header.php'; ?>
        <main class="container py-5 flex-grow-1">
            <h2 class="titulo-listado text-center mb-4">🚁 Ejecutar trabajo</h2>
            <h5 class="text-center text-muted mb-1">📍 <?= htmlspecialchars($trabajo['ubicacion']) ?> | Marca: <?= htmlspecialchars($trabajo['marca']) ?></h5>
            <p class="text-center text-muted mb-3">🗓️ Fecha de ejecución: <?= $fecha_inicio_ejecucion ?> <br> ⏱️ Hora de ejecución: <?= $hora_inicio_ejecucion ?></p>
            <?php if (!empty($lista_tareas)): ?>
                <p class="text-center text-info mb-3">📝 Tareas asignadas: <?= $lista_tareas ?></p>
            <?php endif; ?>
            <div id="mapa-contenedor">
                <div id="mapa"></div>
            </div>
            <div id="estado-vuelo" class="text-center"></div>
            <div class="text-center mt-4">
                <a class="btn btn-danger rounded-pill px-4" href="eje_trabajos.php">⬅ Volver</a>
            </div>
        </main>
        <?php include '../../componentes/footer.php'; ?>
        <script>
            const mapa = L.map('mapa').setView([<?= $trabajo['latitud'] ?>, <?= $trabajo['longitud'] ?>], 17);
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 20,
                attribution: 'Tiles © Esri'
            }).addTo(mapa);

            const geojson = <?= $geojsonData ?>;
            if (geojson && geojson !== 'null') {
                const zona = L.geoJSON(geojson, {
                    style: { color: '#66bb6a', weight: 2, fillColor: '#66bb6a', fillOpacity: 0.2 }
                }).addTo(mapa);
                mapa.fitBounds(zona.getBounds());
            } else if (ruta.length > 0) {
                const poly = L.polyline(ruta).addTo(mapa);
                mapa.fitBounds(poly.getBounds());
            }

            const ruta = <?= json_encode($ruta) ?>;
            if (ruta.length > 0) {
                const poly = L.polyline(ruta, { color: 'red', dashArray: '5, 5' }).addTo(mapa);
                const dronIcon = L.icon({ iconUrl: '../../img/dron_volando.png', iconSize: [50, 50], iconAnchor: [25, 25] });
                let marker = L.marker(ruta[0], { icon: dronIcon }).addTo(mapa);
                let i = 0;
                document.getElementById('estado-vuelo').innerHTML = `🛫 Iniciando vuelo (${ruta.length} puntos)`;
                const volar = setInterval(() => {
                    i++;
                    if (i >= ruta.length) {
                        clearInterval(volar);
                        document.getElementById('estado-vuelo').innerHTML = '<span class="text-success">✅ Trabajo finalizado</span>';
                        fetch('eje_trabajos.php?finalizar=<?= $trabajo['id_trabajo'] ?>')
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    const fechaFinalizacion = data.fecha_ejecucion;
                                    const fechaFormateada = new Date(fechaFinalizacion).toLocaleDateString();
                                    const horaFormateada = new Date(fechaFinalizacion).toLocaleTimeString();
                                    const filaTrabajo = document.querySelector(`table tbody tr[data-id="<?= $trabajo['id_trabajo'] ?>"]`);

                                    if (filaTrabajo) {
                                        const celdas = filaTrabajo.querySelectorAll('td');
                                        if (celdas.length > 1) celdas[1].textContent = fechaFormateada; // Fecha de ejecución (se actualiza al finalizar)
                                        if (celdas.length > 2) celdas[2].textContent = horaFormateada;    // Hora de ejecución (se actualiza al finalizar)
                                        if (celdas.length > 6) celdas[6].innerHTML = '<span class="estado-finalizado">Finalizado</span>';
                                        if (celdas.length > 7) celdas[7].innerHTML = '';
                                    } else {
                                        window.location.href = 'eje_trabajos.php';
                                    }
                                } else {
                                    console.error('Error al finalizar el trabajo:', data.error);
                                    alert('Hubo un error al finalizar el trabajo. Por favor, recargue la página.');
                                }
                            });
                        return;
                    }
                    marker.setLatLng(ruta[i]);
                    document.getElementById('estado-vuelo').innerHTML = `🚁 Volando a punto ${i + 1} de ${ruta.length}`;
                }, 1000);
            } else {
                document.getElementById('estado-vuelo').innerHTML = '<span class="text-warning">⚠ No hay ruta asignada a esta parcela</span>';
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}

// LISTADO
$condicion = $esAdmin ? "" : "AND t.id_usr = $id_sesion";
$trabajos_q = "SELECT t.id_trabajo, t.fecha_asignacion, t.fecha_ejecucion, t.hora, t.estado_general,
                        p.nombre AS parcela, d.marca, d.modelo, u.nombre, u.apellidos, GROUP_CONCAT(ta.nombre_tarea SEPARATOR ', ') AS tareas
                 FROM trabajos t
                 JOIN parcelas p ON t.id_parcela = p.id_parcela
                 JOIN drones d ON t.id_dron = d.id_dron
                 JOIN usuarios u ON t.id_usr = u.id_usr
                 LEFT JOIN trabajos_tareas tt ON t.id_trabajo = tt.id_trabajo
                 LEFT JOIN tareas ta ON tt.id_tarea = ta.id_tarea
                 WHERE t.estado_general IN ('pendiente', 'en curso') $condicion
                 GROUP BY t.id_trabajo
                 ORDER BY t.fecha_asignacion ASC";

$result = mysqli_query($con, $trabajos_q);

if (!$result) {
    die("<p class='text-danger'>Error al obtener el listado de trabajos: " . mysqli_error($con) . "</p>");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Trabajos pendientes y en curso</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body class="d-flex flex-column min-vh-100">
<?php include '../../componentes/header.php'; ?>
<main class="container py-5 flex-grow-1">
    <h2 class="titulo-listado text-center mb-4">📋 Trabajos pendientes y en curso</h2>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Fecha asignación</th>
                <th>Parcela</th>
                <th>Dron</th>
                <th>Piloto</th>
                <th>Tarea(s)</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($fila = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $fila['fecha_asignacion'] ?></td>
                    <td><?= htmlspecialchars($fila['parcela']) ?></td>
                    <td><?= htmlspecialchars($fila['marca'] . ' ' . $fila['modelo']) ?></td>
                    <td><?= htmlspecialchars($fila['nombre'] . ' ' . $fila['apellidos']) ?></td>
                    <td><?= htmlspecialchars($fila['tareas']) ?></td>
                    <td><span class="estado-<?= $fila['estado_general'] ?>"><?= ucfirst($fila['estado_general']) ?></span></td>
                    <td><a href="?ejecutar=<?= $fila['id_trabajo'] ?>" class="btn btn-success rounded-pill px-3">Ejecutar</a></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <div class="text-center mt-4">
        <a href="../../menu/trabajos.php" class="btn btn-danger rounded-pill px-4">⬅ Volver</a>
    </div>
</main>
<?php include '../../componentes/footer.php'; ?>
</body>
</html>
<?php mysqli_free_result($result); ?>
<?php mysqli_close($con); ?>