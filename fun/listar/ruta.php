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
    <link rel="stylesheet" type="text/css" href="/Proyecto_Drones_v3-CSS/css/ruta.css">
    <script src="https://unpkg.com/leaflet@1.0.2/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.0.2/dist/leaflet.css" />
</head>
<body>
<?php
if(isset($_SESSION['usuario'])) {
    if (isset($_REQUEST['ruta'])) {
        $parcela = $_REQUEST['parcela'];
        $vacio = $_REQUEST['vacio'];
        
        if($vacio == 0) {
            echo "<div class='mensaje-error'>";
            echo "<h3>No has seleccionado ninguna ruta</h3>";
            echo "</div>";
        } else {
            $entrar = true;
            // Verificar que los arrays de latitud y longitud existen
            if (isset($_REQUEST['latitud']) && isset($_REQUEST['longitud']) && 
                is_array($_REQUEST['latitud']) && is_array($_REQUEST['longitud'])) {
                
                $latitud = $_REQUEST['latitud'];
                $longitud = $_REQUEST['longitud'];
                $nfilas = count($latitud);
                
                // Comprobar que hay el mismo número de latitudes y longitudes
                if (count($longitud) != $nfilas) {
                    echo "<div class='mensaje-error'>";
                    echo "<h3>Error: El número de latitudes y longitudes no coincide</h3>";
                    echo "</div>";
                } else {
                    echo "<div class='titulo-principal'>";
                    echo "<h2>Ruta guardada correctamente</h2>";
                    echo "</div>";
                    
                    echo "<div class='tabla-contenedor'>";
                    echo "<table>";
                    echo "<tr>";
                    echo "<th>Orden</th>";
                    echo "<th>Latitud</th>";
                    echo "<th>Longitud</th>";
                    echo "</tr>";
                    
                    // Verificar si ya existe una ruta para esta parcela
                    $instruccion = "SELECT * FROM ruta WHERE id_parcela = $parcela";
                    $consulta = mysqli_query(conectar(), $instruccion)
                        or die ("Fallo en la consulta");
                    
                    if (mysqli_num_rows($consulta) > 0) {
                        $entrar = false;
                    }
                    
                    // Guardar los datos para mostrar en el mapa
                    $latitudes = array();
                    $longitudes = array();
                    
                    if ($entrar) {
                        // No existe ruta, insertar nueva
                        for ($i = 0; $i < $nfilas; $i++) {
                            // Validar que los valores son numéricos
                            $lat = floatval($latitud[$i]);
                            $lon = floatval($longitud[$i]);
                            
                            $instruccion1 = "INSERT INTO ruta (latitud, longitud, id_parcela) VALUES ($lat, $lon, $parcela)";
                            $consulta1 = mysqli_query(conectar(), $instruccion1)
                                or die ("Fallo en la consulta de inserción");
                            
                            echo "<tr>";
                            echo "<td>" . ($i+1) . "</td>";
                            echo "<td>" . $lat . "</td>";
                            echo "<td>" . $lon . "</td>";
                            echo "</tr>";
                            
                            // Guardar coordenadas para el mapa
                            $latitudes[] = $lat;
                            $longitudes[] = $lon;
                        }
                    } else {
                        // Ya existe una ruta, actualizarla
                        echo "<div class='info-actualizacion'>";
                        echo "<h3>Ya existe una ruta, pero se ha actualizado</h3>";
                        echo "</div>";
                        
                        // Eliminar ruta existente
                        $instruccion = "DELETE FROM ruta WHERE id_parcela = $parcela";
                        $consulta = mysqli_query(conectar(), $instruccion)
                            or die ("Fallo en la eliminación");
                        
                        // Insertar nueva ruta
                        for ($i = 0; $i < $nfilas; $i++) {
                            // Validar que los valores son numéricos
                            $lat = floatval($latitud[$i]);
                            $lon = floatval($longitud[$i]);
                            
                            $instruccion1 = "INSERT INTO ruta (latitud, longitud, id_parcela) VALUES ($lat, $lon, $parcela)";
                            $consulta1 = mysqli_query(conectar(), $instruccion1)
                                or die ("Fallo en la consulta de inserción");
                            
                            echo "<tr>";
                            echo "<td>" . ($i+1) . "</td>";
                            echo "<td>" . $lat . "</td>";
                            echo "<td>" . $lon . "</td>";
                            echo "</tr>";
                            
                            // Guardar coordenadas para el mapa
                            $latitudes[] = $lat;
                            $longitudes[] = $lon;
                        }
                    }
                    
                    echo "</table>";
                    echo "</div>";
                    
                    // Recuperar información de la parcela para mostrarla
                    $instruccionParcela = "SELECT ubicacion, fichero FROM parcelas WHERE id_parcela = $parcela";
                    $consultaParcela = mysqli_query(conectar(), $instruccionParcela)
                        or die ("Fallo al recuperar información de la parcela");
                    $resultadoParcela = mysqli_fetch_assoc($consultaParcela);
                    
                    echo "<div class='info-ruta'>";
                    if ($resultadoParcela) {
                        echo "<p>Parcela: <strong>" . $resultadoParcela['ubicacion'] . "</strong> (ID: $parcela)</p>";
                        $fichero = '../agregar/parcelas/' . $resultadoParcela['fichero'];
                        $geojson = 'null';
                        $geojsonLeido = false;
                        
                        if (file_exists($fichero)) {
                            $contenido = file_get_contents($fichero);
                            if ($contenido !== false) {
                                // Verificar que es un JSON válido
                                json_decode($contenido);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    $geojson = $contenido;
                                    $geojsonLeido = true;
                                }
                            }
                        }
                        
                        if (!$geojsonLeido) {
                            echo "<div class='mensaje-error'>";
                            echo "<p>⚠ Error: No se pudo cargar el archivo GeoJSON de la parcela.</p>";
                            echo "</div>";
                        }
                    }
                    
                    echo "<p>Se han guardado <strong>$nfilas puntos</strong> para la ruta del dron.</p>";
                    echo "</div>";
                    
                    // Mostrar el mapa con la parcela y la ruta
                    echo "<div class='titulo-secundario'>";
                    echo "<h3>Visualización de la ruta</h3>";
                    echo "</div>";
                    
                    echo "<div class='mapa-contenedor'>";
                    echo "<div id='map'></div>";
                    echo "</div>";
                    
                    // Convertir arrays PHP a JavaScript
                    $latitudesJS = json_encode($latitudes);
                    $longitudesJS = json_encode($longitudes);
                    
                    echo "<script>
                        // Inicializar variables
                        let originalBounds = null;
                        
                        // Cargar mapa
                        let map = L.map('map').setView([40.24, -3.7038], 6);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '© OpenStreetMap contributors'
                        }).addTo(map);
                        
                        // Asegurar que el mapa se renderiza correctamente
                        setTimeout(function() {
                            map.invalidateSize();
                            
                            try {
                                // Cargar GeoJSON de la parcela
                                let geojsonData = $geojson;
                                
                                if (geojsonData) {
                                    var geojsonLayer = L.geoJSON(geojsonData, {
                                        style: {
                                            color: '#45f3ff',
                                            weight: 3,
                                            opacity: 0.7,
                                            fillColor: '#45f3ff',
                                            fillOpacity: 0.3
                                        }
                                    }).addTo(map);
                                    
                                    // Ajustar la vista a la parcela
                                    originalBounds = geojsonLayer.getBounds();
                                    map.fitBounds(originalBounds);
                                    
                                    // Obtener coordenadas de la ruta
                                    var latitudes = $latitudesJS;
                                    var longitudes = $longitudesJS;
                                    
                                    // Crear línea para la ruta
                                    var rutaPoints = [];
                                    for (var i = 0; i < latitudes.length; i++) {
                                        rutaPoints.push([latitudes[i], longitudes[i]]);
                                    }
                                    
                                    var rutaLinea = L.polyline(rutaPoints, {
                                        color: '#4caf50',
                                        weight: 4,
                                        opacity: 0.8
                                    }).addTo(map);
                                    
                                    // Añadir marcadores numerados
                                    for (var i = 0; i < rutaPoints.length; i++) {
                                        var marcador = L.marker(rutaPoints[i]).addTo(map);
                                        marcador.bindTooltip((i+1).toString(), {
                                            permanent: true,
                                            direction: 'center',
                                            className: 'marker-number'
                                        });
                                    }
                                    
                                    // Agregar botón de restablecer vista
                                    var resetViewControl = L.control({position: 'topleft'});
                                    resetViewControl.onAdd = function(map) {
                                        var div = L.DomUtil.create('div', 'leaflet-bar leaflet-control reset-view');
                                        div.innerHTML = '<a href=\"#\" title=\"Restablecer vista\" style=\"font-weight: bold; width: 30px; height: 30px; line-height: 30px; text-decoration: none; text-align: center; display: block; background: white; color: #333;\">🏠</a>';
                                        div.onclick = function() {
                                            if (originalBounds) {
                                                map.fitBounds(originalBounds);
                                            }
                                            return false;
                                        };
                                        return div;
                                    };
                                    resetViewControl.addTo(map);
                                } else {
                                    console.error('GeoJSON no disponible');
                                    // Si no hay GeoJSON, intentar ajustar a los puntos de la ruta
                                    if (rutaLinea && rutaLinea.getBounds) {
                                        map.fitBounds(rutaLinea.getBounds());
                                    }
                                }
                            } catch (error) {
                                console.error('Error al cargar el mapa:', error);
                            }
                        }, 100);
                    </script>";
                }
            } else {
                echo "<div class='mensaje-error'>";
                echo "<h3>Error: No se han recibido las coordenadas correctamente</h3>";
                echo "</div>";
            }
        }
    }
?>

    <!-- Botones de navegación -->
    <div class="botones-navegacion">
        <form action="ver_parcelas.php" method="post">
            <input type="submit" value="Seleccionar otra parcela">
        </form>
        
        <form action="../parcelas.php" method="post">
            <input type="submit" name="volverReg" value="Volver al menú principal">
        </form>
    </div>

<?php
} else {
    echo "<div class='mensaje-error'>";
    echo "<h2>Acceso denegado</h2>";
    echo "<p>No tienes permisos para acceder a esta página. Por favor, inicia sesión.</p>";
    echo "</div>";
    
    echo "<div class='volver-form'>";
    echo "<a href='javascript:history.back()'><button>Volver</button></a>";
    echo "</div>";
    
    session_destroy();
}
?>
</body>
</html>