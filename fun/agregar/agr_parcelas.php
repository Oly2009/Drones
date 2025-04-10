<?php
include '../../lib/functiones.php';
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar parcela</title>
    <link rel="stylesheet" href="../../css/agregarParcelas.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.0.2/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"/>
    <script src="https://unpkg.com/leaflet@1.0.2/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
</head>
<body>
<?php
if (isset($_SESSION['usuario'])) {
    if (isset($_POST['anhadir'])) {
        $ubicacion = trim($_POST['ubicacion']);
        $geojsonRaw = trim($_POST['geojson']);
        $idUsr = $_SESSION['usuario']['id_usr'];
        $errores = "";

        if ($ubicacion === "") {
            $errores .= "<li>Debes indicar una ubicación</li>";
        }
        if ($geojsonRaw === "") {
            $errores .= "<li>Debes dibujar una zona en el mapa</li>";
        }

        if ($errores !== "") {
            echo "<div class='mensaje-error'><h3>No se pudo insertar:</h3><ul>$errores</ul><a href='agr_parcelas.php' class='boton-volver'>Volver</a></div>";
        } else {
            try {
                $sqlMaxId = "SELECT MAX(id_parcela) as max_id FROM parcelas";
                $resultadoMaxId = mysqli_query(conectar(), $sqlMaxId);
                $maxId = mysqli_fetch_assoc($resultadoMaxId)['max_id'];
                $nextId = ($maxId !== null) ? $maxId + 1 : 1;

                $nombreFichero = preg_replace('/[^A-Za-z0-9_\-]/', '_', $ubicacion) . "-" . $nextId . ".geojson";
                $directorioRelativo = "parcelas/";
                $directorioAbsoluto = __DIR__ . "/parcelas/";

                if (!file_exists($directorioAbsoluto)) {
                    mkdir($directorioAbsoluto, 0755, true);
                }

                $geojsonDecoded = json_decode($geojsonRaw, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("JSON inválido: " . json_last_error_msg());
                }

                if (!isset($geojsonDecoded['features'][0]['geometry']['coordinates'][0])) {
                    throw new Exception("Estructura GeoJSON incorrecta");
                }

                $geojsonClean = json_encode($geojsonDecoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                file_put_contents($directorioAbsoluto . $nombreFichero, $geojsonClean);

                $coordinates = $geojsonDecoded['features'][0]['geometry']['coordinates'][0];
                $sumLat = $sumLon = 0;
                $count = count($coordinates);

                foreach ($coordinates as $coord) {
                    $sumLon += $coord[0];
                    $sumLat += $coord[1];
                }

                $latitud = $sumLat / $count;
                $longitud = $sumLon / $count;

                $insert = "INSERT INTO parcelas (ubicacion, fichero, latitud, longitud) 
                           VALUES ('$ubicacion', '$nombreFichero', $latitud, $longitud)";
                mysqli_query(conectar(), $insert);

                $id_parcela = mysqli_insert_id(conectar());
                mysqli_query(conectar(), "INSERT INTO parcelas_usuarios (id_usr, id_parcela) VALUES ($idUsr, $id_parcela)");

                echo "<div class='mensaje-exito'><h2>Parcela insertada correctamente</h2><ul><li><strong>Ubicación:</strong> $ubicacion</li><li><strong>Coordenadas:</strong> Lat: " . number_format($latitud, 5) . " | Lon: " . number_format($longitud, 5) . "</li><li><strong>Archivo:</strong> <a href='$directorioRelativo$nombreFichero' target='_blank'>$nombreFichero</a></li></ul></div>";

                echo "<div class='mapa-contenedor'><div id='map'></div></div>";
                echo "<script>
                    let map = L.map('map').setView([$latitud, $longitud], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
                    let geojson = $geojsonClean;
                    let capa = L.geoJSON(geojson, {
                        style: { color: '#45f3ff', weight: 3, opacity: 0.7, fillColor: '#45f3ff', fillOpacity: 0.3 }
                    }).addTo(map);
                    map.fitBounds(capa.getBounds());
                </script>";
                echo "<div class='botones-navegacion'><a href='agr_parcelas.php' class='boton'>Insertar otra parcela</a><a href='../parcelas.php' class='boton'>Volver al menú</a></div>";

            } catch (Exception $e) {
                echo "<div class='mensaje-error'><h3>Error al procesar la parcela:</h3><p>" . htmlspecialchars($e->getMessage()) . "</p><a href='agr_parcelas.php' class='boton-volver'>Volver</a></div>";
            }
        }
    } else {
?>
<div class="titulo-principal">
    <h2>Agregar nueva parcela</h2>
</div>
<div class="formulario-contenedor">
    <form action="agr_parcelas.php" method="post" onsubmit="return guardarGeoJSON();">
        <div class="campo-form">
            <label for="ubicacion">Ubicación:</label>
            <div class="campo-busqueda">
                <input type="text" id="ubicacion" name="ubicacion" placeholder="Ciudad">
                <button type="button" onclick="buscarUbicacion()" class="boton-buscar">Buscar</button>
            </div>
        </div>
        <div class="campo-form">
            <label>Coordenadas:</label>
            <span id="coordenadas" class="texto-coordenadas"></span>
        </div>
        <input type="hidden" name="geojson" id="geojson">
        <div class="botones-form">
            <input type="submit" name="anhadir" value="Insertar parcela" class="boton-principal">
        </div>
    </form>
    <form action="../parcelas.php" method="post">
        <input type="submit" name="volverReg" value="Volver" class="boton-secundario">
    </form>
</div>
<div class="mapa-contenedor"><div id="map"></div></div>

<script>
let map = L.map('map').setView([40.4168, -3.7038], 6);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
let drawnItems = new L.FeatureGroup();
map.addLayer(drawnItems);
let ultimaCapa = null;

let drawControl = new L.Control.Draw({
    draw: {
        polygon: { shapeOptions: { color: '#45f3ff', weight: 3, opacity: 0.7, fillColor: '#45f3ff', fillOpacity: 0.3 } },
        rectangle: { shapeOptions: { color: '#45f3ff', weight: 3, opacity: 0.7, fillColor: '#45f3ff', fillOpacity: 0.3 } },
        circle: false, polyline: false, marker: false, circlemarker: false
    },
    edit: { featureGroup: drawnItems, remove: true }
});
map.addControl(drawControl);

map.on('draw:created', function (e) {
    drawnItems.clearLayers();
    drawnItems.addLayer(e.layer);
    ultimaCapa = e.layer;
    let bounds = e.layer.getBounds();
    let center = bounds.getCenter();
    document.getElementById('coordenadas').innerText = `Lat: ${center.lat.toFixed(5)} | Lon: ${center.lng.toFixed(5)}`;
});

function buscarUbicacion() {
    let ciudad = document.getElementById('ubicacion').value;
    if (!ciudad) return alert("Introduce una ubicación para buscar");
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(ciudad)}`)
        .then(res => res.json())
        .then(data => {
            if (data.length > 0) {
                let lat = parseFloat(data[0].lat);
                let lon = parseFloat(data[0].lon);
                map.setView([lat, lon], 15);
                document.getElementById('coordenadas').innerText = `Lat: ${lat.toFixed(5)} | Lon: ${lon.toFixed(5)}`;
            } else {
                alert("Ubicación no encontrada. Intenta con otra ciudad.");
            }
        })
        .catch(err => console.error("Error al buscar ubicación:", err));
}

map.on('moveend', function () {
    let center = map.getCenter();
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${center.lat}&lon=${center.lng}&zoom=10`)
        .then(res => res.json())
        .then(data => {
            let ubicacion = '';
            if (data.address) {
                ubicacion = data.address.city || data.address.town || data.address.village || data.address.county || data.address.state || '';
            }
            if (ubicacion) {
                document.getElementById('ubicacion').value = ubicacion;
                document.getElementById('coordenadas').innerText = `Lat: ${center.lat.toFixed(5)} | Lon: ${center.lng.toFixed(5)}`;
            }
        })
        .catch(err => console.error("Error al obtener ubicación:", err));
});

function guardarGeoJSON() {
    let ubicacion = document.getElementById('ubicacion').value.trim();
    if (!ubicacion) return alert("Debes indicar una ubicación."), false;
    if (!ultimaCapa) return alert("Debes dibujar una zona en el mapa."), false;
    try {
        const feature = ultimaCapa.toGeoJSON();
        const collection = { type: "FeatureCollection", features: [feature] };
        document.getElementById("geojson").value = JSON.stringify(collection);
        return true;
    } catch (error) {
        console.error("Error al generar GeoJSON:", error);
        alert("Error al procesar la parcela. Intenta dibujar nuevamente.");
        return false;
    }
}
</script>
<?php
    }
} else {
    echo "<div class='mensaje-error'><h2>Acceso denegado</h2><p>No tienes permisos para acceder a esta página. Por favor, inicia sesión.</p><a href='javascript:history.back()' class='boton-volver'>Volver</a></div>";
    session_destroy();
}
?>
</body>
</html>
