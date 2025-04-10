<?php
include '../../lib/functiones.php';
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar parcelas</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.0.2/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.0.2/dist/leaflet.js"></script>
    <link rel="stylesheet" type="text/css" href="/Proyecto_Drones_v3-CSS/css/verParcelas.css">
</head>
<body>

<?php
if (isset($_SESSION['usuario'])) {
    $idUsr = $_SESSION['usuario']['id_usr'];
    $conexion = conectar();

    // Verificamos si es admin
    $esAdmin = false;
    $consultaRol = mysqli_query($conexion, "SELECT id_rol FROM usuarios_roles WHERE id_usr = '$idUsr'");
    while ($rol = mysqli_fetch_assoc($consultaRol)) {
        if ($rol['id_rol'] == 1) {
            $esAdmin = true;
            break;
        }
    }

    if (isset($_REQUEST['ver'])) {
        $parcela = intval($_REQUEST['parcela']);

        // Validación: si no es admin, comprobar si la parcela pertenece al usuario
        if (!$esAdmin) {
            $verificacion = "
                SELECT COUNT(*) as total
                FROM parcelas_usuarios
                WHERE id_usr = '$idUsr' AND id_parcela = '$parcela'
            ";
            $verificarConsulta = mysqli_query($conexion, $verificacion);
            $puedeVer = mysqli_fetch_assoc($verificarConsulta)['total'] > 0;

            if (!$puedeVer) {
                echo "<div class='mensaje-error'><p>No tienes permisos para ver esta parcela.</p></div>";
                exit;
            }
        }

        $instruccion = "SELECT * FROM parcelas WHERE id_parcela = $parcela";
        $consulta = mysqli_query($conexion, $instruccion) or die("Fallo en la consulta");

        if ($resultado = mysqli_fetch_array($consulta)) {
            echo "<div class='titulo-principal'>";
            echo "<h2>Detalles de la parcela seleccionada</h2>";
            echo "</div>";

            echo "<div class='tabla-contenedor'>";
            echo "<table><tr><th>ID Parcela</th><th>Ubicación</th></tr>";
            echo "<tr><td>{$resultado['id_parcela']}</td><td>{$resultado['ubicacion']}</td></tr></table>";
            echo "</div>";

            $fichero = '../agregar/parcelas/' . $resultado['fichero'];
            $geojson = file_exists($fichero) ? file_get_contents($fichero) : null;

            if (!$geojson) {
                echo "<div class='mensaje-error'>";
                echo "<p>⚠ Error: No se pudo cargar el archivo GeoJSON de la parcela.</p>";
                echo "</div>";
            }

            $sqlRutas = "SELECT COUNT(*) as total FROM ruta WHERE id_parcela = $parcela";
            $consultaRutas = mysqli_query($conexion, $sqlRutas);
            $hayRutas = (mysqli_fetch_assoc($consultaRutas)['total'] > 0);

            if ($hayRutas) {
                echo "<div class='info-ruta existente'>";
                echo "<h3>Ruta existente</h3>";
                echo "<p>Esta parcela ya tiene una ruta definida. Si agregas una nueva, reemplazará la actual.</p>";
                echo "</div>";
            }

            echo "<form action='ruta.php' id='mi_formulario' method='post'>";
            echo "<input type='hidden' name='parcela' value='{$parcela}'>";
            echo "<input type='hidden' id='vacio' name='vacio' value='0'>";
?>

<div class="titulo-principal">
    <h2>Mapa de la parcela</h2>
</div>

<div class="mapa-contenedor">
    <div id="map"></div>
</div>

<div class="instrucciones">
    <h3>Puntos seleccionados: <span id="puntos-seleccionados">0</span></h3>
</div>

<script>
let puntosSeleccionados = 0;
let rutalat = [];
let rutalon = [];

let map = L.map('map').setView([40.24, -3.7038], 6);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19
}).addTo(map);

setTimeout(() => map.invalidateSize(), 100);

<?php if ($geojson): ?>
let geojsonLayer = L.geoJSON(<?php echo $geojson; ?>, {
    style: {
        color: '#45f3ff',
        weight: 3,
        opacity: 0.7,
        fillColor: '#45f3ff',
        fillOpacity: 0.3
    }
}).addTo(map);
map.fitBounds(geojsonLayer.getBounds());
<?php endif; ?>

let rutaLinea = L.polyline([], {
    color: '#ff4545',
    weight: 3,
    opacity: 0.7,
    dashArray: '5, 10'
}).addTo(map);

<?php 
if ($hayRutas) {
    echo "let rutaExistente = [";
    $sqlPuntos = "SELECT latitud, longitud FROM ruta WHERE id_parcela = $parcela ORDER BY id_ruta";
    $consultaPuntos = mysqli_query($conexion, $sqlPuntos);
    $puntosRuta = [];
    while ($punto = mysqli_fetch_assoc($consultaPuntos)) {
        $puntosRuta[] = "[" . $punto['latitud'] . ", " . $punto['longitud'] . "]";
    }
    echo implode(", ", $puntosRuta);
    echo "];";

    echo "
let rutaExistenteLine = L.polyline(rutaExistente, {
    color: '#4caf50',
    weight: 4,
    opacity: 0.8
}).addTo(map);

rutaExistente.forEach((p, i) => {
    L.marker(p, { title: 'Punto existente ' + (i+1) })
        .bindTooltip((i+1).toString(), { permanent: true, direction: 'center', className: 'marker-number-existente' })
        .addTo(map);
});
";
}
?>

map.on('click', function (e) {
    let latlng = e.latlng;
    <?php if ($geojson): ?>
    if (geojsonLayer.getBounds().contains(latlng)) {
        document.getElementById('vacio').value = "1";
        puntosSeleccionados++;
        rutalat.push(latlng.lat);
        rutalon.push(latlng.lng);

        L.marker(latlng, { title: 'Punto ' + puntosSeleccionados })
            .bindTooltip(puntosSeleccionados.toString(), { permanent: true, direction: 'center', className: 'marker-number' })
            .addTo(map);

        rutaLinea.addLatLng(latlng);
        document.getElementById('puntos-seleccionados').innerText = puntosSeleccionados;

        let inputLat = document.createElement('input');
        inputLat.type = 'hidden';
        inputLat.name = 'latitud[]';
        inputLat.value = latlng.lat;

        let inputLon = document.createElement('input');
        inputLon.type = 'hidden';
        inputLon.name = 'longitud[]';
        inputLon.value = latlng.lng;

        document.getElementById('mi_formulario').appendChild(inputLat);
        document.getElementById('mi_formulario').appendChild(inputLon);
    } else {
        alert("Por favor, selecciona un punto dentro de la parcela.");
    }
    <?php endif; ?>
});
</script>

<div class="botones-contenedor">
    <input type="submit" name="ruta" value="Agregar ruta">
</form>
</div>

<div class="volver-form">
    <form action="ver_parcelas.php" method="post">
        <input type="submit" value="Seleccionar otra parcela">
    </form>
    
    <form action="../parcelas.php" method="post">
        <input type="submit" name="volverReg" value="Volver">
    </form>
</div>

<?php
        }
    } else {
        // Mostrar todas las parcelas (si admin) o solo las del usuario
        $instruccion = $esAdmin ?
            "SELECT * FROM parcelas" :
            "SELECT p.* FROM parcelas p INNER JOIN parcelas_usuarios pu ON p.id_parcela = pu.id_parcela WHERE pu.id_usr = '$idUsr'";
        
        $consulta = mysqli_query($conexion, $instruccion);

        echo "<div class='titulo-principal'><h2>Listado de parcelas</h2></div>";
        echo "<div class='tabla-contenedor'><table>";
        echo "<tr><th>ID Parcela</th><th>Ubicación</th><th>Acciones</th></tr>";

        if (mysqli_num_rows($consulta) > 0) {
            while ($fila = mysqli_fetch_array($consulta)) {
                echo "<tr>";
                echo "<td>{$fila['id_parcela']}</td>";
                echo "<td>{$fila['ubicacion']}</td>";
                echo "<td>
                        <form action='ver_parcelas.php' method='post'>
                            <input type='hidden' name='parcela' value='{$fila['id_parcela']}'>
                            <input type='submit' name='ver' value='Ver parcela'>
                        </form>
                      </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3'>No hay parcelas disponibles</td></tr>";
        }

        echo "</table></div>";
        echo "<div class='volver-form'>
                <form action='../parcelas.php' method='post'>
                    <input type='submit' name='volverReg' value='Volver'>
                </form>
              </div>";
    }
} else {
    echo "<div class='mensaje-error'>
            <h2>Acceso denegado</h2>
            <p>No tienes permisos para acceder a esta página. Por favor, inicia sesión.</p>
          </div>";
    echo "<div class='volver-form'>
            <a href='javascript:history.back()'><button>Volver</button></a>
          </div>";
    session_destroy();
}
?>
</body>
</html>
