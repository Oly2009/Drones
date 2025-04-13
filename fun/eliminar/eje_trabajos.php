<?php
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    echo "<p>⛔ Acceso denegado</p>";
    exit;
}

$con = conectar();

function finalizarTrabajo($con, $id_trabajo) {
    $trabajo_q = "SELECT id_trabajo, id_parcela, id_dron FROM trabajos WHERE id_trabajo = ?";
    $stmt = mysqli_prepare($con, $trabajo_q);
    mysqli_stmt_bind_param($stmt, "i", $id_trabajo);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $trabajo = mysqli_fetch_assoc($resultado);

    if ($trabajo) {
        mysqli_query($con, "UPDATE trabajos SET estado_general = 'finalizado' WHERE id_trabajo = $id_trabajo");
        mysqli_query($con, "DELETE FROM trabajos_tareas WHERE id_trabajo = $id_trabajo");
        mysqli_query($con, "UPDATE drones SET estado = 'disponible' WHERE id_dron = " . $trabajo['id_dron']);
        return true;
    }
    return false;
}

if (isset($_GET['finalizar'])) {
    $id = intval($_GET['finalizar']);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => finalizarTrabajo($con, $id),
        'message' => 'Trabajo finalizado correctamente'
    ]);
    exit;
}

if (isset($_GET['preview']) || isset($_GET['ejecutar'])) {
    $id_trabajo = intval($_GET['preview'] ?? $_GET['ejecutar']);

    $q = "SELECT t.*, p.ubicacion, p.fichero, p.latitud, p.longitud, d.marca 
          FROM trabajos t
          JOIN parcelas p ON t.id_parcela = p.id_parcela
          JOIN drones d ON t.id_dron = d.id_dron
          WHERE t.id_trabajo = $id_trabajo";
    $trabajo = mysqli_fetch_assoc(mysqli_query($con, $q));

    $ruta_q = "SELECT latitud, longitud FROM ruta WHERE id_parcela = " . $trabajo['id_parcela'];
    $ruta_result = mysqli_query($con, $ruta_q);
    $coordenadas = [];
    while ($punto = mysqli_fetch_assoc($ruta_result)) {
        $coordenadas[] = [$punto['latitud'], $punto['longitud']];
    }

    $geojsonPath = '../../fun/agregar/parcelas/' . $trabajo['fichero'];
    $geojson = file_exists($geojsonPath) ? file_get_contents($geojsonPath) : 'null';
    if (json_decode($geojson) === null) $geojson = 'null';

    if (isset($_GET['ejecutar'])) {
        mysqli_query($con, "UPDATE trabajos SET estado_general = 'en curso' WHERE id_trabajo = $id_trabajo");
        mysqli_query($con, "UPDATE drones SET estado = 'en uso' WHERE id_dron = " . $trabajo['id_dron']);
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ejecutar trabajo</title>
    <link rel="stylesheet" href="../../css/ejecutarTrabajo.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.0.2/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.0.2/dist/leaflet.js"></script>
</head>
<body>
<h2 class="titulo">🚁 <?= isset($_GET['preview']) ? 'Vista previa de la parcela' : 'Ejecutar trabajo' ?></h2>
<h3 style="text-align:center;">📍 <?= htmlspecialchars($trabajo['ubicacion']) ?> | Marca: <?= htmlspecialchars($trabajo['marca']) ?></h3>
<div id="mapa"></div>
<div id="estado-vuelo"></div>
<div style="text-align:center; margin-top: 20px;">
    <a class="btn btn-secundario" href="eje_trabajos.php">⬅ Volver</a>
</div>
<script>
    let mapa = L.map('mapa').setView([<?= $trabajo['latitud'] ?>, <?= $trabajo['longitud'] ?>], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(mapa);

    const geojsonData = <?= $geojson ?>;
    if (geojsonData && geojsonData !== 'null') {
        const capa = L.geoJSON(geojsonData, {
            style: {
                color: '#66bb6a', weight: 3, opacity: 0.7,
                fillColor: '#66bb6a', fillOpacity: 0.3
            }
        }).addTo(mapa);
        mapa.fitBounds(capa.getBounds());
    }

    const ruta = <?= json_encode($coordenadas) ?>;
    if (ruta.length > 0) {
        const linea = L.polyline(ruta, {color: 'red', weight: 2, dashArray: '5, 5'}).addTo(mapa);
        if (!geojsonData || geojsonData === 'null') mapa.fitBounds(linea.getBounds());

        const dronIcon = L.icon({
            iconUrl: '../../img/dron_volando.png',
            iconSize: [60, 60],
            iconAnchor: [30, 30],
            className: 'no-shadow'
        });

        let marker = L.marker(ruta[0], {icon: dronIcon}).addTo(mapa);
        let actual = 0;
        const total = ruta.length;

        <?php if (isset($_GET['ejecutar'])): ?>
        document.getElementById('estado-vuelo').textContent = `Volando al punto 1 de ${total}`;
        const volar = setInterval(() => {
            actual++;
            if (actual >= total) {
                clearInterval(volar);
                document.getElementById('estado-vuelo').textContent = '✅ Trabajo terminado';
                fetch('eje_trabajos.php?finalizar=<?= $trabajo['id_trabajo'] ?>');
                return;
            }
            marker.setLatLng(ruta[actual]);
            document.getElementById('estado-vuelo').textContent = `Volando al punto ${actual + 1} de ${total}`;
        }, 1000);
        <?php endif; ?>
    } else {
        document.getElementById('estado-vuelo').textContent = '⚠ No hay ruta disponible';
    }
</script>
</body>
</html>
<?php
    exit;
}

$trabajos_q = "SELECT t.id_trabajo, t.id_parcela, t.id_dron, t.estado_general,
                      p.ubicacion, p.fichero, d.marca, d.modelo,
                      ta.nombre_tarea, u.nombre AS nombre_usuario, u.apellidos AS apellidos_usuario,
                      ROW_NUMBER() OVER (PARTITION BY t.id_parcela, d.id_tarea ORDER BY t.id_trabajo ASC) AS orden
               FROM trabajos t
               JOIN parcelas p ON t.id_parcela = p.id_parcela
               JOIN drones d ON t.id_dron = d.id_dron
               JOIN tareas ta ON d.id_tarea = ta.id_tarea
               JOIN usuarios u ON t.id_usr = u.id_usr
               WHERE t.estado_general IN ('pendiente', 'en curso')
               ORDER BY t.id_parcela, d.id_tarea, t.id_trabajo ASC";
$resultado = mysqli_query($con, $trabajos_q);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ejecutar trabajo</title>
    <link rel="stylesheet" href="../../css/ejecutarTrabajo.css">
</head>
<body>
<h2 class="titulo">🚁 Ejecutar trabajo</h2>
<?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
    <ul class="lista-trabajos">
        <?php while ($trabajo = mysqli_fetch_assoc($resultado)): ?>
            <li>
                <div>
                    <span><strong>Orden:</strong> <?= $trabajo['orden'] ?> | <?= htmlspecialchars($trabajo['ubicacion']) ?> (<?= htmlspecialchars($trabajo['marca']) ?> <?= htmlspecialchars($trabajo['modelo']) ?>)</span>
                    <div class="tarea-info">Tarea: <?= htmlspecialchars($trabajo['nombre_tarea']) ?> | Estado: <?= htmlspecialchars($trabajo['estado_general']) ?></div>
                    <div class="tarea-info">Asignado a: <?= htmlspecialchars($trabajo['nombre_usuario'] . ' ' . $trabajo['apellidos_usuario']) ?></div>
                </div>
                <div class="botones">
                    <a class="btn btn-secundario" href="?preview=<?= $trabajo['id_trabajo'] ?>">Ver mapa</a>
                    <a class="btn btn-secundario" href="?ejecutar=<?= $trabajo['id_trabajo'] ?>">Ejecutar</a>
                </div>
            </li>
        <?php endwhile; ?>
    </ul>
<?php else: ?>
    <p class="mensaje-info">No hay trabajos pendientes en este momento.</p>
<?php endif; ?>
<div class="volver-contenedor">
    <a href="../trabajos.php" class="btn btn-secundario">⬅ Volver</a>
</div>
</body>
</html>