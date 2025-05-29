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
        $geojsonRaw = $_POST['geojson'] ?? null;

        if ($ubicacion === '') {
            $mensaje = "Ubicación obligatoria.";
            $tipo = "error";
        } else {
            $stmt = $conexion->prepare("UPDATE parcelas SET ubicacion=?, nombre=?, tipo_cultivo=?, observaciones=? WHERE id_parcela=?");
            $stmt->bind_param("ssssi", $ubicacion, $nombre, $tipo_cultivo, $observaciones, $id);
            $stmt->execute();
            $mensaje_datos = "Datos actualizados.";

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
                        $mensaje .= "\nMapa actualizado.";
                        $tipo = "success";
                    } else {
                        $mensaje .= "\nArchivo GeoJSON no encontrado.";
                        $tipo = "error";
                    }
                } else {
                    $mensaje .= "\nGeoJSON inválido.";
                    $tipo = "error";
                }
            } else {
                $mensaje = $mensaje_datos;
                $tipo = "success";
            }
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
<section><div id="mapa"></div></section>
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
</section></section>
<script>
let drawnItems = new L.FeatureGroup();
let mapa = L.map('mapa').addLayer(drawnItems).setView([<?= $parcela['latitud'] ?>, <?= $parcela['longitud'] ?>], 17);
L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: 'Tiles © Esri'
}).addTo(mapa);

fetch('../agregar/parcelas/<?= $parcela['fichero'] ?>')
.then(r => r.json())
.then(data => {
    const initialLayer = L.geoJSON(data);
    initialLayer.eachLayer(l => drawnItems.addLayer(l));
    mapa.fitBounds(drawnItems.getBounds());

    mapa.addControl(new L.Control.Draw({
        edit: { featureGroup: drawnItems, remove: true, edit: true },
        draw: {
            polygon: true,
            rectangle: true,
            circle: false,
            polyline: false,
            marker: false,
            circlemarker: false
        }
    }));

    function actualizarGeojson() {
        const geo = drawnItems.toGeoJSON();
        document.getElementById('geojson').value = JSON.stringify(geo);

        const formData = new FormData(document.getElementById('mainForm'));
        fetch('', { method: 'POST', body: formData })
            .then(() => {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: 'Parcela modificada correctamente.',
                    confirmButtonColor: '#218838'
                });
                fetch('../agregar/parcelas/<?= $parcela['fichero'] ?>')
                    .then(resp => resp.json())
                    .then(newGeo => {
                        drawnItems.clearLayers();
                        L.geoJSON(newGeo).eachLayer(l => drawnItems.addLayer(l));
                    });
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo modificar.', confirmButtonColor: '#dc3545' }));
    }

    mapa.on('draw:created', function(e) {
        const layer = e.layer;
        drawnItems.clearLayers();
        drawnItems.addLayer(layer);
        actualizarGeojson();
    });

    mapa.on('draw:edited', actualizarGeojson);
    mapa.on('draw:deleted', actualizarGeojson);
});
</script>
<?php endif; ?>
</main>
<?php include '../../componentes/footer.php'; ?>
</body>
