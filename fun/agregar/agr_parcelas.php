<?php
include '../../lib/functiones.php';
session_start();

include '../../componentes/header.php'; ?>
<link rel="stylesheet" href="../../css/style.css">
<?php
if (isset($_SESSION['usuario'])) {
    if (isset($_POST['anhadir'])) {
        $ubicacion = trim($_POST['ubicacion']);
        $geojsonRaw = trim($_POST['geojson']);
        $idUsr = $_SESSION['usuario']['id_usr'];
        $errores = [];

        // Validaciones del formulario
        if ($ubicacion === "") {
            $errores[] = "Debes indicar una ubicación";
        }
        if ($geojsonRaw === "") {
            $errores[] = "Debes dibujar una zona en el mapa";
        }

        if (!empty($errores)) {
            $erroresHtml = "";
            foreach ($errores as $error) {
                $erroresHtml .= "<li>$error</li>";
            }
            // Mostramos un alert con los errores
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Error de validación',
                        html: '<ul class=\"text-start\">".$erroresHtml."</ul>',
                        icon: 'error',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#dc3545'
                    });
                });
            </script>";
        } else {
            try {
                $nombreFichero = preg_replace('/[^A-Za-z0-9_\-]/', '_', $ubicacion) . "-" . time() . ".geojson";
                $directorioRelativo = "../agregar/parcelas/";
                $directorioAbsoluto = __DIR__ . "/../agregar/parcelas/";

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

                function calcularAreaM2($coords) {
                    $radioTierra = 6371000;
                    $lat0 = deg2rad($coords[0][1]);
                    $total = 0.0;

                    for ($i = 0; $i < count($coords) - 1; $i++) {
                        $lon1 = deg2rad($coords[$i][0]);
                        $lat1 = deg2rad($coords[$i][1]);
                        $lon2 = deg2rad($coords[$i + 1][0]);
                        $lat2 = deg2rad($coords[$i + 1][1]);

                        $x1 = $radioTierra * ($lon1) * cos($lat0);
                        $y1 = $radioTierra * ($lat1);
                        $x2 = $radioTierra * ($lon2) * cos($lat0);
                        $y2 = $radioTierra * ($lat2);

                        $total += ($x1 * $y2 - $x2 * $y1);
                    }

                    return abs($total / 2);
                }

                $area_m2 = calcularAreaM2($coordinates);

                $conexion = conectar();
                $stmt = $conexion->prepare("INSERT INTO parcelas (ubicacion, fichero, latitud, longitud, area_m2) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssddd", $ubicacion, $nombreFichero, $latitud, $longitud, $area_m2);
                $stmt->execute();
                $id_parcela = $stmt->insert_id;
                $stmt->close();

                $stmt2 = $conexion->prepare("INSERT INTO parcelas_usuarios (id_usr, id_parcela) VALUES (?, ?)");
                $stmt2->bind_param("ii", $idUsr, $id_parcela);
                $stmt2->execute();
                $stmt2->close();

                $mensajeExito = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Parcela insertada correctamente',
        html: `<ul class="text-start">
            <li><strong>Ubicación:</strong> {$ubicacion}</li>
            <li><strong>Área estimada:</strong> " . number_format($area_m2, 2) . " m²</li>
            <li><strong>Coordenadas:</strong> Lat: " . number_format($latitud, 5) . " | Lon: " . number_format($longitud, 5) . "</li>
            <li><strong>Archivo:</strong> <a href="{$directorioRelativo}{$nombreFichero}" target="_blank">{$nombreFichero}</a></li>
        </ul>`,
        icon: 'success',
        confirmButtonText: 'Continuar',
        confirmButtonColor: '#28a745'
    });
});
</script>
HTML;

echo $mensajeExito;

           echo "
<style>
    html, body {
        height: 100%;
    }
    body {
        display: flex;
        flex-direction: column;
    }
    main {
        flex: 1;
    }
    .same-height {
        height: 500px;
    }
</style>

<main class='container my-5'>
    <div class='row g-4'>
        <!-- Mapa -->
        <div class='col-md-6'>
            <div id='map' class='rounded shadow-sm same-height'></div>
        </div>

        <!-- Tarjeta -->
        <div class='col-md-6'>
            <div class='card shadow-sm border-0 same-height d-flex flex-column justify-content-between' style='background: #f0fdf4;'>
                <div>
                    <div class='card-header bg-success text-white text-center'>
                        🌿 <strong>Resumen de la parcela registrada</strong>
                    </div>
                    <div class='card-body px-4 py-3'>
                        <p class='mb-2'>📍 <strong>Ubicación:</strong> " . htmlspecialchars($ubicacion) . "</p>
                        <p class='mb-2'>🆔 <strong>Nombre:</strong> " . htmlspecialchars($_POST['nombre'] ?? '') . "</p>
                        <p class='mb-2'>🌾 <strong>Tipo de cultivo:</strong> " . htmlspecialchars($_POST['tipo_cultivo'] ?? '') . "</p>
                        <p class='mb-2'>📏 <strong>Área estimada:</strong> " . number_format($area_m2, 2) . " m²</p>
                        <p class='mb-2'>🧭 <strong>Latitud:</strong> " . number_format($latitud, 5) . " &nbsp;&nbsp;&nbsp; 🧭 <strong>Longitud:</strong> " . number_format($longitud, 5) . "</p>
                        <p class='mb-2'>📝 <strong>Observaciones:</strong><br>" . nl2br(htmlspecialchars($_POST['observaciones'] ?? '')) . "</p>
                        <p class='mb-0'>📁 <strong>Archivo GeoJSON:</strong><br><a href='" . $directorioRelativo . $nombreFichero . "' target='_blank'>" . $nombreFichero . "</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

<script>
    let map = L.map('map').setView([$latitud, $longitud], 17);
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri',
        maxZoom: 19
    }).addTo(map);
    let capa = L.geoJSON($geojsonClean, {
        style: {
            color: '#00bcd4',
            weight: 3,
            opacity: 0.8,
            fillColor: '#b2ebf2',
            fillOpacity: 0.4
        }
    }).addTo(map);
    map.fitBounds(capa.getBounds());
</script>
";



                echo "<div class='container text-center mb-5'>
                        <a href='agr_parcelas.php' class='btn  btn-success me-2'>Insertar otra parcela</a>
                        <a href='../../menu/parcelas.php' class='btn btn-outline-success'>Volver al menú</a>
                      </div>";

            } catch (Exception $e) {
                // Mensaje de error con SweetAlert2
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Error al procesar la parcela',
                            text: '" . htmlspecialchars($e->getMessage()) . "',
                            icon: 'error',
                            confirmButtonText: 'Volver',
                            confirmButtonColor: '#dc3545'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'agr_parcelas.php';
                            }
                        });
                    });
                </script>";
            }
        }
    } else {
?>
<div class="container py-5">
    <h2 class="text-center mb-4">Agregar nueva parcela</h2>
    <form action="agr_parcelas.php" method="post" onsubmit="return validarFormulario();">
        <div class="row g-4">
            <div class="col-md-6">
                <label for="ubicacion" class="form-label fw-bold">Ubicación:</label>
                <div class="d-flex">
                    <input type="text" class="form-control me-2" id="ubicacion" name="ubicacion" placeholder="Calle, número, ciudad" onkeydown="if(event.key === 'Enter'){event.preventDefault(); buscarUbicacion();}">
                    <button type="button" onclick="buscarUbicacion()" class="btn btn-success">Buscar</button>
                </div>
                <div class="bg-success-subtle p-3 mt-3 rounded">
                    <strong>Dirección exacta:</strong>
                    <div id="direccionExacta" class="mb-2"></div>
                    <strong>Coordenadas:</strong>
                    <div id="coordenadas"></div>
                </div>
                <div id="map" class="mt-4 rounded" style="height: 400px;"></div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="nombre" class="form-label fw-bold">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="tipo_cultivo" class="form-label fw-bold">Tipo de cultivo:</label>
                    <input type="text" id="tipo_cultivo" name="tipo_cultivo" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="area_m2" class="form-label fw-bold">Área (m²):</label>
                    <input type="number" id="area_m2" name="area_m2" step="0.01" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="observaciones" class="form-label fw-bold">Observaciones:</label>
                    <textarea id="observaciones" name="observaciones" rows="3" class="form-control"></textarea>
                </div>
                <input type="hidden" name="geojson" id="geojson">
                <div class="text-center mt-4 d-flex justify-content-center gap-3">
                    <a href="../../menu/parcelas.php"" class="btn btn-danger px-4">Volver</a>
                <input type="submit" name="anhadir" value="Insertar parcela" class="btn btn-success">
                </div>
            </div>
        </div>
    </form>
</div>
<script>
    
    
    
    
// Agregar SweetAlert2 al head
document.addEventListener('DOMContentLoaded', function() {
    if (!document.querySelector('script[src*="sweetalert2"]')) {
        const sweetalertScript = document.createElement('script');
        sweetalertScript.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        document.head.appendChild(sweetalertScript);
    }
});

let map = L.map('map').setView([40.4168, -3.7038], 6);
L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: 'Tiles &copy; Esri',
    maxZoom: 19
}).addTo(map);

let drawnItems = new L.FeatureGroup();
map.addLayer(drawnItems);
let ultimaCapa = null;

let drawControl = new L.Control.Draw({
    draw: {
        polygon: { shapeOptions: { color: '#45f3ff', weight: 3, fillOpacity: 0.3 } },
        rectangle: { shapeOptions: { color: '#45f3ff', weight: 3, fillOpacity: 0.3 } },
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
    calcularArea(ultimaCapa);
});

function calcularArea(capa) {
    try {
        const turfArea = turf.area(capa.toGeoJSON());
        const m2 = turfArea.toFixed(2);
        document.getElementById("area_m2").value = m2;
    } catch (err) {
        console.error("Error al calcular área:", err);
    }
}

map.on('moveend', function () {
    let center = map.getCenter();
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${center.lat}&lon=${center.lng}&zoom=18&addressdetails=1`)
        .then(res => res.json())
        .then(data => {
            if (data && data.display_name) {
                document.getElementById('ubicacion').value = data.display_name;
                document.getElementById('direccionExacta').innerText = data.display_name;
                document.getElementById('coordenadas').innerText = `Lat: ${center.lat.toFixed(5)} | Lon: ${center.lng.toFixed(5)}`;
            }
        });
});

function buscarUbicacion() {
    let ciudad = document.getElementById('ubicacion').value;
    if (!ciudad) {
        Swal.fire({
            title: 'Campo vacío',
            text: 'Introduce una ubicación para buscar',
            icon: 'warning',
            confirmButtonColor: '#ffc107'
        });
        return;
    }
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(ciudad)}&addressdetails=1`)
        .then(res => res.json())
        .then(data => {
            if (data.length > 0) {
                let lat = parseFloat(data[0].lat);
                let lon = parseFloat(data[0].lon);
                let displayName = data[0].display_name;
                map.setView([lat, lon], 17);
                L.marker([lat, lon]).addTo(map);
                document.getElementById('coordenadas').innerText = `Lat: ${lat.toFixed(5)} | Lon: ${lon.toFixed(5)}`;
                document.getElementById('direccionExacta').innerText = displayName;
            } else {
                Swal.fire({
                    title: 'Error de ubicación',
                    text: 'Ubicación no encontrada. Intenta con otra dirección.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                title: 'Error de conexión',
                text: 'No se pudo conectar al servicio de geocodificación.',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        });
}

function validarFormulario() {
    let ubicacion = document.getElementById('ubicacion').value.trim();
    let errores = [];
    
    if (!ubicacion) {
        errores.push("Debes indicar una ubicación");
    }
    
    if (!ultimaCapa) {
        errores.push("Debes dibujar una zona en el mapa");
    }
    
    if (errores.length > 0) {
        let mensaje = '<ul class="text-start">';
        errores.forEach(error => {
            mensaje += `<li>${error}</li>`;
        });
        mensaje += '</ul>';
        
        Swal.fire({
            title: 'Error de validación',
            html: mensaje,
            icon: 'error',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#dc3545'
        });
        
        return false;
    }
    
    try {
        const feature = ultimaCapa.toGeoJSON();
        const collection = { type: "FeatureCollection", features: [feature] };
        document.getElementById("geojson").value = JSON.stringify(collection);
        
        // Mostrar alerta de procesando
        Swal.fire({
            title: 'Procesando',
            text: 'Guardando la información de la parcela...',
            icon: 'info',
            showConfirmButton: false,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        return true;
    } catch (error) {
        console.error("Error al generar GeoJSON:", error);
        Swal.fire({
            title: 'Error técnico',
            text: 'Error al procesar la parcela. Detalles: ' + error.message,
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
}
</script>

<?php
    }
} else {
    // Mensaje de acceso denegado con SweetAlert2
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            if (!document.querySelector('script[src*=\"sweetalert2\"]')) {
                const sweetalertScript = document.createElement('script');
                sweetalertScript.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                document.head.appendChild(sweetalertScript);
                
                sweetalertScript.onload = function() {
                    mostrarAlertaAccesoDenegado();
                };
            } else {
                mostrarAlertaAccesoDenegado();
            }
            
            function mostrarAlertaAccesoDenegado() {
                Swal.fire({
                    title: 'Acceso denegado',
                    text: 'No tienes permisos para acceder a esta página. Por favor, inicia sesión.',
                    icon: 'error',
                    confirmButtonText: 'Volver',
                    confirmButtonColor: '#dc3545'
                }).then((result) => {
                    if (result.isConfirmed) {
                        history.back();
                    }
                });
            }
        });
    </script>";
    session_destroy();
}
?>
<?php include '../../componentes/footer.php'; ?>