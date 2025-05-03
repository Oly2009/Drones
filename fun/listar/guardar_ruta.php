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
$id_parcela = $_GET['id_parcela'] ?? null;
if (!$id_parcela) {
    header('Location: ../../menu/parcelas.php');
    exit;
}

$parcela = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM parcelas WHERE id_parcela = $id_parcela"));
$puntos = mysqli_query($conexion, "SELECT * FROM ruta WHERE id_parcela = $id_parcela");
include '../../componentes/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="../../css/style.css">

<body class="d-flex flex-column min-vh-100">
<main class="container py-4 flex-grow-1">
  <h2 class="text-center mb-4"><i class="fas fa-map-marked-alt"></i> Ruta guardada</h2>

  <div class="row">
    <div class="col-lg-12 d-flex flex-column flex-lg-row gap-4">
      <!-- INFO IZQUIERDA -->
      <div class="flex-fill">
        <div class="mb-3 p-3 text-center" style="background-color: #dcedc8; border-radius: 10px;">
          <h4 class="text-success fw-bold mb-2">Ubicación</h4>
          <p style="background: #fffde7; padding: 0.7rem; border-radius: 8px;">
            <?= htmlspecialchars($parcela['ubicacion']) ?>
          </p>
        </div>
        <div class="text-center p-3 mb-3" style="background-color: #e8f5e9; border-radius: 10px;">
          <h5 class="text-success">Total puntos: <?= $puntos->num_rows ?></h5>
        </div>
        <div id="mapa" class="bg-light" style="height: 400px; width: 100%;"></div>
      </div>

      <!-- TABLA DERECHA -->
      <div class="flex-fill">
        <div class="table-responsive">
          <table class="table text-center">
            <thead class="table-success">
              <tr>
                <th>#</th>
                <th>Latitud</th>
                <th>Longitud</th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 1; while ($p = mysqli_fetch_assoc($puntos)): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= $p['latitud'] ?></td>
                <td><?= $p['longitud'] ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

<div class="text-center mt-4">
    <a href="lis_parcelas.php?id=<?= $id_parcela ?>" class="btn btn-danger btn-sm px-5 py-2 d-inline-flex align-items-center">
        <i class="fas fa-chevron-left me-2"></i> ⬅️ Volver
    </a>
</div>


</main>

<script>
const mapa = L.map('mapa').setView([<?= $parcela['latitud'] ?>, <?= $parcela['longitud'] ?>], 16);
L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: 'Leaflet | Tiles © Esri',
    maxZoom: 19
}).addTo(mapa);

// Mostrar delimitación de la parcela
fetch('../agregar/parcelas/<?= $parcela['fichero'] ?>')
.then(response => response.json())
.then(data => {
  L.geoJSON(data, {
    style: {
      color: '#28a745',
      fillColor: '#81c784',
      fillOpacity: 0.3,
      weight: 2
    }
  }).addTo(mapa);
});

let puntos = <?php
  $puntos->data_seek(0);
  $coords = [];
  while ($p = mysqli_fetch_assoc($puntos)) $coords[] = [$p['latitud'], $p['longitud']];
  echo json_encode($coords);
?>;

let polyline = L.polyline(puntos, { color: '#d32f2f', dashArray: '5, 10' }).addTo(mapa);

puntos.forEach((p, i) => {
  L.marker(p, {
    icon: L.divIcon({
      className: 'custom-icon',
      html: `<div style="background:#2e7d32;color:white;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;">${i + 1}</div>`,
      iconSize: [24, 24]
    })
  }).addTo(mapa);
});

mapa.fitBounds(polyline.getBounds());
</script>

<?php include '../../componentes/footer.php'; ?>
</body>