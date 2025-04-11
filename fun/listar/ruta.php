<?php
include '../../lib/functiones.php';
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar ruta</title>
    <link rel="stylesheet" href="/Proyecto_Drones_v3-CSS/css/ruta.css">
    <script src="https://unpkg.com/leaflet@1.0.2/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.0.2/dist/leaflet.css" />
</head>
<body>
<?php
if (isset($_SESSION['usuario'])) {
    if (isset($_REQUEST['ruta'])) {
        $parcela = $_REQUEST['parcela'];
        $vacio = $_REQUEST['vacio'];

        if ($vacio == 0) {
            echo "<div class='mensaje-error'><h3>No has seleccionado ninguna ruta</h3></div>";
        } else {
            $entrar = true;
            if (isset($_REQUEST['latitud'], $_REQUEST['longitud']) && is_array($_REQUEST['latitud']) && is_array($_REQUEST['longitud'])) {
                $latitud = $_REQUEST['latitud'];
                $longitud = $_REQUEST['longitud'];
                $nfilas = count($latitud);

                if (count($longitud) != $nfilas) {
                    echo "<div class='mensaje-error'><h3>Error: El número de latitudes y longitudes no coincide</h3></div>";
                } else {
                    $rutaExistente = false;
                    $checkRuta = mysqli_query(conectar(), "SELECT * FROM ruta WHERE id_parcela = $parcela");
                    if (mysqli_num_rows($checkRuta) > 0) {
                        $rutaExistente = true;
                        echo "<div class='mensaje-actualizado'><h3>Ya existe una ruta, pero se ha actualizado</h3></div>";
                        mysqli_query(conectar(), "DELETE FROM ruta WHERE id_parcela = $parcela") or die("Fallo al eliminar ruta");
                    }

                    $latitudes = [];
                    $longitudes = [];

                    for ($i = 0; $i < $nfilas; $i++) {
                        $lat = floatval($latitud[$i]);
                        $lon = floatval($longitud[$i]);
                        mysqli_query(conectar(), "INSERT INTO ruta (latitud, longitud, id_parcela) VALUES ($lat, $lon, $parcela)") or die("Fallo insertando ruta");
                        $latitudes[] = $lat;
                        $longitudes[] = $lon;
                    }

                    $datosParcela = mysqli_fetch_assoc(mysqli_query(conectar(), "SELECT ubicacion, fichero FROM parcelas WHERE id_parcela = $parcela"));
                    $geojson = 'null';
                    $archivo = '../agregar/parcelas/' . $datosParcela['fichero'];
                    if (file_exists($archivo)) {
                        $contenido = file_get_contents($archivo);
                        json_decode($contenido);
                        if (json_last_error() === JSON_ERROR_NONE) $geojson = $contenido;
                    }

                    echo "<div class='contenedor-flex'>";
                    // Bloque Izquierdo
                    echo "<div class='bloque'>";
                    echo "<h2 class='titulo'>Ruta guardada correctamente</h2>";
                    echo "<table class='tabla'>";
                    echo "<tr><th>Orden</th><th>Latitud</th><th>Longitud</th></tr>";
                    for ($i = 0; $i < $nfilas; $i++) {
                        echo "<tr><td>" . ($i+1) . "</td><td>{$latitudes[$i]}</td><td>{$longitudes[$i]}</td></tr>";
                    }
                    echo "</table>";
                    echo "<p><strong>Parcela:</strong> {$datosParcela['ubicacion']} (ID: $parcela)</p>";
                    echo "<p>Se han guardado <strong>$nfilas puntos</strong> para la ruta del dron.</p>";
                    echo "</div>";

                    // Bloque Derecho
                    echo "<div class='bloque'>";
                    echo "<h3 class='subtitulo'>Visualización de la ruta</h3>";
                    echo "<div id='map'></div>";
                    echo "</div>"; // Fin del segundo bloque
echo "</div>"; // Fin del contenedor-flex

// Botones centrados abajo del todo
echo "<div class='botones-globales'>";
echo "<form action='ver_parcelas.php' method='post'><input type='submit' value='Seleccionar otra parcela'></form>";
echo "<form action='../../menu/parcelas.php' method='post'><input type='submit' value='Volver al menú principal'></form>";
echo "</div>";


                    // Script del mapa
                    $latJS = json_encode($latitudes);
                    $lonJS = json_encode($longitudes);
                    echo "<script>
                        let map = L.map('map').setView([40.24, -3.7038], 6);
                        let originalBounds = null;
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '© OpenStreetMap contributors'
                        }).addTo(map);

                        setTimeout(() => {
                            map.invalidateSize();
                            let geojsonData = $geojson;
                            if (geojsonData) {
                                let layer = L.geoJSON(geojsonData, {
                                    style: { color: '#45f3ff', weight: 3, opacity: 0.7, fillColor: '#45f3ff', fillOpacity: 0.3 }
                                }).addTo(map);
                                originalBounds = layer.getBounds();
                                map.fitBounds(originalBounds);
                            }

                            let lat = $latJS, lon = $lonJS, points = lat.map((l, i) => [l, lon[i]]);
                            let line = L.polyline(points, { color: '#4caf50', weight: 4, opacity: 0.8 }).addTo(map);
                            points.forEach((pt, i) => {
                                L.marker(pt).addTo(map).bindTooltip((i+1).toString(), {
                                    permanent: true, direction: 'center', className: 'marker-number'
                                });
                            });

                            let resetBtn = L.control({position: 'topleft'});
                            resetBtn.onAdd = function(map) {
                                let div = L.DomUtil.create('div', 'leaflet-bar leaflet-control reset-view');
                                div.innerHTML = '<a href=\"#\" title=\"Restablecer vista\">🏠</a>';
                                div.onclick = () => { if (originalBounds) map.fitBounds(originalBounds); return false; };
                                return div;
                            };
                            resetBtn.addTo(map);
                        }, 100);
                    </script>";
                }
            } else {
                echo "<div class='mensaje-error'><h3>Error: No se recibieron coordenadas válidas</h3></div>";
            }
        }
    }
} else {
    echo "<div class='mensaje-error'><h2>Acceso denegado</h2><p>Inicia sesión para continuar</p></div>";
    echo "<div class='volver-form'><a href='javascript:history.back()'><button>Volver</button></a></div>";
    session_destroy();
}
?>
</body>
</html>
