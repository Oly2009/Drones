<?php
include '../../lib/functiones.php';
session_start();
$conexion = conectar();
$mensaje = "";
$tipo = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $id = intval($_POST['id_parcela']);

    if ($_POST['accion'] === 'modificar') {
        $ubicacion = trim($_POST['ubicacion']);
        $nombre = trim($_POST['nombre'] ?? '');
        $tipo_cultivo = trim($_POST['tipo_cultivo'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');
        $geojsonRaw = $_POST['geojson'] ?? null; // Capture GeoJSON here

        if ($ubicacion === '') {
            $mensaje = "❌ Error: La ubicación es obligatoria";
            $tipo = "error";
        } else {
            $stmt = $conexion->prepare("UPDATE parcelas SET ubicacion=?, nombre=?, tipo_cultivo=?, observaciones=? WHERE id_parcela=?");
            $stmt->bind_param("ssssi", $ubicacion, $nombre, $tipo_cultivo, $observaciones, $id);
            $stmt->execute();
            $mensaje_datos = "✅ Datos actualizados correctamente";

            // If GeoJSON was also submitted, process it
            if ($geojsonRaw) {
                $decoded = json_decode($geojsonRaw, true);
                if ($decoded && isset($decoded['features'][0]['geometry']['coordinates'][0])) {
                    $coords = $decoded['features'][0]['geometry']['coordinates'][0];
                    $lat = $lon = 0;
                    foreach ($coords as $coord) {
                        $lon += $coord[0];
                        $lat += $coord[1];
                    }
                    $lat /= count($coords);
                    $lon /= count($coords);

                    $stmt = $conexion->prepare("SELECT fichero FROM parcelas WHERE id_parcela=?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $fichero = $stmt->get_result()->fetch_assoc()['fichero'];
                    $stmt->close();

                    $ruta = __DIR__ . "/../agregar/parcelas/" . $fichero;
                    if (file_exists($ruta)) {
                        file_put_contents($ruta, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        function calcularAreaM2($coords) {
                            $radioTierra = 6371000;
                            $lat0 = deg2rad($coords[0][1]);
                            $total = 0.0;
                            for ($i = 0; $i < count($coords) - 1; $i++) {
                                $lon1 = deg2rad($coords[$i][0]);
                                $lat1 = deg2rad($coords[$i][1]);
                                $lon2 = deg2rad($coords[$i + 1][0]);
                                $lat2 = deg2rad($coords[$i + 1][1]);

                                $x1 = $radioTierra * $lon1 * cos($lat0);
                                $y1 = $radioTierra * $lat1;
                                $x2 = $radioTierra * $lon2 * cos($lat0);
                                $y2 = $radioTierra * $lat2;

                                $total += ($x1 * $y2 - $x2 * $y1);
                            }
                            return abs($total / 2);
                        }
                        $area_m2 = calcularAreaM2($coords);
                        $stmt = $conexion->prepare("UPDATE parcelas SET latitud=?, longitud=?, area_m2=? WHERE id_parcela=?");
                        $stmt->bind_param("dddi", $lat, $lon, $area_m2, $id);
                        $stmt->execute();
                        $stmt->close();
                        $mensaje = $mensaje_datos . "<br>✅ Geometría actualizada correctamente";
                        $tipo = "success";
                    } else {
                        $mensaje = $mensaje_datos . "<br>❌ Error: El archivo GeoJSON no existe";
                        $tipo = "error";
                    }
                } else {
                    $mensaje = $mensaje_datos . "<br>❌ Error: GeoJSON inválido o vacío";
                    $tipo = "error";
                }
            } else {
                $mensaje = $mensaje_datos;
                $tipo = "success";
            }
        }
    } elseif ($_POST['accion'] === 'modificar_geojson') {
        $geojsonRaw = $_POST['geojson'];
        $decoded = json_decode($geojsonRaw, true);
        if ($decoded && isset($decoded['features'][0]['geometry']['coordinates'][0])) {
            $coords = $decoded['features'][0]['geometry']['coordinates'][0];
            $lat = $lon = 0;
            foreach ($coords as $coord) {
                $lon += $coord[0];
                $lat += $coord[1];
            }
            $lat /= count($coords);
            $lon /= count($coords);

            $stmt = $conexion->prepare("SELECT fichero FROM parcelas WHERE id_parcela=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $fichero = $stmt->get_result()->fetch_assoc()['fichero'];
            $stmt->close();

            $ruta = __DIR__ . "/../agregar/parcelas/" . $fichero;
            if (file_exists($ruta)) {
                file_put_contents($ruta, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                function calcularAreaM2($coords) {
                    $radioTierra = 6371000;
                    $lat0 = deg2rad($coords[0][1]);
                    $total = 0.0;
                    for ($i = 0; $i < count($coords) - 1; $i++) {
                        $lon1 = deg2rad($coords[$i][0]);
                        $lat1 = deg2rad($coords[$i][1]);
                        $lon2 = deg2rad($coords[$i + 1][0]);
                        $lat2 = deg2rad($coords[$i + 1][1]);

                        $x1 = $radioTierra * $lon1 * cos($lat0);
                        $y1 = $radioTierra * $lat1;
                        $x2 = $radioTierra * $lon2 * cos($lat0);
                        $y2 = $radioTierra * $lat2;

                        $total += ($x1 * $y2 - $x2 * $y1);
                    }
                    return abs($total / 2);
                }
                $area_m2 = calcularAreaM2($coords);
                $stmt = $conexion->prepare("UPDATE parcelas SET latitud=?, longitud=?, area_m2=? WHERE id_parcela=?");
                $stmt->bind_param("dddi", $lat, $lon, $area_m2, $id);
                $stmt->execute();
                $stmt->close();
                $mensaje = "✅ Geometría actualizada correctamente";
                $tipo = "success";
            } else {
                $mensaje = "❌ Error: El archivo GeoJSON no existe";
                $tipo = "error";
            }
        } else {
            $mensaje = "❌ Error: GeoJSON inválido o vacío";
            $tipo = "error";
        }
    }
}

include '../../componentes/header.php';
?>
<link rel="stylesheet" href="../../css/style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>


<body class="d-flex flex-column min-vh-100">
<main class="modificar-parcelas flex-grow-1">
    <h2 class="text-center">✏️ Modificar Parcela</h2>
    <?php if (isset($_GET['id'])):
        $idParcela = intval($_GET['id']);
        $res = mysqli_query($conexion, "SELECT * FROM parcelas WHERE id_parcela = $idParcela");
        $parcela = mysqli_fetch_assoc($res);
        ?>
        <section class="contenedor-formulario">
            <section>
                <div id="mapa"></div>
            </section>
            <section class="formulario">
                <form method="POST" class="d-flex flex-column gap-3" id="mainForm">
                    <input type="hidden" name="accion" value="modificar">
                    <input type="hidden" name="id_parcela" value="<?= $parcela['id_parcela'] ?>">

                    <label><i class="bi bi-geo-alt-fill text-primary"></i> Ubicación</label>
                    <input class="form-control" name="ubicacion" value="<?= htmlspecialchars($parcela['ubicacion']) ?>">

                    <label><i class="bi bi-tag-fill text-info"></i> Nombre</label>
                    <input class="form-control" name="nombre" value="<?= htmlspecialchars($parcela['nombre']) ?>">

                    <label><i class="bi bi-seedling text-success"></i> Tipo de cultivo</label>
                    <input class="form-control" name="tipo_cultivo" value="<?= htmlspecialchars($parcela['tipo_cultivo']) ?>">

                    <label><i class="bi bi-pencil-square text-warning"></i> Observaciones</label>
                    <textarea class="form-control" name="observaciones"><?= htmlspecialchars($parcela['observaciones']) ?></textarea>

                    <input type="hidden" name="geojson" id="geojson">

                    <div class="d-flex gap-3 align-items-stretch">
                        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar Cambios</button>
                    </div>
                    <a href="../listar/lis_parcelas.php" class="btn btn-danger w-100 mt-3"><i class="bi bi-arrow-left-circle"></i> Volver al Listado</a>
                </form>
            </section>
        </section>
        <script>
            let drawnItems = new L.FeatureGroup();
            let mapa = L.map('mapa').addLayer(drawnItems).setView([<?= $parcela['latitud'] ?>, <?= $parcela['longitud'] ?>], 17);
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles © Esri'
            }).addTo(mapa);

            // Get the initial bounds of the parcel
            fetch('../agregar/parcelas/<?= $parcela['fichero'] ?>')
                .then(r => r.json())
                .then(data => {
                    const initialLayer = L.geoJSON(data);
                    initialLayer.eachLayer(l => drawnItems.addLayer(l));
                    mapa.fitBounds(drawnItems.getBounds());
                    const initialBounds = drawnItems.getBounds();

                    // Define a maximum allowed distance (in map units, roughly meters)
                    const maxDistance = 200; // Adjust this value as needed

                    mapa.addControl(new L.Control.Draw({
                        edit: {
                            featureGroup: drawnItems,
                            remove: true,
                            edit: true // Enable editing of existing shapes
                        },
                        draw: {
                            polygon: true,
                            rectangle: true,
                            circle: false,
                            marker: false,
                            polyline: false,
                            circlemarker: false
                        }
                    }));

                    mapa.on('draw:created', function(e) {
                        const layer = e.layer;
                        const newBounds = layer.getBounds();

                        // Check if the new drawing is too far from the initial bounds
                        if (initialBounds && !initialBounds.contains(newBounds.getNorthWest()) && !initialBounds.contains(newBounds.getSouthEast())) {
                            Swal.fire({
                                title: '¡Dibujo demasiado lejos!',
                                text: 'No puedes dibujar demasiado lejos de la parcela original.',
                                icon: 'warning',
                                confirmButtonColor: '#d33'
                            });
                            mapa.removeLayer(layer); // Remove the drawn layer
                            return;
                        }

                        drawnItems.clearLayers();
                        drawnItems.addLayer(layer);
                        actualizarGeojson();
                        actualizarDatosConGeometria(layer.toGeoJSON());
                    });

                    mapa.on('draw:edited', function(e) {
                        let isWithinBounds = true;
                        e.layers.eachLayer(function(layer) {
                            const newBounds = layer.getBounds();
                            if (initialBounds && !initialBounds.contains(newBounds.getNorthWest()) && !initialBounds.contains(newBounds.getSouthEast())) {
                                isWithinBounds = false;
                            }
                        });

                        if (!isWithinBounds) {
                            Swal.fire({
                                title: '¡Modificación fuera de límites!',
                                text: 'La parcela modificada está demasiado lejos de su ubicación original.',
                                icon: 'warning',
                                confirmButtonColor: '#d33'
                            });
                            // Revert the edit (this might be tricky with leaflet-draw, a full revert might require more complex logic)
                            // A simpler approach is to just not save the changes if they are out of bounds.
                            // We will rely on the user to redraw within the limits.
                        } else {
                            actualizarGeojson();
                            e.layers.eachLayer(function(layer) {
                                actualizarDatosConGeometria(layer.toGeoJSON());
                            });
                        }
                    });

                    mapa.on('draw:deleted', actualizarGeojson);

                    function actualizarGeojson() {
                        const geo = drawnItems.toGeoJSON();
                        document.getElementById('geojson').value = JSON.stringify(geo);
                    }

                    document.getElementById('mainForm').addEventListener('submit', function() {
                        actualizarGeojson(); // Ensure GeoJSON is updated before submitting
                    });

                    function actualizarDatosConGeometria(geojson) {
                        try {
                            if (geojson && geojson.features && geojson.features.length > 0 && geojson.features[0].geometry && geojson.features[0].geometry.coordinates && geojson.features[0].geometry.coordinates[0]) {
                                const coords = geojson.features[0].geometry.coordinates[0];
                                let sumLat = 0;
                                let sumLon = 0;

                                coords.forEach(coord => {
                                    sumLon += coord[0];
                                    sumLat += coord[1];
                                });

                                const count = coords.length;
                                const latitud = count > 0 ? sumLat / count : 0;
                                const longitud = count > 0 ? sumLon / count : 0;

                                function calcularAreaM2(coords) {
                                    const radioTierra = 6371000;
                                    const lat0 = deg2rad(coords[0][1]);
                                    let total = 0.0;
                                    for (let i = 0; i < coords.length - 1; i++) {
                                        const lon1 = deg2rad(coords[i][0]);
                                        const lat1 = deg2rad(coords[i][1]);
                                        const lon2 = deg2rad(coords[i + 1][0]);
                                        const lat2 = deg2rad(coords[i + 1][1]);

                                        const x1 = radioTierra * lon1 * Math.cos(lat0);
                                        const y1 = radioTierra * lat1;
                                        const x2 = radioTierra * lon2 * Math.cos(lat0);
                                        const y2 = radioTierra * lat2;

                                        total += (x1 * y2 - x2 * y1);
                                    }
                                    return Math.abs(total / 2);
                                }

                                const area_m2 = calcularAreaM2(coords);

                                // You can optionally update the input fields in the form here if you want
                                // document.querySelector('[name="ubicacion"]').value = `Lat: ${latitud.toFixed(5)}, Lon: ${longitud.toFixed(5)}`;
                                // document.querySelector('[name="area_m2"]').value = area_m2.toFixed(2);

                                // The geojson hidden input is already updated by the 'draw:created' and 'draw:edited' events
                            }
                        } catch (error) {
                            console.error("Error al actualizar datos con geometría:", error);
                        }
                    }
                });
        </script>
    <?php endif; ?>
</main>
<script>
    <?php if ($mensaje): ?>
    Swal.fire({
        title: '<?= $mensaje ?>',
        icon: '<?= $tipo ?>',
        confirmButtonColor: '#218838'
    });
    <?php endif; ?>
</script>
<?php include '../../componentes/footer.php'; ?>
</body>