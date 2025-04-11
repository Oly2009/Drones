<?php
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    echo "⛔ Acceso denegado";
    exit;
}

$con = conectar();
$mensaje = "";
$tipo = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_parcela'], $_POST['geojson'])) {
    $id = intval($_POST['id_parcela']);
    $geojsonRaw = $_POST['geojson'];
    $decoded = json_decode($geojsonRaw, true);

    if ($decoded && isset($decoded['features'][0]['geometry']['coordinates'][0])) {
        $coords = $decoded['features'][0]['geometry']['coordinates'][0];
        $sumLat = $sumLon = 0;
        foreach ($coords as $coord) {
            $sumLon += $coord[0];
            $sumLat += $coord[1];
        }

        $lat = $sumLat / count($coords);
        $lon = $sumLon / count($coords);

        $query = mysqli_query($con, "SELECT fichero FROM parcelas WHERE id_parcela = $id");
        $row = mysqli_fetch_assoc($query);
        $fichero = $row['fichero'];
        $ruta = realpath(__DIR__ . "/../agregar/parcelas/" . $fichero);

        if ($ruta && file_exists($ruta)) {
            file_put_contents($ruta, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            mysqli_query($con, "UPDATE parcelas SET latitud = $lat, longitud = $lon WHERE id_parcela = $id");
            $mensaje = "✅ Parcela modificada correctamente.";
            $tipo = "success";
        } else {
            $mensaje = "❌ Error: No se encontró el archivo GeoJSON.";
            $tipo = "error";
        }
    } else {
        $mensaje = "❌ Error: GeoJSON inválido o no se ha dibujado nada.";
        $tipo = "error";
    }
}

$parcelas = mysqli_query($con, "SELECT * FROM parcelas");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>✏️ Modificar parcelas - AgroSky</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../css/modificarParcelas.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
</head>
<body>

<a href="../../fun/ayuda/instrucciones.php?origen=mod_parcelas.php" class="icono-ayuda" title="Ayuda">
    <i class="bi bi-question-circle-fill"></i>
</a>

<h1>✏️ Modificar parcelas</h1>

<div class="formulario-cuenta">
    <form method="POST" id="formulario" onsubmit="return validarAntesDeGuardar();">
        <label for="id_parcela">Selecciona una parcela:</label>
        <select name="id_parcela" id="id_parcela" onchange="mostrarParcela()">
            <option value="">-- Elegir --</option>
            <?php while ($row = mysqli_fetch_assoc($parcelas)): ?>
                <option value="<?= $row['id_parcela'] ?>" data-fichero="<?= htmlspecialchars($row['fichero']) ?>">
                    <?= htmlspecialchars($row['ubicacion']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <div id="mapa"></div>
        <textarea id="geojson" name="geojson" hidden></textarea>

        <div class="acciones">
            <button type="submit" class="btn-accion">✅ Guardar cambios</button>
            <a href="../../menu/parcelas.php" class="btn">⬅ Volver</a>
        </div>
    </form>
</div>

<!-- Modal -->
<div id="modal" class="modal">
    <div id="modal-contenido" class="modal-contenido">
        <span class="cerrar" onclick="document.getElementById('modal').style.display='none'">&times;</span>
        <p id="mensaje"></p>
    </div>
</div>

<script>
let mapa = L.map('mapa').setView([40.0, -3.7], 6);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapa);

let drawnItems = new L.FeatureGroup().addTo(mapa);
mapa.addControl(new L.Control.Draw({
    edit: { featureGroup: drawnItems, remove: true },
    draw: {
        polygon: { allowIntersection: false },
        rectangle: true,
        polyline: false,
        marker: false,
        circle: false,
        circlemarker: false
    }
}));

mapa.on(L.Draw.Event.CREATED, function (e) {
    drawnItems.clearLayers();
    drawnItems.addLayer(e.layer);
    actualizarGeoJSON();
});
mapa.on(L.Draw.Event.EDITED, actualizarGeoJSON);
mapa.on(L.Draw.Event.DELETED, actualizarGeoJSON);

function actualizarGeoJSON() {
    const datos = drawnItems.toGeoJSON();
    document.getElementById("geojson").value = JSON.stringify(datos);
}

function mostrarParcela() {
    drawnItems.clearLayers();
    document.getElementById("geojson").value = "";
    const select = document.getElementById("id_parcela");
    const option = select.options[select.selectedIndex];
    const fichero = option.getAttribute("data-fichero");
    if (!fichero) return;

    fetch(`../agregar/parcelas/${fichero}?t=${Date.now()}`)
        .then(res => res.json())
        .then(data => {
            let capa = L.geoJSON(data, {
                style: { color: '#45f3ff', weight: 3, fillOpacity: 0.3 }
            });
            drawnItems.addLayer(capa);
            mapa.fitBounds(capa.getBounds());
            actualizarGeoJSON();
        })
        .catch(err => {
            console.error(err);
            mostrarModal("❌ Error cargando el GeoJSON", "error");
        });
}

function mostrarModal(mensaje, tipo) {
    const modal = document.getElementById("modal");
    const contenido = document.getElementById("modal-contenido");
    const texto = document.getElementById("mensaje");
    contenido.className = "modal-contenido modal-" + tipo;
    texto.textContent = mensaje;
    modal.style.display = "block";
}

function validarAntesDeGuardar() {
    const geojson = document.getElementById("geojson").value;
    if (!geojson || geojson.trim() === "" || geojson === "{}") {
        mostrarModal("⚠️ Debes dibujar una nueva parcela antes de guardar", "error");
        return false;
    }
    return true;
}

<?php if ($mensaje): ?>
    mostrarModal("<?= $mensaje ?>", "<?= $tipo ?>");
<?php endif; ?>
</script>

</body>
</html>
