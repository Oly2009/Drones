<?php
include '../../lib/functiones.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "Acceso denegado",
                text: "Debes iniciar sesión para acceder a esta página",
                icon: "error",
                confirmButtonText: "Volver",
                confirmButtonColor: "#dc3545"
            }).then(() => window.location.href = "../../index.php");
        });
    </script>';
    exit;
}

$conexion = conectar();
$id_parcela = $_GET['id'] ?? null;

if (!$id_parcela) {
    header('Location: ../../menu/parcelas.php');
    exit;
}

$consulta = mysqli_query($conexion, "SELECT * FROM parcelas WHERE id_parcela = $id_parcela");
$parcela = mysqli_fetch_assoc($consulta);

if (!$parcela) {
    echo "Parcela no encontrada.";
    exit;
}

$ruta_existente = mysqli_query($conexion, "SELECT * FROM ruta WHERE id_parcela = $id_parcela");
$puntos_guardados = mysqli_fetch_all($ruta_existente, MYSQLI_ASSOC);
$existe_ruta = count($puntos_guardados) > 0;

include '../../componentes/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="../../css/style.css">

<body class="d-flex flex-column min-vh-100">
<main class="container py-4 flex-grow-1">
    <h2 class="text-center mb-4"><i class="fas fa-route"></i> Agregar Ruta al Dron</h2>

    <?php if ($existe_ruta): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'warning',
                title: 'Ruta ya existente',
                html: '<p>Esta parcela ya tiene una ruta guardada.</p><p>Haz clic en el mapa para definir una nueva. La anterior se mostrará como referencia y se reemplazará al guardar.</p>',
                confirmButtonText: '<i class="bi bi-check-circle"></i> Entendido',
                confirmButtonColor: '#ffc107'
            });
        });
        </script>
    <?php endif; ?>

    <div class="contenedor-formulario">
        <div id="mapa" class="bg-light"></div>

        <div class="formulario">
            <div>
                <div class="mb-4 text-center p-3" style="background-color: #dcedc8; border-radius: 10px;">
                    <h4 class="text-success fw-bold mb-2">Ubicación</h4>
                    <p style="background: #fffde7; padding: 0.7rem; border-radius: 8px;">
                        <?= htmlspecialchars($parcela['ubicacion']) ?>
                    </p>
                </div>

                <div class="text-center p-3" style="background-color: #e8f5e9; border-radius: 10px;">
                    <h5 class="text-success">Puntos seleccionados: <span id="contadorPuntos">0</span></h5>
                </div>
            </div>

            <form method="post" class="d-flex gap-3 mt-4">
                <input type="hidden" name="datos_ruta" id="datosRuta">
                <button type="submit" class="btn btn-success w-100 btn-sm"><i class="bi bi-save me-2"></i>Guardar ruta</button>
                <button type="button" class="btn btn-warning w-100 btn-sm" onclick="location.reload();"><i class="bi bi-arrow-clockwise me-2"></i>Seleccionar otra</button>
                <a href="lis_parcelas.php" class="btn btn-danger w-100 btn-sm d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-box-arrow-left me-2"></i>Volver
                </a>
            </form>
        </div>
    </div>
</main>

<script>
let mapa = L.map('mapa').setView([<?= $parcela['latitud'] ?>, <?= $parcela['longitud'] ?>], 16);
L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: 'Leaflet | Tiles © Esri',
    maxZoom: 19
}).addTo(mapa);

let parcelaPoligono = null;
fetch('../agregar/parcelas/<?= $parcela['fichero'] ?>')
.then(response => response.json())
.then(data => {
    parcelaPoligono = L.geoJSON(data, {
        style: {
            color: '#28a745',
            fillColor: '#81c784',
            fillOpacity: 0.3,
            weight: 2
        }
    }).addTo(mapa);
    mapa.fitBounds(parcelaPoligono.getBounds());
});

let puntos = [];
let linea;
let primeraModificacion = true;

<?php if ($existe_ruta): ?>
    const puntosGuardados = <?= json_encode($puntos_guardados) ?>;
    const coordsAntiguos = puntosGuardados.map(p => [parseFloat(p.latitud), parseFloat(p.longitud)]);
    L.polyline(coordsAntiguos, {
        color: '#d32f2f',
        dashArray: '5, 10',
        weight: 2
    }).addTo(mapa);
<?php endif; ?>

mapa.on('click', function(e) {
    const { lat, lng } = e.latlng;

    if (!parcelaPoligono || !leafletPuntoDentroDePoligono(e.latlng, parcelaPoligono)) {
        Swal.fire('Punto fuera de la parcela', '', 'warning');
        return;
    }

    if (primeraModificacion) {
        puntos = [];
        document.querySelectorAll('.leaflet-marker-icon').forEach(el => el.remove());
        if (linea) mapa.removeLayer(linea);
        primeraModificacion = false;
    }

    const numero = puntos.length + 1;
    const marcador = L.marker([lat, lng], {
        icon: L.divIcon({
            className: 'custom-icon',
            html: `<div style="background:#4caf50;color:white;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;">${numero}</div>`,
            iconSize: [24, 24]
        })
    }).addTo(mapa);

    puntos.push({ lat, lng });

    if (linea) mapa.removeLayer(linea);
    linea = L.polyline(puntos.map(p => [p.lat, p.lng]), {
        color: '#388e3c',
        weight: 3
    }).addTo(mapa);

    document.getElementById('contadorPuntos').textContent = puntos.length;
    document.getElementById('datosRuta').value = JSON.stringify(puntos);
});

function leafletPuntoDentroDePoligono(punto, geojsonLayer) {
    let dentro = false;
    geojsonLayer.eachLayer(layer => {
        if (layer instanceof L.Polygon && layer.getBounds().contains(punto)) {
            dentro = true;
        }
    });
    return dentro;
}
</script>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['datos_ruta'])) {
    $puntos = json_decode($_POST['datos_ruta'], true);
    if (is_array($puntos) && count($puntos) >= 2) {
        mysqli_query($conexion, "DELETE FROM ruta WHERE id_parcela = $id_parcela");

        $stmt = mysqli_prepare($conexion, "INSERT INTO ruta (latitud, longitud, id_parcela) VALUES (?, ?, ?)");
        foreach ($puntos as $p) {
            $lat = $p['lat'] ?? null;
            $lng = $p['lng'] ?? null;
            if ($lat && $lng) {
                mysqli_stmt_bind_param($stmt, 'ddi', $lat, $lng, $id_parcela);
                mysqli_stmt_execute($stmt);
            }
        }
        mysqli_stmt_close($stmt);

        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Ruta guardada con éxito',
                    confirmButtonText: 'Ver ruta',
                    confirmButtonColor: '#28a745'
                }).then(() => {
                    window.location.href = 'guardar_ruta.php?id_parcela=$id_parcela';
                });
            });
        </script>";
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire('Debes seleccionar al menos 2 puntos.', '', 'warning');
            });
        </script>";
    }
}
?>

<?php include '../../componentes/footer.php'; ?>
</body>
