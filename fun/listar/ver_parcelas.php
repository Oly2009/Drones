<?php
include '../../lib/functiones.php';
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar / Modificar ruta</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.0.2/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.0.2/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" type="text/css" href="../../css/verParcelas.css">
</head>
<body>

<a href="../fun/ayuda/instrucciones.php?origen=ver_parcelas.php" class="icono-ayuda" title="Ayuda">
    <i class="bi bi-question-circle"></i>
</a>

<?php
if (isset($_SESSION['usuario'])) {
    $idUsr = $_SESSION['usuario']['id_usr'];
    $conexion = conectar();

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

        if (!$esAdmin) {
            $verificacion = "SELECT COUNT(*) as total FROM parcelas_usuarios WHERE id_usr = '$idUsr' AND id_parcela = '$parcela'";
            $verificarConsulta = mysqli_query($conexion, $verificacion);
            if (mysqli_fetch_assoc($verificarConsulta)['total'] == 0) {
                echo "<div class='mensaje-error'><p>No tienes permisos para ver esta parcela.</p></div>";
                exit;
            }
        }

        $instruccion = "SELECT * FROM parcelas WHERE id_parcela = $parcela";
        $consulta = mysqli_query($conexion, $instruccion);

        if ($resultado = mysqli_fetch_array($consulta)) {
            $fichero = '../agregar/parcelas/' . $resultado['fichero'];
            $geojson = file_exists($fichero) ? file_get_contents($fichero) : null;

            $sqlRutas = "SELECT COUNT(*) as total FROM ruta WHERE id_parcela = $parcela";
            $consultaRutas = mysqli_query($conexion, $sqlRutas);
            $hayRutas = (mysqli_fetch_assoc($consultaRutas)['total'] > 0);

            echo "<div class='titulo-principal'><h2>Agregar / Modificar ruta</h2></div>";
            echo "<div class='contenedor-flex'>";

            echo "<div class='bloque'>";
            echo "<div class='tabla-contenedor'><table><tr><th>Ubicación</th></tr>";
            echo "<tr><td>{$resultado['ubicacion']}</td></tr></table></div>";

            if ($hayRutas) {
                echo "<div class='info-ruta existente'><h3>Ruta existente</h3><p>Esta parcela ya tiene una ruta definida. Si agregas una nueva, reemplazará la actual.</p></div>";
            }

            echo "<div class='instrucciones'><h3>Puntos seleccionados: <span id='puntos-seleccionados'>0</span></h3></div>";
            echo "</div>"; 
            echo "<div class='bloque'><div id='map'></div></div>";
            echo "</div>";

            echo "<form action='ruta.php' id='mi_formulario' method='post'>";
            echo "<input type='hidden' name='parcela' value='{$parcela}'>";
            echo "<input type='hidden' id='vacio' name='vacio' value='0'>";

            echo "<div class='fila-botones'>";
            echo "<input type='submit' name='ruta' value='Guardar nueva ruta' class='boton-ancho-total'>";
            echo "</form>";

            echo "<form action='ver_parcelas.php' method='post'>
                    <input type='submit' value='Seleccionar otra parcela' class='boton-ancho-total'>
                  </form>";
            echo "<form action='../../menu/parcelas.php' method='post'>
                    <input type='submit' value='Volver' class='boton-ancho-total'>
                  </form>";
            echo "</div>";

            echo "<script>
let puntosSeleccionados = 0;
let map = L.map('map').setView([40.24, -3.7038], 6);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
setTimeout(() => map.invalidateSize(), 100);
";

            if ($geojson) {
                echo "let geojsonLayer = L.geoJSON($geojson, {
                    style: { color: '#45f3ff', weight: 3, opacity: 0.7, fillColor: '#45f3ff', fillOpacity: 0.3 }
                }).addTo(map);
                map.fitBounds(geojsonLayer.getBounds());\n";
            }

            echo "let rutaLinea = L.polyline([], {
                color: '#ff4545',
                weight: 3,
                opacity: 0.7,
                dashArray: '5, 10'
            }).addTo(map);";

            if ($hayRutas) {
                echo "let rutaExistente = [";
                $sqlPuntos = "SELECT latitud, longitud FROM ruta WHERE id_parcela = $parcela ORDER BY id_ruta";
                $consultaPuntos = mysqli_query($conexion, $sqlPuntos);
                while ($p = mysqli_fetch_assoc($consultaPuntos)) {
                    echo "[{$p['latitud']}, {$p['longitud']}],";
                }
                echo "];
L.polyline(rutaExistente, { color: '#4caf50', weight: 4, opacity: 0.8 }).addTo(map);
rutaExistente.forEach((p, i) => {
    L.marker(p, { title: 'Punto existente ' + (i+1) })
     .bindTooltip((i+1).toString(), { permanent: true, direction: 'center', className: 'marker-number-existente' })
     .addTo(map);
});\n";
            }

            echo "map.on('click', function (e) {
    let latlng = e.latlng;
    if (geojsonLayer.getBounds().contains(latlng)) {
        document.getElementById('vacio').value = \"1\";
        puntosSeleccionados++;

        L.marker(latlng, { title: 'Punto ' + puntosSeleccionados })
            .bindTooltip(puntosSeleccionados.toString(), { permanent: true, direction: 'center', className: 'marker-number' })
            .addTo(map);

        rutaLinea.addLatLng(latlng);
        document.getElementById('puntos-seleccionados').innerText = puntosSeleccionados;

        let form = document.getElementById('mi_formulario');
        ['latitud', 'longitud'].forEach((campo, idx) => {
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = campo + '[]';
            input.value = idx === 0 ? latlng.lat : latlng.lng;
            form.appendChild(input);
        });
    } else {
        alert(\"Por favor, selecciona un punto dentro de la parcela.\");
    }
});
</script>";
        }
    } else {
        echo "<div class='titulo-principal'><h2>Listado de parcelas</h2></div>";

        echo "<div class='buscador'>
                <form method='post'>
                    <input type='text' name='busqueda' placeholder='Buscar ubicación...' class='input-busqueda'>
                    <input type='submit' name='buscar' value='Buscar' class='boton-buscar'>
                </form>
              </div>";

        $busqueda = isset($_POST['busqueda']) ? trim($_POST['busqueda']) : '';

        $sql = ($busqueda !== '') ?
            ($esAdmin ?
                "SELECT p.*, (SELECT COUNT(*) FROM ruta r WHERE r.id_parcela = p.id_parcela) as tiene_ruta 
                 FROM parcelas p WHERE p.ubicacion LIKE '%$busqueda%'" :
                "SELECT p.*, (SELECT COUNT(*) FROM ruta r WHERE r.id_parcela = p.id_parcela) as tiene_ruta 
                 FROM parcelas p INNER JOIN parcelas_usuarios pu ON p.id_parcela = pu.id_parcela 
                 WHERE pu.id_usr = '$idUsr' AND p.ubicacion LIKE '%$busqueda%'") :
            ($esAdmin ?
                "SELECT p.*, (SELECT COUNT(*) FROM ruta r WHERE r.id_parcela = p.id_parcela) as tiene_ruta FROM parcelas p" :
                "SELECT p.*, (SELECT COUNT(*) FROM ruta r WHERE r.id_parcela = p.id_parcela) as tiene_ruta 
                 FROM parcelas p INNER JOIN parcelas_usuarios pu ON p.id_parcela = pu.id_parcela 
                 WHERE pu.id_usr = '$idUsr'");

        $consulta = mysqli_query($conexion, $sql);

        echo "<div class='caja-contenedora'>";
        echo "<div class='tabla-contenedor'><table>";
        echo "<tr><th>Ubicación</th><th>Ruta</th><th>Acciones</th></tr>";

        if (mysqli_num_rows($consulta) > 0) {
            while ($fila = mysqli_fetch_array($consulta)) {
                $estadoRuta = $fila['tiene_ruta'] > 0 ? "✅ Sí" : "❌ No";
                echo "<tr>
                        <td>{$fila['ubicacion']}</td>
                        <td>$estadoRuta</td>
                        <td>
                            <form method='post'>
                                <input type='hidden' name='parcela' value='{$fila['id_parcela']}'>
                                <input type='submit' name='ver' value='Ver / Modificar' class='btn-ver'>
                            </form>
                        </td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='3'>No hay parcelas disponibles</td></tr>";
        }

        echo "</table></div>";
        echo "</div>";

        echo "<div class='volver-form'>
                <form action='../../menu/parcelas.php' method='post'>
                    <input type='submit' value='Volver al menú'>
                </form>
              </div>";
    }
} else {
    echo "<div class='mensaje-error'>
            <h2>⛔ Acceso denegado</h2>
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