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

// Determinar si el usuario es administrador
// Convertir el resultado de la consulta a un booleano para mayor claridad
$esAdmin_query = mysqli_query($con, "SELECT 1 FROM usuarios_roles WHERE id_usr = $id_sesion AND id_rol = 1");
$esAdmin = (mysqli_fetch_assoc($esAdmin_query) !== null);
mysqli_free_result($esAdmin_query); // Liberar el resultado de la consulta

// FINALIZAR trabajo
if (isset($_GET['finalizar'])) {
    $id_trabajo = intval($_GET['finalizar']);
    $fecha_finalizacion = date('Y-m-d H:i:s'); // Obtener la fecha y hora de finalización

    // Iniciar transacción para asegurar que todas las actualizaciones se hagan o ninguna
    mysqli_begin_transaction($con);

    try {
        // Obtener el ID del dron asociado a este trabajo antes de actualizar el trabajo
        $get_dron_id_q = "SELECT id_dron FROM trabajos WHERE id_trabajo = $id_trabajo";
        $dron_result = mysqli_query($con, $get_dron_id_q);
        if (!$dron_result || mysqli_num_rows($dron_result) == 0) {
            throw new Exception("No se encontró el dron para el trabajo ID: " . $id_trabajo);
        }
        $dron_info = mysqli_fetch_assoc($dron_result);
        $id_dron = $dron_info['id_dron'];
        mysqli_free_result($dron_result);

        // 1. Actualizar el estado general del trabajo y la fecha/hora de ejecución
        $update_trabajo_q = "UPDATE trabajos SET estado_general = 'finalizado', fecha_ejecucion = '$fecha_finalizacion', hora = TIME('$fecha_finalizacion') WHERE id_trabajo = $id_trabajo";
        if (!mysqli_query($con, $update_trabajo_q)) {
            throw new Exception("Error al actualizar el trabajo a 'finalizado': " . mysqli_error($con));
        }

        // 2. Actualizar el estado del dron a 'disponible' E incrementar su contador de vuelos
        $update_dron_q = "UPDATE drones SET estado = 'disponible', numero_vuelos = numero_vuelos + 1 WHERE id_dron = $id_dron";
        if (!mysqli_query($con, $update_dron_q)) {
            throw new Exception("Error al actualizar el estado y el contador de vuelos del dron: " . mysqli_error($con));
        }

        // Si todo ha ido bien, confirmar la transacción
        mysqli_commit($con);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'fecha_ejecucion' => $fecha_finalizacion]);
        exit;

    } catch (Exception $e) {
        // Si hay algún error, revertir todos los cambios de la transacción
        mysqli_rollback($con);
        error_log("Error al finalizar el trabajo " . $id_trabajo . ": " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// EJECUTAR trabajo con mapa y animación
if (isset($_GET['ejecutar'])) {
    $id_trabajo = intval($_GET['ejecutar']);

    // Obtener información detallada del trabajo, incluyendo id_usr para verificar permisos
    $trabajo_info_q = mysqli_query($con, "SELECT t.*, p.ubicacion, p.latitud, p.longitud, p.fichero, d.marca FROM trabajos t JOIN parcelas p ON t.id_parcela = p.id_parcela JOIN drones d ON t.id_dron = d.id_dron WHERE t.id_trabajo = $id_trabajo");
    $trabajo = mysqli_fetch_assoc($trabajo_info_q);
    mysqli_free_result($trabajo_info_q); // Liberar el resultado de la consulta

    if (!$trabajo) {
        echo "<p class='text-danger'>Error: No se encontró el trabajo con ID $id_trabajo.</p>";
        exit;
    }

    // Asegurar que solo el piloto asignado pueda ejecutar/reanudar su trabajo
    // El administrador no debe ejecutar trabajos desde esta interfaz, sino supervisar.
    if ($esAdmin || $_SESSION['usuario']['id_usr'] != $trabajo['id_usr'] || !in_array($trabajo['estado_general'], ['pendiente', 'en curso'])) {
        echo "<p class='text-danger'>⛔ Acceso denegado para ejecutar/reanudar este trabajo.</p>";
        exit;
    }

    // Si el trabajo estaba 'en curso' y se "reanuda", no actualizamos la fecha/hora de inicio,
    // porque ya se inició. Solo la actualizamos si pasa de 'pendiente' a 'en curso'.
    if ($trabajo['estado_general'] == 'pendiente') {
        $fecha_inicio_ejecucion = date('Y-m-d');
        $hora_inicio_ejecucion = date('H:i:s');
    } else { // Si está 'en curso', usamos las que ya tiene
        $fecha_inicio_ejecucion = $trabajo['fecha_ejecucion'];
        $hora_inicio_ejecucion = $trabajo['hora'];
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

    // Iniciar transacción para asegurar que todas las actualizaciones se hagan o ninguna
    mysqli_begin_transaction($con);

    try {
        // Actualizar estado a 'en curso' si no lo está ya, y fecha/hora si pasa de 'pendiente'
        if ($trabajo['estado_general'] == 'pendiente') {
            $update_ejecucion_q = "UPDATE trabajos SET estado_general = 'en curso', fecha_ejecucion = '$fecha_inicio_ejecucion', hora = '$hora_inicio_ejecucion' WHERE id_trabajo = $id_trabajo";
        } else { // Si ya está 'en curso', solo aseguramos que el estado del dron sea 'en uso'
            $update_ejecucion_q = "UPDATE trabajos SET estado_general = 'en curso' WHERE id_trabajo = $id_trabajo"; // No actualizamos fecha/hora si ya está en curso
        }

        if (!mysqli_query($con, $update_ejecucion_q)) {
            throw new Exception("Error al iniciar/reanudar el trabajo: " . mysqli_error($con));
        }

        $update_dron_uso_q = "UPDATE drones SET estado = 'en uso' WHERE id_dron = {$trabajo['id_dron']}";
        if (!mysqli_query($con, $update_dron_uso_q)) {
            throw new Exception("Error al actualizar el estado del dron a 'en uso': " . mysqli_error($con));
        }

        // Si todo ha ido bien, confirmar la transacción
        mysqli_commit($con);

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
                            // Llamada AJAX para finalizar el trabajo y actualizar el contador de vuelos
                            fetch('eje_trabajos.php?finalizar=<?= $trabajo['id_trabajo'] ?>')
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        console.log('Trabajo finalizado y dron actualizado. Fecha de ejecución:', data.fecha_ejecucion);
                                        // Redirigir o recargar la página para ver los datos actualizados en la tabla
                                        window.location.href = 'eje_trabajos.php'; // Redirige a la lista de trabajos pendientes
                                    } else {
                                        console.error('Error al finalizar el trabajo:', data.error);
                                        alert('Hubo un error al finalizar el trabajo. Por favor, recargue la página. Detalle: ' + data.error);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error en la solicitud Fetch:', error);
                                    alert('Error de red al intentar finalizar el trabajo.');
                                });
                            return;
                        }
                        marker.setLatLng(ruta[i]);
                        document.getElementById('estado-vuelo').innerHTML = `🚁 Volando a punto ${i + 1} de ${ruta.length}`;
                    }, 1000); // Velocidad de simulación del dron (1 segundo por punto)
                } else {
                    document.getElementById('estado-vuelo').innerHTML = '<span class="text-warning">⚠ No hay ruta asignada a esta parcela</span>';
                    // Si no hay ruta, permitir finalizar inmediatamente si es un trabajo 'pendiente' o 'en curso'
                    if ('<?= $trabajo['estado_general'] ?>' === 'pendiente' || '<?= $trabajo['estado_general'] ?>' === 'en curso') {
                           document.getElementById('estado-vuelo').innerHTML += '<br><button class="btn btn-primary mt-3" onclick="finalizarTrabajoSinRuta(<?= $trabajo['id_trabajo'] ?>)">Finalizar Trabajo (Sin Ruta)</button>';
                    }
                }

                function finalizarTrabajoSinRuta(idTrabajo) {
                    if (confirm('¿Está seguro de que desea finalizar este trabajo? No hay ruta asignada.')) {
                        fetch(`eje_trabajos.php?finalizar=${idTrabajo}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert('Trabajo finalizado correctamente.');
                                    window.location.href = 'eje_trabajos.php';
                                } else {
                                    alert('Error al finalizar el trabajo: ' + data.error);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Error de red al finalizar el trabajo.');
                            });
                    }
                }
            </script>
        </body>
        </html>
        <?php
        exit; // Es importante salir aquí para que no se renderice la tabla de listado
    } catch (Exception $e) {
        mysqli_rollback($con); // Revertir en caso de error durante la preparación para la ejecución
        echo "<p class='text-danger'>Error al iniciar/reanudar el trabajo: " . $e->getMessage() . "</p>";
        exit;
    }
}

// LISTADO DE TRABAJOS (PARA AMBOS ROLES)
$condicion = $esAdmin ? "" : "AND t.id_usr = $id_sesion";
// Ajusta la consulta para incluir el numero_vuelos del dron
$trabajos_q = "SELECT t.id_trabajo, t.fecha_asignacion, t.fecha_ejecucion, t.hora, t.estado_general,
                        p.nombre AS parcela, d.marca, d.modelo, d.numero_vuelos, u.nombre, u.apellidos, GROUP_CONCAT(ta.nombre_tarea SEPARATOR ', ') AS tareas, t.id_usr
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
                <th>Vuelos Dron</th>
                <th>Piloto</th>
                <th>Tarea(s)</th> <th>Acción</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($fila = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $fila['fecha_asignacion'] ?></td>
                    <td><?= htmlspecialchars($fila['parcela']) ?></td>
                    <td><?= htmlspecialchars($fila['marca'] . ' ' . $fila['modelo']) ?></td>
                    <td><?= htmlspecialchars($fila['numero_vuelos']) ?></td>
                    <td><?= htmlspecialchars($fila['nombre'] . ' ' . $fila['apellidos']) ?></td>
                    <td>
                        <?php
                        // Si el usuario no es admin (es piloto), muestra el estado del trabajo aquí.
                        // Si es admin, muestra las tareas asignadas.
                        if (!$esAdmin) {
                            echo '<span class="estado-' . $fila['estado_general'] . '">' . ucfirst($fila['estado_general']) . '</span>';
                        } else {
                            echo htmlspecialchars($fila['tareas']);
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        // Mostrar el botón "Ejecutar" o "Reanudar"
                        if (!$esAdmin && $fila['id_usr'] == $id_sesion) {
                            if ($fila['estado_general'] == 'pendiente') {
                                echo '<a href="?ejecutar=' . $fila['id_trabajo'] . '" class="btn btn-success rounded-pill px-3">Ejecutar</a>';
                            } elseif ($fila['estado_general'] == 'en curso') {
                                // El trabajo está "en curso", mostrar botón de reanudar
                                echo '<a href="?ejecutar=' . $fila['id_trabajo'] . '" class="btn btn-warning rounded-pill px-3">Reanudar</a>';
                            }
                        } else {
                            echo '<span class="text-muted">No disponible</span>';
                        }
                        ?>
                    </td>
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